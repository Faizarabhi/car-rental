<?php

namespace App\Service;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;

class ReservationService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function create(Reservation $reservation): Reservation
    {
        $this->em->persist($reservation);
        $this->em->flush();
        return $reservation;
    }

    public function update(): void
    {
        $this->em->flush();
    }

    public function delete(Reservation $reservation): void
    {
        $this->em->remove($reservation);
        $this->em->flush();
    }

    public function getAll(): array
    {
        return $this->em->getRepository(Reservation::class)->findAll();
    }

    public function get(int $id): ?Reservation
    {
        return $this->em->getRepository(Reservation::class)->find($id);
    }
}
