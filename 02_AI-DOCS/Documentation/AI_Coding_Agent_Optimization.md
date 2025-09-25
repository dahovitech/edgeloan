# AI Coding Agent Optimization for EdgeLoan

## Core Principles: Agentic Coding Logic

### 1. Excellence Standards
- **Quality Target:** Silicon Valley / Y Combinator standard
- **Code Quality:** Enterprise-level, production-ready
- **Design Quality:** Modern, intuitive, accessible
- **Documentation:** Comprehensive and AI-friendly

### 2. Symfony Framework Mastery
- **Architecture:** Follow Symfony best practices and conventions
- **Security:** Implement proper authentication, authorization, and validation
- **Performance:** Optimize queries, use caching where appropriate
- **Testing:** Comprehensive unit and integration tests

### 3. Multi-Language Excellence
- **Translation Keys:** Use semantic, hierarchical naming
- **Language Support:** FR, EN, DE, ES consistently across all features
- **Fallback Strategy:** Proper fallback to default language
- **Testing:** Validate all languages in automated tests

## Technical Guidelines

### Code Structure
```php
// Example: Proper Symfony Controller
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ExampleController extends AbstractController
{
    #[Route('/example', name: 'app_example')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('example/index.html.twig', [
            'title' => 'example.title'
        ]);
    }
}
```

### Form Implementation
```php
// Example: Multi-language Form with proper validation
use Symfony\Component\Form\AbstractType;
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
                'label' => 'form.name.label',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'form.name.placeholder'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'form.name.not_blank'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'admin'
        ]);
    }
}
```

### Entity Best Practices
```php
// Example: Proper Doctrine Entity with validation
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExampleRepository::class)]
#[ORM\Table(name: 'examples')]
class Example
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Proper getters and setters...
}
```

## UI/UX Standards

### Bootstrap 5 Integration
- Use Bootstrap 5 classes consistently
- Implement responsive design principles
- Ensure accessibility (WCAG AA compliance)
- Maintain consistent spacing and typography

### Twig Template Excellence
```twig
{# Example: Proper Twig template with i18n #}
{% extends 'base.html.twig' %}

{% block title %}{{ 'page.title'|trans }}{% endblock %}

{% block body %}
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">{{ 'section.title'|trans }}</h4>
                </div>
                <div class="card-body">
                    {{ form_start(form, {'attr': {'class': 'needs-validation', 'novalidate': true}}) }}
                        <div class="row">
                            <div class="col-md-6">
                                {{ form_row(form.name, {'attr': {'class': 'form-control'}}) }}
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                {{ 'button.save'|trans }}
                            </button>
                        </div>
                    {{ form_end(form) }}
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

## Security Implementation

### Authentication & Authorization
- Use Symfony Security component properly
- Implement role-based access control
- Validate all user inputs
- Protect against common vulnerabilities (XSS, CSRF, SQL Injection)

### Example Security Configuration
```php
// In Security/Voter
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ExampleVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['EDIT', 'DELETE']) && $subject instanceof Example;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case 'EDIT':
                return $this->canEdit($subject, $user);
            case 'DELETE':
                return $this->canDelete($subject, $user);
        }

        return false;
    }
}
```

## Testing Standards

### Unit Tests
```php
// Example: Comprehensive unit test
use PHPUnit\Framework\TestCase;
use App\Entity\Example;

class ExampleTest extends TestCase
{
    public function testExampleCreation(): void
    {
        $example = new Example();
        $example->setName('Test Name');
        
        $this->assertEquals('Test Name', $example->getName());
        $this->assertInstanceOf(\DateTimeImmutable::class, $example->getCreatedAt());
    }

    public function testValidation(): void
    {
        // Test validation constraints
        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $example = new Example();
        
        $violations = $validator->validate($example);
        $this->assertCount(1, $violations); // Name is required
    }
}
```

### Integration Tests
```php
// Example: Controller integration test
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExampleControllerTest extends WebTestCase
{
    public function testIndexPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/examples');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Examples');
    }
}
```

## Performance Optimization

### Database Queries
- Use proper indexing
- Optimize N+1 query problems
- Implement proper pagination
- Use QueryBuilder for complex queries

### Caching Strategy
- Implement Symfony Cache for expensive operations
- Use HTTP caching headers appropriately
- Cache translations and static content

## Error Handling

### Exception Management
```php
// Example: Proper exception handling
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

public function show(int $id, ExampleRepository $repository): Response
{
    $example = $repository->find($id);
    
    if (!$example) {
        throw new NotFoundHttpException('Example not found');
    }
    
    return $this->render('example/show.html.twig', [
        'example' => $example
    ]);
}
```

## Documentation Standards

### Code Documentation
```php
/**
 * Manages example entities with full CRUD operations
 * 
 * @author AI Agent
 * @since 1.0.0
 */
class ExampleController extends AbstractController
{
    /**
     * Display paginated list of examples
     * 
     * @param Request $request HTTP request object
     * @param ExampleRepository $repository Example repository
     * @return Response Rendered template with examples list
     */
    public function index(Request $request, ExampleRepository $repository): Response
    {
        // Implementation...
    }
}
```

## Translation Management

### Translation Key Structure
```yaml
# admin.en.yaml
example:
  title: "Examples Management"
  create: "Create Example"
  edit: "Edit Example"
  delete: "Delete Example"
  list:
    title: "Examples List"
    empty: "No examples found"
  form:
    name:
      label: "Name"
      placeholder: "Enter example name"
      help: "Provide a descriptive name for the example"
    save: "Save Example"
    cancel: "Cancel"
  messages:
    created: "Example created successfully"
    updated: "Example updated successfully"
    deleted: "Example deleted successfully"
    error: "An error occurred while processing the example"
```

## Deployment Considerations

### Docker Configuration
- Optimize Docker images for production
- Use proper environment variables
- Implement health checks
- Configure proper logging

### Security in Production
- Use HTTPS everywhere
- Implement proper CORS policies
- Configure security headers
- Regular security updates

## Quality Checklist

Before considering any feature complete, verify:

- ✅ Code follows Symfony best practices
- ✅ All strings are translatable with proper keys
- ✅ Forms include proper validation and error handling
- ✅ Templates are responsive and accessible
- ✅ Security measures are properly implemented
- ✅ Unit and integration tests are written
- ✅ Documentation is comprehensive
- ✅ Performance is optimized
- ✅ Multi-language support is functional
- ✅ Error handling is robust

Remember: Every line of code should reflect enterprise-level quality and attention to detail. Excellence is not negotiable.
