<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserRequest
{
    #[Assert\Length(min: 3, max: 50)]
    public ?string $username = null;

    #[Assert\Email]
    public ?string $email = null;

    #[Assert\Length(min: 8)]
    public ?string $password = null;

    #[Assert\Choice(choices: ['ROLE_USER', 'ROLE_ADMIN'])]
    public ?string $role = null;

    public ?bool $isActive = null;
}
