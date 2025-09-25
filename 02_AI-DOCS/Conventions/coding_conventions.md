# Coding Conventions for EdgeLoan

## PHP and Symfony Standards

### Code Style
- Follow PSR-12 coding standard strictly
- Use PHP 8+ features (typed properties, constructor promotion, etc.)
- Always use strict types declaration: `<?php declare(strict_types=1);`
- Prefer explicit over implicit (clear variable names, explicit types)

### Symfony Best Practices

#### Controllers
```php
<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Example;
use App\Form\ExampleType;
use App\Repository\ExampleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/examples', name: 'admin_example_')]
#[IsGranted('ROLE_ADMIN')]
class ExampleController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ExampleRepository $repository): Response
    {
        $examples = $repository->findAllPaginated();
        
        return $this->render('admin/example/index.html.twig', [
            'examples' => $examples,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $example = new Example();
        $form = $this->createForm(ExampleType::class, $example);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($example);
            $entityManager->flush();

            $this->addFlash('success', 'example.created_successfully');

            return $this->redirectToRoute('admin_example_index');
        }

        return $this->render('admin/example/new.html.twig', [
            'example' => $example,
            'form' => $form,
        ]);
    }
}
```

#### Entities
```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

#[ORM\Entity(repositoryClass: ExampleRepository::class)]
#[ORM\Table(name: 'examples')]
#[ORM\Index(columns: ['status'], name: 'idx_example_status')]
#[ORM\Index(columns: ['created_at'], name: 'idx_example_created')]
class Example
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?UuidV4 $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'example.name.not_blank')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'example.name.too_long'
    )]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'example.description.too_long'
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Choice(
        choices: self::VALID_STATUSES,
        message: 'example.status.invalid'
    )]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?UuidV4
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        $this->updateTimestamp();
        
        return $this;
    }

    // ... other getters and setters

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function publish(): static
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->updateTimestamp();
        
        return $this;
    }

    private function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
```

#### Forms
```php
<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Example;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ExampleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'example.form.name.label',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'example.form.name.placeholder',
                    'maxlength' => 255,
                ],
                'help' => 'example.form.name.help',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'example.form.name.not_blank'
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'example.form.name.too_long'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'example.form.description.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'example.form.description.placeholder',
                ],
                'help' => 'example.form.description.help',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'example.form.status.label',
                'choices' => [
                    'example.status.draft' => Example::STATUS_DRAFT,
                    'example.status.published' => Example::STATUS_PUBLISHED,
                    'example.status.archived' => Example::STATUS_ARCHIVED,
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'placeholder' => 'example.form.status.placeholder',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Example::class,
            'translation_domain' => 'admin',
            'attr' => [
                'class' => 'needs-validation',
                'novalidate' => true,
            ],
        ]);
    }
}
```

#### Repositories
```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Example;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\UuidV4;

/**
 * Repository for Example entity operations
 * 
 * @extends ServiceEntityRepository<Example>
 */
class ExampleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Example::class);
    }

    /**
     * Find all examples with optional filtering and pagination
     */
    public function findAllPaginated(
        ?string $search = null,
        ?string $status = null,
        int $page = 1,
        int $limit = 20
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('e.name LIKE :search OR e.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->setFirstResult(($page - 1) * $limit)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find published examples only
     * 
     * @return Example[]
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', Example::STATUS_PUBLISHED)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e.status, COUNT(e.id) as count')
            ->groupBy('e.status');

        $results = $qb->getQuery()->getResult();
        
        $stats = [
            Example::STATUS_DRAFT => 0,
            Example::STATUS_PUBLISHED => 0,
            Example::STATUS_ARCHIVED => 0,
        ];

        foreach ($results as $result) {
            $stats[$result['status']] = (int)$result['count'];
        }

        return $stats;
    }
}
```

#### Services
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Example;
use App\Repository\ExampleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExampleService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ExampleRepository $repository,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ) {}

    /**
     * Create a new example with validation
     */
    public function createExample(array $data): Example
    {
        $example = new Example();
        $example->setName($data['name']);
        
        if (isset($data['description'])) {
            $example->setDescription($data['description']);
        }
        
        if (isset($data['status'])) {
            $example->setStatus($data['status']);
        }

        $this->entityManager->persist($example);
        $this->entityManager->flush();

        $this->logger->info('Example created', [
            'example_id' => $example->getId()->toString(),
            'name' => $example->getName()
        ]);

        return $example;
    }

    /**
     * Update existing example
     */
    public function updateExample(Example $example, array $data): Example
    {
        if (isset($data['name'])) {
            $example->setName($data['name']);
        }
        
        if (isset($data['description'])) {
            $example->setDescription($data['description']);
        }
        
        if (isset($data['status'])) {
            $example->setStatus($data['status']);
        }

        $this->entityManager->flush();

        $this->logger->info('Example updated', [
            'example_id' => $example->getId()->toString()
        ]);

        return $example;
    }

    /**
     * Soft delete example (archive)
     */
    public function archiveExample(Example $example): void
    {
        $example->setStatus(Example::STATUS_ARCHIVED);
        $this->entityManager->flush();

        $this->logger->info('Example archived', [
            'example_id' => $example->getId()->toString()
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardData(): array
    {
        $stats = $this->repository->getStatistics();
        $recentExamples = $this->repository->findBy(
            [], 
            ['createdAt' => 'DESC'], 
            5
        );

        return [
            'statistics' => $stats,
            'recent_examples' => $recentExamples,
            'total_count' => array_sum($stats)
        ];
    }
}
```

### Testing Standards

#### Unit Tests
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Example;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ExampleTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->addDefaultDoctrineAnnotationReader()
            ->getValidator();
    }

    public function testExampleCreation(): void
    {
        $example = new Example();
        $example->setName('Test Example');
        $example->setDescription('Test Description');
        
        $this->assertEquals('Test Example', $example->getName());
        $this->assertEquals('Test Description', $example->getDescription());
        $this->assertEquals(Example::STATUS_DRAFT, $example->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $example->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $example->getUpdatedAt());
    }

    public function testValidation(): void
    {
        $example = new Example();
        
        $violations = $this->validator->validate($example);
        $this->assertCount(1, $violations); // Name is required
        
        $example->setName('Valid Name');
        $violations = $this->validator->validate($example);
        $this->assertCount(0, $violations);
    }

    public function testNameTooLong(): void
    {
        $example = new Example();
        $example->setName(str_repeat('a', 256)); // Exceeds 255 limit
        
        $violations = $this->validator->validate($example);
        $this->assertCount(1, $violations);
        $this->assertEquals('example.name.too_long', $violations[0]->getMessage());
    }

    public function testStatusMethods(): void
    {
        $example = new Example();
        $example->setName('Test');
        
        $this->assertFalse($example->isPublished());
        
        $example->publish();
        $this->assertTrue($example->isPublished());
        $this->assertEquals(Example::STATUS_PUBLISHED, $example->getStatus());
    }
}
```

#### Integration Tests
```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Entity\Example;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ExampleControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/examples');
        
        $this->assertResponseRedirects('/login');
    }

    public function testIndexPageForAdmin(): void
    {
        $client = static::createClient();
        
        // Create and login admin user
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword('hashed_password');
        
        $client->loginUser($user);
        
        $crawler = $client->request('GET', '/admin/examples');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Examples');
        $this->assertSelectorExists('.btn-primary'); // Add button
    }

    public function testCreateExample(): void
    {
        $client = static::createClient();
        
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setRoles(['ROLE_ADMIN']);
        $client->loginUser($user);
        
        $crawler = $client->request('GET', '/admin/examples/new');
        $this->assertResponseIsSuccessful();
        
        $form = $crawler->selectButton('Save')->form([
            'example[name]' => 'Test Example',
            'example[description]' => 'Test Description',
            'example[status]' => Example::STATUS_DRAFT
        ]);
        
        $client->submit($form);
        
        $this->assertResponseRedirects('/admin/examples');
        
        // Follow redirect and check flash message
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'created successfully');
    }
}
```

## JavaScript Standards

### Modern ES6+ Code
```javascript
// assets/js/admin/example-manager.js

class ExampleManager {
    constructor() {
        this.initializeEventListeners();
        this.initializeTooltips();
    }

    initializeEventListeners() {
        // Bulk actions
        document.getElementById('selectAll')?.addEventListener('change', (e) => {
            this.toggleAllCheckboxes(e.target.checked);
        });

        // Status change confirmation
        document.querySelectorAll('[data-action="archive"]').forEach(button => {
            button.addEventListener('click', (e) => this.handleArchiveClick(e));
        });

        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        });
    }

    initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    toggleAllCheckboxes(checked) {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][data-bulk-item]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        this.updateBulkActionsVisibility();
    }

    async handleArchiveClick(event) {
        event.preventDefault();
        
        const button = event.currentTarget;
        const exampleId = button.dataset.exampleId;
        const exampleName = button.dataset.exampleName;
        
        const confirmed = await this.showConfirmDialog(
            'Confirm Archive',
            `Are you sure you want to archive "${exampleName}"?`
        );
        
        if (confirmed) {
            try {
                await this.archiveExample(exampleId);
                this.showSuccessMessage('Example archived successfully');
                button.closest('tr').remove();
            } catch (error) {
                this.showErrorMessage('Failed to archive example');
                console.error('Archive error:', error);
            }
        }
    }

    async archiveExample(exampleId) {
        const response = await fetch(`/admin/examples/${exampleId}/archive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    handleFormSubmit(event) {
        const form = event.target;
        
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }

    showConfirmDialog(title, message) {
        return new Promise((resolve) => {
            // Using Bootstrap modal for confirmation
            const modalHtml = `
                <div class="modal fade" id="confirmModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>${message}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmAction">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            
            document.getElementById('confirmAction').addEventListener('click', () => {
                resolve(true);
                modal.hide();
            });
            
            document.getElementById('confirmModal').addEventListener('hidden.bs.modal', () => {
                document.getElementById('confirmModal').remove();
                resolve(false);
            });
            
            modal.show();
        });
    }

    showSuccessMessage(message) {
        this.showToast(message, 'success');
    }

    showErrorMessage(message) {
        this.showToast(message, 'danger');
    }

    showToast(message, type = 'info') {
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        const toastContainer = document.querySelector('.toast-container') || this.createToastContainer();
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }
}

// Initialize on DOM content loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-page="examples"]')) {
        new ExampleManager();
    }
});
```

## CSS/SCSS Standards

### Responsive Design
```scss
// assets/styles/admin/_examples.scss

.examples-manager {
  .table-responsive {
    @include media-breakpoint-down(md) {
      font-size: 0.875rem;
      
      .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
      }
    }
  }

  .card {
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.15s ease-in-out;

    &:hover {
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
  }

  .status-badge {
    &.status-draft {
      background-color: var(--bs-secondary);
    }
    
    &.status-published {
      background-color: var(--bs-success);
    }
    
    &.status-archived {
      background-color: var(--bs-warning);
    }
  }

  .bulk-actions {
    display: none;
    
    &.show {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.75rem;
      background-color: var(--bs-light);
      border-radius: 0.375rem;
      margin-bottom: 1rem;
    }
  }
}

// Accessibility improvements
.btn:focus,
.form-control:focus,
.form-select:focus {
  outline: 2px solid var(--bs-primary);
  outline-offset: 2px;
}

// Loading states
.loading {
  position: relative;
  pointer-events: none;
  
  &::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 1rem;
    height: 1rem;
    margin: -0.5rem 0 0 -0.5rem;
    border: 2px solid transparent;
    border-top-color: var(--bs-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
```

## Quality Standards

### Code Review Checklist
- ✅ PSR-12 compliance verified
- ✅ Proper type hints and return types
- ✅ Error handling implemented
- ✅ Security considerations addressed
- ✅ Performance implications assessed
- ✅ Multi-language support included
- ✅ Accessibility requirements met
- ✅ Tests written and passing
- ✅ Documentation updated
- ✅ No hardcoded strings (use translation keys)

### Performance Guidelines
- Use database indexes for frequently queried columns
- Implement pagination for large datasets
- Optimize N+1 query problems with proper joins
- Use Symfony's cache system for expensive operations
- Minimize JavaScript bundle size with tree shaking
- Optimize images and use appropriate formats (WebP when possible)

### Security Requirements
- Always validate user input
- Use parameterized queries (Doctrine handles this)
- Implement CSRF protection on forms
- Sanitize output in templates
- Follow principle of least privilege for user roles
- Use HTTPS in production
- Implement rate limiting for API endpoints
- Regular security audits of dependencies

These coding conventions ensure consistent, maintainable, and secure code across the EdgeLoan project while maintaining the high quality standards expected in enterprise applications.
