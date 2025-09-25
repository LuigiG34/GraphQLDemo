<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class AuthorsController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $http,
        private RouterInterface $router
    ) {}

    private function gql(string $query, array $variables = []): array
    {
        $path = $this->router->generate('api_graphql_entrypoint', [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $res = $this->http->request('POST', 'http://nginx' . $path, [
            'json' => ['query' => $query, 'variables' => $variables],
            'headers' => ['Accept' => 'application/json'],
        ]);

        $payload = $res->toArray(false);
        if (!empty($payload['errors'])) {
            $err = $payload['errors'][0] ?? [];
            $msg = ($err['message'] ?? 'GraphQL error')
                 . (!empty($err['extensions']['debugMessage']) ? ' — '.$err['extensions']['debugMessage'] : '');
            throw new \RuntimeException($msg);
        }
        return $payload['data'] ?? [];
    }

    #[Route('/authors', name: 'authors_index')]
    public function index(): Response
    {
        $q = <<<'GQL'
        query {
          authors(first: 100) {
            edges { node { id name } }
            totalCount
          }
        }
        GQL;

        $d = $this->gql($q);

        return $this->render('authors/index.html.twig', [
            'authors' => array_map(fn($e) => $e['node'], $d['authors']['edges'] ?? []),
            'total'   => $d['authors']['totalCount'] ?? 0,
        ]);
    }

    #[Route('/authors/new', name: 'authors_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));

            $m = <<<'GQL'
            mutation($input: createAuthorInput!) {
              createAuthor(input: $input) { author { id } }
            }
            GQL;

            try {
                $this->gql($m, ['input' => ['name' => $name]]);
                $this->addFlash('success', 'Author created.');
                return $this->redirectToRoute('authors_index');
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('authors/new.html.twig');
    }
}
