<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private string $frontendUrl,
        private string $mailerFrom,
    ) {}

    public function login(string $email, string $password): ?array
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$user->isActive()) {
            return null;
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        // Mise à jour last_login
        $user->setLastLoginAt(new \DateTimeImmutable());
        $this->em->flush();

        $token = $this->jwtManager->create($user);
        $refreshToken = $this->generateRefreshToken($user);

        return [
            'token'         => $token,
            'refresh_token' => $refreshToken,
            'user'          => [
                'id'       => $user->getId(),
                'username' => $user->getUsername(),
                'email'    => $user->getEmail(),
                'role'     => $user->getRole(),
            ],
        ];
    }

    public function refresh(string $refreshToken): ?array
    {
        $user = $this->userRepository->findOneBy(['resetPasswordToken' => null]);
        // Note: en prod, stocker les refresh tokens dans une table dédiée
        // Pour simplifier ici on utilise un champ dédié ou une table refresh_tokens
        // Implémentation simplifiée - à enrichir avec une vraie table refresh_tokens
        return null;
    }

    public function logout(string $refreshToken): void
    {
        // Invalider le refresh token en base
        // À implémenter avec une table refresh_tokens
    }

    public function sendResetPasswordEmail(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return; // Silencieux pour la sécurité
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user->setResetPasswordToken($token);
        $user->setResetPasswordTokenExpiresAt($expiresAt);
        $this->em->flush();

        $resetUrl = "{$this->frontendUrl}/reset-password?token={$token}";

        $mail = (new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe - SW Farm')
            ->html("
                <p>Bonjour {$user->getUsername()},</p>
                <p>Cliquez sur le lien suivant pour réinitialiser votre mot de passe :</p>
                <p><a href='{$resetUrl}'>{$resetUrl}</a></p>
                <p>Ce lien expire dans 1 heure.</p>
                <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
            ");

        $this->mailer->send($mail);
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->userRepository->findOneBy(['resetPasswordToken' => $token]);

        if (!$user) {
            return false;
        }

        if ($user->getResetPasswordTokenExpiresAt() < new \DateTimeImmutable()) {
            return false;
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPasswordHash($hashedPassword);
        $user->setResetPasswordToken(null);
        $user->setResetPasswordTokenExpiresAt(null);
        $this->em->flush();

        return true;
    }

    private function generateRefreshToken(User $user): string
    {
        // Simplifié - à remplacer par une table refresh_tokens dédiée
        return base64_encode($user->getId() . ':' . bin2hex(random_bytes(32)));
    }
}
