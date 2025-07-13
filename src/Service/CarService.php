<?php

namespace App\Service;

use App\Entity\Car;
use Doctrine\ORM\EntityManagerInterface;

class CarService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function create(Car $car): Car
    {
        $this->em->persist($car);
        $this->em->flush();
        return $car;
    }

    public function update(): void
    {
        $this->em->flush();
    }

    public function delete(Car $car): void
    {
        $this->em->remove($car);
        $this->em->flush();
    }

    public function getAll(): array
    {
        return $this->em->getRepository(Car::class)->findAll();
    }

    public function get(int $id): ?Car
    {
        return $this->em->getRepository(Car::class)->find($id);
    }
}
