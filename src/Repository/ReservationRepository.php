<?php

namespace App\Repository;

use App\Entity\Car;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findConflictingReservation(
    Car $car,
    \DateTimeInterface $startDate,
    \DateTimeInterface $endDate,
    ?int $excludeId = null
): ?Reservation {
    $qb = $this->createQueryBuilder('r')
        ->where('r.car = :car')
        ->andWhere('r.startDate < :endDate')
        ->andWhere('r.endDate > :startDate')
        ->setParameter('car', $car)
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate);

    if ($excludeId) {
        $qb->andWhere('r.id != :excludeId')
            ->setParameter('excludeId', $excludeId);
    }

    return $qb->getQuery()->getOneOrNullResult();
}
}
