<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class BooksController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $http,
        private RouterInterface $router
    ) {}

    private function gql(string $query, array $variables = []): array
    {
        $endpoint = $this->router->generate('api_graphql_entrypoint', [], UrlGeneratorInterface::ABSOLUTE_PATH);

        $res = $this->http->request('POST', 'http://nginx' . $endpoint, [
            'json' => ['query' => $query, 'variables' => $variables],
            'headers' => ['Accept' => 'application/json'],
        ]);

        $payload = $res->toArray(false);
        if (!empty($payload['errors'])) {
            $err = $payload['errors'][0];
            $msg = $err['message'] ?? 'GraphQL error';
            if (!empty($err['extensions']['debugMessage'])) {
                $msg .= ' — ' . $err['extensions']['debugMessage'];
            }
            if (!empty($err['path'])) {
                $msg .= ' (at ' . implode('.', $err['path']) . ')';
            }
            throw new \RuntimeException($msg);
        }

        return $payload['data'] ?? [];
    }

    #[Route('/books', name: 'books_index')]
    public function index(): Response
    {
        $query = <<<'GQL'
        query ($n:Int!) {
          books(first: $n) {
            totalCount
            edges { node { id title publishedAt author { id name } } }
          }
        }
        GQL;

        $data = $this->gql($query, ['n' => 50]);

        return $this->render('books/index.html.twig', [
            'books' => array_map(fn ($e) => $e['node'], $data['books']['edges'] ?? []),
            'total' => $data['books']['totalCount'] ?? 0,
        ]);
    }

    #[Route('/books/new', name: 'books_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $title = trim((string) $request->request->get('title', ''));
            $publishedAt = trim((string) $request->request->get('publishedAt', ''));
            $authorIri = $request->request->get('authorIri') ?: null;

            $mutation = <<<'GQL'
            mutation($input: createBookInput!) {
              createBook(input: $input) { book { id } }
            }
            GQL;

            $input = [
                'title' => $title,
                'publishedAt' => $publishedAt !== '' ? $publishedAt : null,
                'author' => $authorIri,
            ];

            try {
                $this->gql($mutation, ['input' => $input]);
                $this->addFlash('success', 'Book created.');
                return $this->redirectToRoute('books_index');
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $authorsQuery = <<<'GQL'
        query { authors(first: 100) { edges { node { id name } } } }
        GQL;
        $authorsData = $this->gql($authorsQuery);
        $authors = array_map(fn($e) => $e['node'], $authorsData['authors']['edges'] ?? []);

        return $this->render('books/new.html.twig', ['authors' => $authors]);
    }

    #[Route('/books/{id<\d+>}', name: 'books_show')]
    public function show(int $id): Response
    {
        $query = <<<'GQL'
        query($id: ID!) {
          book(id: $id) { id title publishedAt author { id name } }
        }
        GQL;

        $data = $this->gql($query, ['id' => "/api/books/$id"]);
        if (!($data['book'] ?? null)) {
            throw $this->createNotFoundException();
        }

        return $this->render('books/show.html.twig', ['book' => $data['book']]);
    }

    #[Route('/books/{id<\d+>}/delete', name: 'books_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $mutation = <<<'GQL'
        mutation($id: ID!) { deleteBook(input: { id: $id }) { book { id } } }
        GQL;

        try {
            $this->gql($mutation, ['id' => "/api/books/$id"]);
            $this->addFlash('success', 'Book deleted.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('books_index');
    }
}
