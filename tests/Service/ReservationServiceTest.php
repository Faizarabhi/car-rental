<?php

namespace App\Tests\Service;

use App\Entity\Reservation;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ReservationServiceTest extends TestCase
{
    private ReservationService $reservationService;
    private \PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->reservationService = new ReservationService($this->entityManager);
    }

    public function testCreateReservation()
    {
        $reservation = new Reservation();

        $this->entityManager->expects($this->once())->method('persist')->with($reservation);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->reservationService->create($reservation);
        $this->assertSame($reservation, $result);
    }

    public function testUpdateReservation()
    {
        $this->entityManager->expects($this->once())->method('flush');
        $this->reservationService->update();
        $this->assertTrue(true);
    }

    public function testDeleteReservation()
    {
        $reservation = new Reservation();

        $this->entityManager->expects($this->once())->method('remove')->with($reservation);
        $this->entityManager->expects($this->once())->method('flush');

        $this->reservationService->delete($reservation);
        $this->assertTrue(true);
    }

    public function testGetAllReservations()
    {
        $mockRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $this->entityManager
            ->method('getRepository')
            ->with(Reservation::class)
            ->willReturn($mockRepo);

        $mockRepo->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->reservationService->getAll();
        $this->assertIsArray($result);
    }

    public function testGetReservationById()
    {
        $reservation = new Reservation();
        $mockRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $this->entityManager
            ->method('getRepository')
            ->with(Reservation::class)
            ->willReturn($mockRepo);

        $mockRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($reservation);

        $result = $this->reservationService->get(1);
        $this->assertSame($reservation, $result);
    }
}
