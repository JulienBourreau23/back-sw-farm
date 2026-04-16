<?php

namespace App\Controller;

use App\DTO\CreateUserRequest;
use App\DTO\UpdateUserRequest;
use App\Repository\UserRepository;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/users', name: 'admin_users_')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Admin - Users')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private UserRepository $userRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(summary: 'Lister tous les utilisateurs')]
    public function list(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        return $this->json(array_map(fn($u) => [
            'id'           => $u->getId(),
            'username'     => $u->getUsername(),
            'email'        => $u->getEmail(),
            'role'         => $u->getRole(),
            'is_active'    => $u->isActive(),
            'created_at'   => $u->getCreatedAt()->format('c'),
            'last_login_at'=> $u->getLastLoginAt()?->format('c'),
        ], $users));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer un utilisateur (admin only)')]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), CreateUserRequest::class, 'json');
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->createUser($dto, $this->getUser());

        return $this->json([
            'id'       => $user->getId(),
            'username' => $user->getUsername(),
            'email'    => $user->getEmail(),
            'role'     => $user->getRole(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[OA\Put(summary: 'Modifier un utilisateur')]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $dto = $this->serializer->deserialize($request->getContent(), UpdateUserRequest::class, 'json');
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->updateUser($user, $dto);

        return $this->json([
            'id'        => $user->getId(),
            'username'  => $user->getUsername(),
            'email'     => $user->getEmail(),
            'role'      => $user->getRole(),
            'is_active' => $user->isActive(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer un utilisateur')]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        // Empêcher l'admin de se supprimer lui-même
        if ($user->getId() === $this->getUser()->getId()) {
            return $this->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], Response::HTTP_FORBIDDEN);
        }

        $this->userService->deleteUser($user);

        return $this->json(['message' => 'Utilisateur supprimé.']);
    }
}
