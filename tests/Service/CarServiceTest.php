<?php

namespace App\Tests\Service;

use App\Entity\Car;
use App\Service\CarService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CarServiceTest extends TestCase
{
    private CarService $carService;
    private \PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->carService = new CarService($this->entityManager);
    }

    public function testGetAllCars()
    {
        $mockRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->with(Car::class)
            ->willReturn($mockRepo);

        $mockRepo->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $cars = $this->carService->getAll();
        $this->assertIsArray($cars);
    }

    public function testGetCarById()
    {
        $car = new Car();
        $mockRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->with(Car::class)
            ->willReturn($mockRepo);

        $mockRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($car);

        $result = $this->carService->get(1);
        $this->assertSame($car, $result);
    }
}
