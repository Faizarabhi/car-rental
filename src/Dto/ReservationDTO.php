<?php
namespace App\Dto;

class ReservationDTO
{
    public ?int $id = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $userId = null;
    public ?int $carId = null;
}
