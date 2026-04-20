<?php
namespace App\Controller;
use App\Repository\UserRepository;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/profile', name: 'profile_')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Profile')]
class ProfileController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
    ) {}
    #[Route('', name: 'get', methods: ['GET'])]
    #[OA\Get(summary: 'Récupérer son profil')]
    public function get(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json([
            'id'         => $user->getId(),
            'username'   => $user->getUsername(),
            'email'      => $user->getEmail(),
            'role'       => $user->getRole(),
            'created_at' => $user->getCreatedAt()->format('c'),
        ]);
    }
    #[Route('', name: 'update', methods: ['PUT'])]
    #[OA\Put(summary: 'Modifier son profil')]
    public function update(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }
        if (!empty($data['new_password'])) {
            $currentPassword = $data['current_password'] ?? '';
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                return $this->json(['message' => 'Mot de passe actuel incorrect.'], Response::HTTP_BAD_REQUEST);
            }
            if (strlen($data['new_password']) < 8) {
                return $this->json(['message' => 'Le nouveau mot de passe doit faire au moins 8 caractères.'], Response::HTTP_BAD_REQUEST);
            }
        }
        if (!empty($data['email']) && $data['email'] !== $user->getEmail()) {
            if ($this->userRepository->findOneBy(['email' => $data['email']])) {
                return $this->json(['message' => 'Cet email est déjà utilisé.'], Response::HTTP_CONFLICT);
            }
        }
        if (!empty($data['username']) && $data['username'] !== $user->getUsername()) {
            if ($this->userRepository->findOneBy(['username' => $data['username']])) {
                return $this->json(['message' => 'Ce nom d\'utilisateur est déjà utilisé.'], Response::HTTP_CONFLICT);
            }
            if (strlen($data['username']) < 3) {
                return $this->json(['message' => 'Le nom d\'utilisateur doit faire au moins 3 caractères.'], Response::HTTP_BAD_REQUEST);
            }
        }
        if (!empty($data['username'])) {
            $user->setUsername($data['username']);
        }
        if (!empty($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (!empty($data['new_password'])) {
            $hashed = $this->passwordHasher->hashPassword($user, $data['new_password']);
            $user->setPasswordHash($hashed);
        }
        $this->userRepository->save($user, true);
        // Réémettre un nouveau JWT avec les données à jour
        $newToken = $this->jwtManager->create($user);
        return $this->json([
            'token' => $newToken,
            'user'  => [
                'id'       => $user->getId(),
                'username' => $user->getUsername(),
                'email'    => $user->getEmail(),
                'role'     => $user->getRole(),
            ],
        ]);
    }
}
