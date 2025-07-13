<?php

namespace App\Controller;

use App\Dto\ReservationDTO;
use App\Entity\Reservation;
use App\Entity\Car;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/reservations')]
#[Security(name: 'Bearer')]
class ReservationController extends AbstractController
{
    public function __construct(
        private ReservationService $reservationService,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private SerializerInterface $serializer,
        private ReservationRepository $reservationRepository
    ) {}

    private function toDto(Reservation $reservation): ReservationDTO
    {
        $dto = new ReservationDTO();
        $dto->id = $reservation->getId();
        $dto->startDate = $reservation->getStartDate()?->format('Y-m-d');
        $dto->endDate = $reservation->getEndDate()?->format('Y-m-d');
        $dto->userId = $reservation->getUser()?->getId();
        $dto->carId = $reservation->getCar()?->getId();

        return $dto;
    }

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        path: '/api/reservations',
        tags: ['reservations'],
        summary: 'Get user reservations',
        responses: [
            new OA\Response(response: 200, description: 'List of user reservations', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#'))),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $reservations = $this->reservationRepository->findBy(['user' => $user]);
        $dtos = array_map(fn($r) => $this->toDto($r), $reservations);
        return $this->json($dtos);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/reservations/{id}',
        tags: ['reservations'],
        summary: 'Get a reservation',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID of the reservation', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reservation details', content: new OA\JsonContent(ref: '#')),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Reservation not found')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $reservation = $this->reservationService->get($id);
        if (!$reservation) {
            return $this->json(['error' => 'Reservation not found'], 404);
        }

        $user = $this->getUser();
        if (!$user || $user !== $reservation->getUser()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $dto = $this->toDto($reservation);
        return $this->json($dto);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        path: '/api/reservations',
        tags: ['reservations'],
        summary: 'Créer une nouvelle réservation',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données JSON pour créer une réservation',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'car_id', type: 'integer', description: 'ID de la voiture à réserver', example: 1),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'Date de début de réservation (YYYY-MM-DD)', example: '2025-07-13'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', description: 'Date de fin de réservation (YYYY-MM-DD)', example: '2025-07-20'),
                ],
                required: ['car_id', 'start_date', 'end_date']
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Réservation créée'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 401, description: 'Non autorisé'),
            new OA\Response(response: 404, description: 'Voiture non trouvée'),
            new OA\Response(response: 409, description: 'Conflit de réservation')
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['car_id'], $data['start_date'], $data['end_date'])) {
            return $this->json(['error' => 'car_id, start_date and end_date are required'], 400);
        }

        $startDate = new \DateTimeImmutable($data['start_date']);
        $endDate = new \DateTimeImmutable($data['end_date']);

        if ($endDate < $startDate) {
            return $this->json(['error' => 'End date must be after start date'], 400);
        }

        $car = $this->em->getRepository(Car::class)->find($data['car_id']);
        if (!$car) {
            return $this->json(['error' => 'Car not found'], 404);
        }

        $existingReservation = $this->reservationRepository->findConflictingReservation(
            $car,
            $startDate,
            $endDate
        );

        if ($existingReservation) {
            return $this->json(['error' => 'Car is already reserved for these dates'], 409);
        }

        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setCar($car);
        $reservation->setStartDate($startDate);
        $reservation->setEndDate($endDate);

        $errors = $this->validator->validate($reservation);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $this->reservationService->create($reservation);

        $dto = $this->toDto($reservation);
        return $this->json($dto, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/reservations/{id}',
        tags: ['reservations'],
        summary: 'modifier une réservation',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la réservation', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données JSON pour mettre à jour une réservation',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'Date de début de réservation (YYYY-MM-DD)', example: '2025-07-13'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', description: 'Date de fin de réservation (YYYY-MM-DD)', example: '2025-07-20'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Réservation mise à jour'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 401, description: 'Non autorisé'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Réservation non trouvée'),
            new OA\Response(response: 409, description: 'Conflit de réservation')
        ]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        $reservation = $this->reservationService->get($id);
        if (!$reservation) {
            return $this->json(['error' => 'Reservation not found'], 404);
        }

        $user = $this->getUser();
        if (!$user || $user !== $reservation->getUser()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $startDate = isset($data['start_date'])
            ? new \DateTimeImmutable($data['start_date'])
            : $reservation->getStartDate();

        $endDate = isset($data['end_date'])
            ? new \DateTimeImmutable($data['end_date'])
            : $reservation->getEndDate();

        if ($endDate < $startDate) {
            return $this->json(['error' => 'End date must be after start date'], 400);
        }

        $existingReservation = $this->reservationRepository->findConflictingReservation(
            $reservation->getCar(),
            $startDate,
            $endDate,
            $reservation->getId()
        );

        if ($existingReservation) {
            return $this->json(['error' => 'Car is already reserved for these dates'], 409);
        }

        if (isset($data['start_date'])) {
            $reservation->setStartDate($startDate);
        }
        if (isset($data['end_date'])) {
            $reservation->setEndDate($endDate);
        }

        $errors = $this->validator->validate($reservation);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $this->reservationService->update();

        $dto = $this->toDto($reservation);
        return $this->json($dto);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/reservations/{id}',
        tags: ['reservations'],
        summary: 'Annuler la réservation',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la réservation', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Reservation deleted'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Reservation not found')
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $reservation = $this->reservationService->get($id);
        if (!$reservation) {
            return $this->json(['error' => 'Reservation not found'], 404);
        }

        $user = $this->getUser();
        if (!$user || $user !== $reservation->getUser()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $this->reservationService->delete($reservation);

        return $this->json(null, 204);
    }
}
