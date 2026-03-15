<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActivityLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ActivityLoggerService $activityLogger
    ) {}

    #[Route('/', name: 'app_users_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $searchQuery = $request->query->get('q');
        $roleFilter = $request->query->get('role');
        $statusFilter = $request->query->get('status');
        
        if ($searchQuery) {
            $users = $this->userRepository->searchUsers($searchQuery);
        } elseif ($roleFilter) {
            $users = $this->userRepository->findByRole($roleFilter);
        } elseif ($statusFilter) {
            $users = $this->userRepository->findByStatus($statusFilter);
        } else {
            $users = $this->userRepository->findAll();
        }

        $statistics = $this->userRepository->getUserStatistics();

        return $this->render('main/users.html.twig', [
            'users' => $users,
            'statistics' => $statistics,
            'searchQuery' => $searchQuery,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter
        ]);
    }

    #[Route('/search', name: 'app_users_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $searchQuery = $request->query->get('q');
        
        if (!$searchQuery) {
            return $this->redirectToRoute('app_users_index');
        }

        $users = $this->userRepository->searchUsers($searchQuery);
        $statistics = $this->userRepository->getUserStatistics();

        return $this->render('main/users.html.twig', [
            'users' => $users,
            'statistics' => $statistics,
            'searchQuery' => $searchQuery
        ]);
    }

    #[Route('/new', name: 'app_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['include_password' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
            
            $user->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Log the activity
            $this->activityLogger->logCreate(
                'User',
                $user->getId(),
                "Created user: {$user->getFullName()} ({$user->getUsername()})",
                [
                    'username' => ['after' => $user->getUsername()],
                    'email' => ['after' => $user->getEmail()],
                    'firstName' => ['after' => $user->getFirstName()],
                    'lastName' => ['after' => $user->getLastName()]
                ]
            );

            $this->addFlash('success', 'User created successfully!');
            return $this->redirectToRoute('app_users_show', ['id' => $user->getId()]);
        }

        return $this->render('main/user_form.html.twig', [
            'user' => $user,
            'form' => $form,
            'title' => 'Add New User'
        ]);
    }

    #[Route('/{id}', name: 'app_users_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('main/user_details.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/{id}/edit', name: 'app_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        // Check authorization: staff can only edit their own records
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to edit this user.');
            return $this->redirectToRoute('app_users_show', ['id' => $user->getId()]);
        }
        
        // Check authorization: staff cannot edit admin records
        if (!$this->isGranted('ROLE_ADMIN') && in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'You do not have permission to edit administrator records.');
            return $this->redirectToRoute('app_users_index');
        }
        
        // Store original values for comparison
        $originalData = [
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'phone' => $user->getPhone(),
            'status' => $user->getStatus()
        ];

        $form = $this->createForm(UserType::class, $user, ['include_password' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            // Log the activity with changes
            $changes = [];
            $newData = [
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'status' => $user->getStatus()
            ];

            foreach ($originalData as $field => $oldValue) {
                if ($oldValue !== $newData[$field]) {
                    $changes[$field] = [
                        'before' => $oldValue,
                        'after' => $newData[$field]
                    ];
                }
            }

            if (!empty($changes)) {
                $this->activityLogger->logUpdate(
                    'User',
                    $user->getId(),
                    "Updated user: {$user->getFullName()} ({$user->getUsername()})",
                    $changes
                );
            }

            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('app_users_show', ['id' => $user->getId()]);
        }

        return $this->render('main/user_form.html.twig', [
            'user' => $user,
            'form' => $form,
            'title' => 'Edit User'
        ]);
    }

    #[Route('/{id}/change-password', name: 'app_users_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, User $user): Response
    {
        // Check authorization: users can only change their own password
        if ($this->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to change this password.');
            return $this->redirectToRoute('app_users_show', ['id' => $user->getId()]);
        }
        
        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');
            
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match!');
            } elseif (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters long!');
            } else {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $user->setUpdatedAt(new \DateTime());
                
                $this->entityManager->flush();
                $this->addFlash('success', 'Password changed successfully!');
                
                return $this->redirectToRoute('app_users_show', ['id' => $user->getId()]);
            }
        }

        return $this->render('main/user_change_password.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/{id}', name: 'app_users_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        // Check authorization: only admins can delete, OR staff can delete their own record
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to delete this user.');
            return $this->redirectToRoute('app_users_show', ['id' => $user->getId()]);
        }
        
        // Check authorization: staff cannot delete admin records
        if (!$this->isGranted('ROLE_ADMIN') && in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'You do not have permission to delete administrator records.');
            return $this->redirectToRoute('app_users_index');
        }
        
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $userId = $user->getId();
            $userName = $user->getFullName();
            $userUsername = $user->getUsername();

            // Log before deletion
            $this->activityLogger->logDelete(
                'User',
                $userId,
                "Deleted user: {$userName} ({$userUsername})"
            );

            $this->entityManager->remove($user);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'User deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid request token.');
        }

        return $this->redirectToRoute('app_users_index');
    }

    #[Route('/{id}/toggle-status', name: 'app_users_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
            $oldStatus = $user->getStatus();
            $newStatus = $oldStatus === 'active' ? 'inactive' : 'active';
            $user->setStatus($newStatus);
            $user->setUpdatedAt(new \DateTime());
            
            $this->entityManager->flush();

            // Log the status change
            $this->activityLogger->logUpdate(
                'User',
                $user->getId(),
                "Changed user status: {$user->getFullName()} ({$oldStatus} → {$newStatus})",
                [
                    'status' => [
                        'before' => $oldStatus,
                        'after' => $newStatus
                    ]
                ]
            );
            
            $this->addFlash('success', 'User status updated successfully!');
        }

        return $this->redirectToRoute('app_users_show', ['id' => $user->getId()]);
    }

    #[Route('/api/statistics', name: 'app_users_api_statistics', methods: ['GET'])]
    public function apiStatistics(): JsonResponse
    {
        $statistics = $this->userRepository->getUserStatistics();
        return $this->json($statistics);
    }

    #[Route('/api/mechanics', name: 'app_users_api_mechanics', methods: ['GET'])]
    public function apiMechanics(): JsonResponse
    {
        $mechanics = $this->userRepository->findMechanics();
        
        $data = array_map(function(User $user) {
            return [
                'id' => $user->getId(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
                'status' => $user->getStatus()
            ];
        }, $mechanics);

        return $this->json($data);
    }
}
