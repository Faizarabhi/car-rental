<?php

namespace App\Controller;

use App\Dto\CarDTO;
use App\Entity\Car;
use App\Service\CarService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/cars')]
#[Security(name: 'Bearer')]
class CarController extends AbstractController
{
    public function __construct(
        private CarService $carService,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private SerializerInterface $serializer
    ) {}

    private function toDto(Car $car): CarDTO
    {
        $dto = new CarDTO();
        $dto->id = $car->getId();
        $dto->brand = $car->getBrand();
        $dto->name = $car->getName();
        $dto->pricePerDay = $car->getPricePerDay();

        return $dto;
    }

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        path: '/api/cars',
        tags: ['cars'],
        summary: 'Get all cars',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of cars',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: CarDTO::class))
            )
        ]
    )]
    public function index(): JsonResponse
{

    $cars = $this->carService->getAll();
    $carDtos = array_map(fn(Car $car) => $this->toDto($car), $cars);

    return $this->json($carDtos);
}

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/cars/{id}',
        tags: ['cars'],
        summary: 'Get a car by id',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID of the car', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Car details', content: new OA\JsonContent(ref: CarDTO::class)),
            new OA\Response(response: 404, description: 'Car not found')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $car = $this->carService->get($id);
        if (!$car) {
            return $this->json(['error' => 'Car not found'], 404);
        }
        $dto = $this->toDto($car);
        return $this->json($dto);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        path: '/api/cars',
        tags: ['cars'],
        summary: 'Create a new car',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'JSON data to create a car',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'brand', type: 'string', nullable: true, example: 'Toyota', description: 'Brand of the car'),
                    new OA\Property(property: 'name', type: 'string', example: 'Corolla', description: 'Model name'),
                    new OA\Property(property: 'pricePerDay', type: 'number', format: 'float', example: 49.99, description: 'Price per day rental'),
                ],
                required: ['name', 'pricePerDay']
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Car created', content: new OA\JsonContent(ref: CarDTO::class)),
            new OA\Response(response: 400, description: 'Invalid input')
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $car = $this->serializer->deserialize($request->getContent(), Car::class, 'json');
        $errors = $this->validator->validate($car);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string)$errors], 400);
        }

        $this->carService->create($car);
        $dto = $this->toDto($car);

        return $this->json($dto, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/cars/{id}',
        tags: ['cars'],
        summary: 'Update a car',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID of the car', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'JSON data to update a car',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'brand', type: 'string', nullable: true, example: 'Toyota', description: 'Brand of the car'),
                    new OA\Property(property: 'name', type: 'string', example: 'Corolla', description: 'Model name'),
                    new OA\Property(property: 'pricePerDay', type: 'number', format: 'float', example: 49.99, description: 'Price per day rental'),
                ],
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Car updated', content: new OA\JsonContent(ref: CarDTO::class)),
            new OA\Response(response: 400, description: 'Invalid input'),
            new OA\Response(response: 404, description: 'Car not found')
        ]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        $car = $this->carService->get($id);
        if (!$car) {
            return $this->json(['error' => 'Car not found'], 404);
        }

        $updatedCar = $this->serializer->deserialize(
            $request->getContent(),
            Car::class,
            'json',
            ['object_to_populate' => $car]
        );

        $errors = $this->validator->validate($updatedCar);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string)$errors], 400);
        }

        $this->carService->update();

        $dto = $this->toDto($updatedCar);
        return $this->json($dto);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/cars/{id}',
        tags: ['cars'],
        summary: 'Delete a car',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID of the car', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Car deleted'),
            new OA\Response(response: 404, description: 'Car not found')
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $car = $this->carService->get($id);
        if (!$car) {
            return $this->json(['error' => 'Car not found'], 404);
        }

        $this->carService->delete($car);

        return $this->json(null, 204);
    }
}
