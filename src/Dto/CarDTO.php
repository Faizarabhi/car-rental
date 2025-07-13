<?php
namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Car',
    required: ['name', 'pricePerDay'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, description: 'Unique identifier'),
        new OA\Property(property: 'brand', type: 'string', nullable: true, example: 'Toyota', description: 'Brand of the car'),
        new OA\Property(property: 'name', type: 'string', example: 'Corolla', description: 'Model name'),
        new OA\Property(property: 'pricePerDay', type: 'number', format: 'float', example: 49.99, description: 'Price per day rental')
    ]
)]


class CarDTO
{
    public ?int $id = null;
    public ?string $brand = null;
    public ?string $name = null;
    public ?float $pricePerDay = null;
}

