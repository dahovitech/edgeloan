<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Form\PasswordChangeType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile', name: 'profile_')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private TranslatorInterface $translator,
        private SluggerInterface $slugger
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $userStats = [
            'member_since' => $user->getCreatedAt()->format('Y'),
            'last_login' => $user->getLastLoginAt() ? $user->getLastLoginAt()->format('d/m/Y H:i') : null,
            'profile_completion' => $this->calculateProfileCompletion($user),
            'active_roles' => $user->getActiveRoles(),
            'total_permissions' => count($user->getAllPermissions()),
        ];

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'stats' => $userStats,
        ]);
    }

    #[Route('/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle profile image upload
                /** @var UploadedFile $profileImageFile */
                $profileImageFile = $form->get('profileImage')->getData();
                
                if ($profileImageFile) {
                    $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $profileImageFile->guessExtension();

                    try {
                        $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/profile_images';
                        
                        if (!is_dir($uploadDirectory)) {
                            mkdir($uploadDirectory, 0755, true);
                        }

                        $profileImageFile->move($uploadDirectory, $newFilename);
                        
                        // Remove old profile image if exists
                        if ($user->getProfileImage()) {
                            $oldImagePath = $uploadDirectory . '/' . $user->getProfileImage();
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        
                        $user->setProfileImage($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', $this->translator->trans('profile.image.upload_failed', [], 'profile'));
                    }
                }

                $this->entityManager->flush();

                $this->addFlash('success', $this->translator->trans('profile.edit.success', [], 'profile'));

                return $this->redirectToRoute('profile_index');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('profile.edit.error', [], 'profile'));
            }
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/change-password', name: 'change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(PasswordChangeType::class);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', $this->translator->trans('profile.password.current_invalid', [], 'profile'));
                return $this->redirectToRoute('profile_change_password');
            }

            try {
                // Hash and set new password
                $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                
                $this->entityManager->flush();

                $this->addFlash('success', $this->translator->trans('profile.password.success', [], 'profile'));

                return $this->redirectToRoute('profile_index');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('profile.password.error', [], 'profile'));
            }
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/preferences', name: 'preferences', methods: ['GET', 'POST'])]
    public function preferences(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($request->isMethod('POST')) {
            $preferences = $request->request->all('preferences');
            
            if ($this->isCsrfTokenValid('update_preferences', $request->request->get('_token'))) {
                try {
                    // Process language preference
                    if (isset($preferences['language']) && in_array($preferences['language'], ['fr', 'en', 'de', 'es'])) {
                        $user->setPreferredLanguage($preferences['language']);
                    }

                    // Process notification preferences
                    if (isset($preferences['notifications'])) {
                        $user->setNotificationPreferences($preferences['notifications']);
                    }

                    // Process privacy settings
                    if (isset($preferences['privacy'])) {
                        $user->setPrivacySettings($preferences['privacy']);
                    }

                    // Process display preferences
                    if (isset($preferences['display'])) {
                        $user->setDisplayPreferences($preferences['display']);
                    }

                    $this->entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('profile.preferences.success', [], 'profile'));

                    return $this->redirectToRoute('profile_preferences');
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans('profile.preferences.error', [], 'profile'));
                }
            }
        }

        return $this->render('profile/preferences.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/security', name: 'security', methods: ['GET'])]
    public function security(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get recent login history (if implemented)
        $recentLogins = []; // This would come from a login history entity/service
        
        // Security score calculation
        $securityScore = $this->calculateSecurityScore($user);

        return $this->render('profile/security.html.twig', [
            'user' => $user,
            'recent_logins' => $recentLogins,
            'security_score' => $securityScore,
        ]);
    }

    #[Route('/delete-image', name: 'delete_image', methods: ['POST'])]
    public function deleteProfileImage(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete_image', $request->request->get('_token'))) {
            try {
                if ($user->getProfileImage()) {
                    $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/profile_images';
                    $imagePath = $uploadDirectory . '/' . $user->getProfileImage();
                    
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    
                    $user->setProfileImage(null);
                    $this->entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('profile.image.deleted', [], 'profile'));
                } else {
                    $this->addFlash('warning', $this->translator->trans('profile.image.none_to_delete', [], 'profile'));
                }
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('profile.image.delete_error', [], 'profile'));
            }
        }

        return $this->redirectToRoute('profile_edit');
    }

    #[Route('/download-data', name: 'download_data', methods: ['GET'])]
    public function downloadData(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Prepare user data for export
        $userData = [
            'personal_information' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'client_type' => $user->getClientType(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
            ],
            'roles' => array_map(fn($role) => [
                'name' => $role->getName(),
                'code' => $role->getCode(),
                'priority' => $role->getPriority(),
            ], $user->getActiveRoles()),
            'permissions' => array_map(fn($permission) => [
                'name' => $permission->getName(),
                'code' => $permission->getCode(),
                'category' => $permission->getCategory(),
            ], $user->getAllPermissions()),
            'financial_information' => [
                'monthly_income' => $user->getMonthlyIncome(),
                'monthly_charges' => $user->getMonthlyCharges(),
                'employment_status' => $user->getEmploymentStatus(),
                'employer' => $user->getEmployer(),
            ],
            'export_date' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $response = new Response(json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="profile_data_' . $user->getId() . '.json"');

        return $response;
    }

    private function calculateProfileCompletion(User $user): int
    {
        $fields = [
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getMonthlyIncome(),
            $user->getEmploymentStatus(),
        ];

        $completedFields = array_filter($fields, fn($field) => !empty($field));
        return round((count($completedFields) / count($fields)) * 100);
    }

    private function calculateSecurityScore(User $user): array
    {
        $score = 0;
        $maxScore = 100;
        $recommendations = [];

        // Password age (assuming we track this)
        $score += 20;

        // Two-factor authentication (if implemented)
        if ($user->isTwoFactorEnabled() ?? false) {
            $score += 25;
        } else {
            $recommendations[] = 'profile.security.recommendations.enable_2fa';
        }

        // Profile completeness
        $completeness = $this->calculateProfileCompletion($user);
        $score += round($completeness * 0.3);

        // Active sessions (if tracked)
        $score += 15;

        // Recent password change
        $score += 10;

        // Email verification
        if ($user->isAccountVerified()) {
            $score += 10;
        } else {
            $recommendations[] = 'profile.security.recommendations.verify_email';
        }

        return [
            'score' => min($score, $maxScore),
            'level' => $this->getSecurityLevel($score),
            'recommendations' => $recommendations,
        ];
    }

    private function getSecurityLevel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'fair',
            default => 'poor'
        };
    }
}