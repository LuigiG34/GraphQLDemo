<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    public function load(ObjectManager $em): void
    {
        $authors = [];

        $names = ['Frank Herbert', 'Ursula K. Le Guin', 'Isaac Asimov'];
        foreach ($names as $n) {
            $a = new Author();
            $a->setName($n);
            $em->persist($a);
            $authors[] = $a;
        }

        $books = [
            ['Dune',  '1965-06-01', 0],
            ['Dune Messiah', '1969-10-01', 0],
            ['The Left Hand of Darkness', '1969-01-01', 1],
            ['A Wizard of Earthsea', '1968-01-01', 1],
            ['Foundation', '1951-01-01', 2],
        ];

        foreach ($books as [$title, $date, $authorIndex]) {
            $b = new Book();
            $b->setTitle($title);
            $b->setPublishedAt(new \DateTimeImmutable($date.'T00:00:00+00:00'));
            $b->setAuthor($authors[$authorIndex]);
            $em->persist($b);
        }

        $em->flush();
    }
}
