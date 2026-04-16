<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    public string $username = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password = '';

    #[Assert\Choice(choices: ['ROLE_USER', 'ROLE_ADMIN'])]
    public ?string $role = 'ROLE_USER';
}
