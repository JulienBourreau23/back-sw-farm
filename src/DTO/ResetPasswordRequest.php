<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordRequest
{
    #[Assert\NotBlank]
    public string $token = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password = '';
}
