<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer le premier compte administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création du compte administrateur');

        $username = $io->ask('Nom d\'utilisateur');
        $email    = $io->ask('Email');
        $password = $io->askHidden('Mot de passe');

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRole('ROLE_ADMIN');

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPasswordHash($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        $io->success("Administrateur '{$username}' créé avec succès (ID: {$user->getId()})");

        return Command::SUCCESS;
    }
}
