<?php
namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserRegisterRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    public ?string $password = null;
}
