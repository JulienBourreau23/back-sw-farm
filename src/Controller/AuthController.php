<?php

namespace App\Controller;

use App\DTO\LoginRequest;
use App\DTO\ForgotPasswordRequest;
use App\DTO\ResetPasswordRequest;
use App\Repository\UserRepository;
use App\Service\AuthService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/auth', name: 'auth_')]
#[OA\Tag(name: 'Auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private UserRepository $userRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        summary: 'Connexion utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: LoginRequest::class))
        ),
        responses: [
            new OA\Response(response: 200, description: 'JWT + refresh token'),
            new OA\Response(response: 401, description: 'Identifiants invalides'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), LoginRequest::class, 'json');
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->authService->login($dto->email, $dto->password);

        if (!$result) {
            return $this->json(['message' => 'Identifiants invalides ou compte inactif.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($result);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    #[OA\Post(summary: 'Renouveler le JWT via refresh token')]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {
            return $this->json(['message' => 'Refresh token manquant.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->authService->refresh($refreshToken);

        if (!$result) {
            return $this->json(['message' => 'Refresh token invalide ou expiré.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($result);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[OA\Post(summary: 'Déconnexion - invalide le refresh token')]
    public function logout(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refresh_token'] ?? null;

        if ($refreshToken) {
            $this->authService->logout($refreshToken);
        }

        return $this->json(['message' => 'Déconnecté.']);
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    #[OA\Post(summary: 'Demande de réinitialisation de mot de passe')]
    public function forgotPassword(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), ForgotPasswordRequest::class, 'json');
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // Toujours retourner 200 même si email inconnu (sécurité)
        $this->authService->sendResetPasswordEmail($dto->email);

        return $this->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    #[OA\Post(summary: 'Réinitialiser le mot de passe via token email')]
    public function resetPassword(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), ResetPasswordRequest::class, 'json');
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $success = $this->authService->resetPassword($dto->token, $dto->password);

        if (!$success) {
            return $this->json(['message' => 'Token invalide ou expiré.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => 'Mot de passe modifié avec succès.']);
    }
}
