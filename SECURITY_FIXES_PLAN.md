# 🛠️ CORRECTIONS CRITIQUES - Plan d'Actions Prioritaires
## EdgeLoan Security & Quality Fixes

---

## 🚨 1. CORRECTIONS SÉCURITÉ XSS - IMMÉDIAT

### Template: loan/dashboard.html.twig
**Ligne 15 - Vulnérabilité XSS:**

```twig
<!-- AVANT (VULNÉRABLE) -->
<p class="text-muted">{{ 'loans.dashboard_welcome'|trans }} {{ user.firstName ?? user.email }}, {{ 'loans.manage_easily'|trans }}</p>

<!-- APRÈS (SÉCURISÉ) -->
<p class="text-muted">{{ 'loans.dashboard_welcome'|trans }} {{ (user.firstName ?? user.email)|e }}, {{ 'loans.manage_easily'|trans }}</p>
```

### Template: admin/base.html.twig
**Ligne 519 - Vulnérabilité XSS:**

```twig
<!-- AVANT (VULNÉRABLE) -->
<i class="fas fa-user me-1"></i>{{ app.user.firstName ?? app.user.email }}

<!-- APRÈS (SÉCURISÉ) -->
<i class="fas fa-user me-1"></i>{{ (app.user.firstName ?? app.user.email)|e }}
```

### Configuration Globale Twig
**Fichier: config/packages/twig.yaml**

```yaml
twig:
    file_name_pattern: '*.twig'
    form_themes: ['bootstrap_5_layout.html.twig']
    paths:
        '%kernel.project_dir%/templates/admin': admin
    # AJOUTS SÉCURITAIRES
    autoescape: 'html'          # Force l'échappement automatique
    strict_variables: true       # Variables strictes en production aussi

when@test:
    twig:
        strict_variables: true
```

---

## 🔧 2. VALIDATION YAML AUTOMATISÉE

### Test Unitaire pour YAML
**Nouveau fichier: tests/Translations/YamlValidationTest.php**

```php
<?php

namespace App\Tests\Translations;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlValidationTest extends KernelTestCase
{
    public function testYamlTranslationFilesAreValid(): void
    {
        $translationsDir = self::getContainer()->getParameter('kernel.project_dir') . '/translations';
        $yamlFiles = glob($translationsDir . '/*.yaml');
        
        $this->assertGreaterThan(0, count($yamlFiles), 'Aucun fichier YAML trouvé');
        
        foreach ($yamlFiles as $file) {
            try {
                $content = Yaml::parseFile($file);
                $this->assertIsArray($content, "Le fichier {$file} ne contient pas de structure YAML valide");
                
                // Vérifier les clés dupliquées
                $this->assertNoDuplicateKeys($file);
                
            } catch (ParseException $e) {
                $this->fail("Erreur YAML dans {$file}: " . $e->getMessage());
            }
        }
    }
    
    private function assertNoDuplicateKeys(string $file): void
    {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $keys = [];
        
        foreach ($lines as $lineNum => $line) {
            if (preg_match('/^(\s*)([a-zA-Z_][a-zA-Z0-9_]*):/', $line, $matches)) {
                $key = $matches[2];
                if (isset($keys[$key])) {
                    $this->fail("Clé dupliquée '{$key}' trouvée ligne " . ($lineNum + 1) . " dans {$file}");
                }
                $keys[$key] = $lineNum + 1;
            }
        }
    }
}
```

---

## 🚀 3. OPTIMISATIONS PERFORMANCE

### Configuration Cache Traductions
**Fichier: config/packages/translation.yaml**

```yaml
framework:
    default_locale: fr
    translator:
        default_path: '%kernel.project_dir%/translations'
        fallbacks: ['fr', 'en']
        providers:
        # AJOUTS PERFORMANCE
        cache_vary:
            - 'locale'
        logging: false              # Désactiver logs en production

when@prod:
    framework:
        translator:
            cache_dir: '%kernel.cache_dir%/translations'
```

### Optimisation Requêtes N+1
**Correction dans LoanApplicationRepository.php:**

```php
public function findApplicationsWithUsers(?int $limit = null): array
{
    $qb = $this->createQueryBuilder('la')
        ->leftJoin('la.customer', 'u')
        ->addSelect('u')  // Évite les requêtes N+1
        ->orderBy('la.createdAt', 'DESC');
    
    if ($limit) {
        $qb->setMaxResults($limit);
    }
    
    return $qb->getQuery()->getResult();
}
```

---

## 🛡️ 4. SÉCURITÉ AVANCÉE

### Extension Twig de Sécurité
**Nouveau fichier: src/Twig/SecurityExtension.php**

```php
<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SecurityExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('safe_user_data', [$this, 'sanitizeUserData']),
            new TwigFilter('currency_format', [$this, 'formatCurrency']),
        ];
    }
    
    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_access_loan', [$this, 'canAccessLoan']),
        ];
    }
    
    public function sanitizeUserData(string $data): string
    {
        // Échappement sécurisé avec validation
        return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public function formatCurrency(float $amount, string $currency = 'XOF'): string
    {
        // Formattage sécurisé des montants
        return number_format($amount, 0, ',', ' ') . ' ' . $currency;
    }
    
    public function canAccessLoan($user, $loan): bool
    {
        // Vérification des permissions
        return $user->getId() === $loan->getCustomer()->getId() || 
               in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
```

### Service de Validation des Permissions
**Nouveau fichier: src/Security/LoanAccessVoter.php**

```php
<?php

namespace App\Security;

use App\Entity\LoanApplication;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LoanAccessVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof LoanApplication;
    }
    
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof User) {
            return false;
        }
        
        /** @var LoanApplication $loan */
        $loan = $subject;
        
        // Admin peut tout voir
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }
        
        // Utilisateur ne peut voir que ses propres prêts
        return $loan->getCustomer()->getId() === $user->getId();
    }
}
```

---

## 📋 5. TEMPLATE SÉCURISÉ - EXEMPLE

### Template Corrigé: loan/dashboard.html.twig (extrait)

```twig
{% extends 'frontend/base.html.twig' %}

{% block body %}
<div class="container my-5 pt-5">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="fw-bold mb-2">{{ 'loans.my_loans'|trans }}</h1>
                    <p class="text-muted">
                        {{ 'loans.dashboard_welcome'|trans }} 
                        {{ (app.user.firstName ?? app.user.email)|safe_user_data }}, 
                        {{ 'loans.manage_easily'|trans }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Données sécurisées avec vérification des permissions -->
    {% for loan in applications %}
        {% if is_granted('view', loan) %}
            <div class="loan-card">
                <h3>{{ 'loan.reference'|trans }}: {{ loan.reference|e }}</h3>
                <p>{{ 'loan.amount'|trans }}: {{ loan.requestedAmount|currency_format }}</p>
            </div>
        {% endif %}
    {% endfor %}
</div>
{% endblock %}
```

---

## 🎯 6. COMMANDES D'INSTALLATION

### Installation des Nouveaux Composants

```bash
# Tests
composer require --dev symfony/yaml

# Extensions Twig (si nécessaire)
composer require twig/extra-bundle

# Commandes de validation
./bin/console lint:yaml translations/
./bin/console lint:twig templates/
```

### Tests Automatisés

```bash
# Validation YAML
./bin/console test:yaml-translations

# Tests de sécurité
./bin/phpunit tests/Translations/YamlValidationTest.php
./bin/phpunit tests/Security/
```

---

## ✅ CHECKLIST DE VALIDATION

### Sécurité ✅
- [ ] Échappement XSS dans tous les templates
- [ ] Validation des permissions avant affichage des données
- [ ] Configuration Twig sécurisée
- [ ] Voter de sécurité implémenté

### Performance ✅  
- [ ] Cache des traductions activé
- [ ] Requêtes N+1 éliminées
- [ ] Optimisations base de données

### Qualité ✅
- [ ] Tests YAML automatisés
- [ ] Validation des configurations
- [ ] Code documenté et commenté
- [ ] Standards de codage respectés

---

**🎯 PRIORITÉ ABSOLUE:** Appliquer les corrections XSS avant tout déploiement en production.

**⏰ TEMPS ESTIMÉ:** 2-3 heures pour les corrections critiques, 1-2 jours pour l'implémentation complète.

---

**MiniMax Agent** | 26/09/2025