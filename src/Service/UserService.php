<?php

namespace App\Service;

use App\DTO\CreateUserRequest;
use App\DTO\UpdateUserRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function createUser(CreateUserRequest $dto, ?User $createdBy = null): User
    {
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            throw new \InvalidArgumentException('Cet email est déjà utilisé.');
        }

        if ($this->userRepository->findOneBy(['username' => $dto->username])) {
            throw new \InvalidArgumentException('Ce nom d\'utilisateur est déjà utilisé.');
        }

        $user = new User();
        $user->setUsername($dto->username);
        $user->setEmail($dto->email);
        $user->setRole($dto->role ?? 'ROLE_USER');
        $user->setCreatedBy($createdBy);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPasswordHash($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function updateUser(User $user, UpdateUserRequest $dto): User
    {
        if ($dto->username !== null) {
            $user->setUsername($dto->username);
        }

        if ($dto->email !== null) {
            $user->setEmail($dto->email);
        }

        if ($dto->role !== null) {
            $user->setRole($dto->role);
        }

        if ($dto->isActive !== null) {
            $user->setIsActive($dto->isActive);
        }

        if ($dto->password !== null) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
            $user->setPasswordHash($hashedPassword);
        }

        $this->em->flush();

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }
}
