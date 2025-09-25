# AI Design Agent Optimization for EdgeLoan

## Design Philosophy: Silicon Valley Standards

### Core Principles
- **User-Centric:** Every design decision prioritizes user experience
- **Professional Excellence:** Enterprise-grade visual design
- **Accessibility First:** WCAG AA compliance as minimum standard
- **Responsive Excellence:** Flawless experience across all devices
- **Performance Conscious:** Fast loading, optimized assets

### Visual Identity Standards

#### Color Palette
```css
/* Primary Colors */
:root {
  --primary: #0066cc;           /* Main brand blue */
  --primary-dark: #004499;      /* Darker shade for hover states */
  --primary-light: #3385d6;     /* Lighter shade for backgrounds */
  
  --secondary: #6c757d;         /* Bootstrap secondary */
  --success: #198754;           /* Success actions */
  --warning: #ffc107;           /* Warning states */
  --danger: #dc3545;            /* Error states */
  --info: #0dcaf0;             /* Information */
  
  --light: #f8f9fa;            /* Light backgrounds */
  --dark: #212529;             /* Dark text */
  --muted: #6c757d;            /* Muted text */
}
```

#### Typography Hierarchy
```css
/* Typography Scale */
.display-1 { font-size: 5rem; font-weight: 300; }
.display-2 { font-size: 4.5rem; font-weight: 300; }
.display-3 { font-size: 4rem; font-weight: 300; }
.display-4 { font-size: 3.5rem; font-weight: 300; }

.h1, h1 { font-size: 2.5rem; font-weight: 600; }
.h2, h2 { font-size: 2rem; font-weight: 600; }
.h3, h3 { font-size: 1.75rem; font-weight: 600; }
.h4, h4 { font-size: 1.5rem; font-weight: 600; }
.h5, h5 { font-size: 1.25rem; font-weight: 600; }
.h6, h6 { font-size: 1rem; font-weight: 600; }

.lead { font-size: 1.25rem; font-weight: 300; }
.text-large { font-size: 1.1rem; }
.text-small { font-size: 0.875rem; }
.text-xs { font-size: 0.75rem; }
```

## Component Design Standards

### Navigation Design
```html
<!-- Example: Professional Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="{{ path('admin_dashboard') }}">
      <i class="bi bi-bank2 me-2"></i>EdgeLoan
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link {{ app.request.get('_route') starts with 'admin_dashboard' ? 'active' }}" 
             href="{{ path('admin_dashboard') }}">
            <i class="bi bi-speedometer2 me-1"></i>{{ 'nav.dashboard'|trans }}
          </a>
        </li>
        <!-- More nav items... -->
      </ul>
      
      <div class="navbar-nav">
        <div class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i>{{ app.user.username }}
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ path('app_logout') }}">
              <i class="bi bi-box-arrow-right me-2"></i>{{ 'nav.logout'|trans }}
            </a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>
```

### Card Components
```html
<!-- Example: Professional Card Design -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-bottom py-3">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">
        <i class="bi bi-folder2 text-primary me-2"></i>
        {{ 'section.title'|trans }}
      </h5>
      <div class="card-tools">
        <button type="button" class="btn btn-primary btn-sm">
          <i class="bi bi-plus-circle me-1"></i>{{ 'button.add'|trans }}
        </button>
      </div>
    </div>
  </div>
  <div class="card-body">
    <!-- Card content -->
  </div>
</div>
```

### Form Design Excellence
```html
<!-- Example: Professional Form Layout -->
<form class="needs-validation" novalidate>
  <div class="row g-3">
    <div class="col-md-6">
      <label for="firstName" class="form-label required">
        {{ 'form.first_name'|trans }}
      </label>
      <input type="text" 
             class="form-control" 
             id="firstName" 
             name="firstName" 
             required 
             aria-describedby="firstNameHelp">
      <div class="form-text" id="firstNameHelp">
        {{ 'form.first_name.help'|trans }}
      </div>
      <div class="invalid-feedback">
        {{ 'form.first_name.required'|trans }}
      </div>
    </div>
    
    <div class="col-md-6">
      <label for="lastName" class="form-label required">
        {{ 'form.last_name'|trans }}
      </label>
      <input type="text" 
             class="form-control" 
             id="lastName" 
             name="lastName" 
             required>
      <div class="invalid-feedback">
        {{ 'form.last_name.required'|trans }}
      </div>
    </div>
  </div>
  
  <div class="row g-3 mt-2">
    <div class="col-12">
      <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>{{ 'button.cancel'|trans }}
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-circle me-1"></i>{{ 'button.save'|trans }}
        </button>
      </div>
    </div>
  </div>
</form>
```

### Data Tables
```html
<!-- Example: Professional Data Table -->
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th scope="col" class="border-0">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="selectAll">
          </div>
        </th>
        <th scope="col" class="border-0">{{ 'table.name'|trans }}</th>
        <th scope="col" class="border-0">{{ 'table.email'|trans }}</th>
        <th scope="col" class="border-0">{{ 'table.status'|trans }}</th>
        <th scope="col" class="border-0">{{ 'table.created'|trans }}</th>
        <th scope="col" class="border-0 text-end">{{ 'table.actions'|trans }}</th>
      </tr>
    </thead>
    <tbody>
      {% for item in items %}
      <tr>
        <td>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="{{ item.id }}">
          </div>
        </td>
        <td>
          <div class="d-flex align-items-center">
            <div class="avatar me-3">
              <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                   style="width: 32px; height: 32px;">
                {{ item.name|first|upper }}
              </div>
            </div>
            <div>
              <div class="fw-semibold">{{ item.name }}</div>
              <small class="text-muted">{{ item.description|u.truncate(50) }}</small>
            </div>
          </div>
        </td>
        <td>{{ item.email }}</td>
        <td>
          <span class="badge bg-{{ item.active ? 'success' : 'secondary' }}">
            {{ item.active ? 'status.active'|trans : 'status.inactive'|trans }}
          </span>
        </td>
        <td>
          <small class="text-muted">{{ item.createdAt|date('d/m/Y H:i') }}</small>
        </td>
        <td class="text-end">
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                    type="button" 
                    data-bs-toggle="dropdown">
              {{ 'button.actions'|trans }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="{{ path('admin_item_edit', {id: item.id}) }}">
                  <i class="bi bi-pencil me-2"></i>{{ 'action.edit'|trans }}
                </a>
              </li>
              <li>
                <a class="dropdown-item text-danger" 
                   href="{{ path('admin_item_delete', {id: item.id}) }}"
                   onclick="return confirm('{{ 'confirm.delete'|trans }}')">
                  <i class="bi bi-trash me-2"></i>{{ 'action.delete'|trans }}
                </a>
              </li>
            </ul>
          </div>
        </td>
      </tr>
      {% endfor %}
    </tbody>
  </table>
</div>
```

## User Experience Patterns

### Loading States
```html
<!-- Professional Loading Indicator -->
<div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
  <div class="spinner-border text-primary" role="status">
    <span class="visually-hidden">{{ 'loading'|trans }}</span>
  </div>
  <span class="ms-2">{{ 'loading.message'|trans }}</span>
</div>

<!-- Skeleton Loading for Tables -->
<div class="skeleton-loader">
  <div class="row g-3">
    {% for i in 1..5 %}
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="placeholder-glow">
            <span class="placeholder col-3 me-2"></span>
            <span class="placeholder col-4 me-2"></span>
            <span class="placeholder col-2"></span>
          </div>
        </div>
      </div>
    </div>
    {% endfor %}
  </div>
</div>
```

### Error States
```html
<!-- Professional Error Display -->
<div class="alert alert-danger border-0 shadow-sm" role="alert">
  <div class="d-flex align-items-center">
    <i class="bi bi-exclamation-triangle-fill text-danger fs-4 me-3"></i>
    <div>
      <h6 class="alert-heading mb-1">{{ 'error.title'|trans }}</h6>
      <p class="mb-0 small">{{ error_message|trans }}</p>
    </div>
  </div>
</div>

<!-- Empty State -->
<div class="text-center py-5">
  <i class="bi bi-inbox display-1 text-muted mb-3"></i>
  <h4 class="text-muted">{{ 'empty.title'|trans }}</h4>
  <p class="text-muted mb-4">{{ 'empty.description'|trans }}</p>
  <a href="{{ path('admin_create') }}" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>{{ 'button.create_first'|trans }}
  </a>
</div>
```

### Success Feedback
```html
<!-- Toast Notifications -->
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div class="toast" role="alert">
    <div class="toast-header">
      <i class="bi bi-check-circle-fill text-success me-2"></i>
      <strong class="me-auto">{{ 'success.title'|trans }}</strong>
      <small class="text-muted">{{ 'time.now'|trans }}</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">
      {{ success_message|trans }}
    </div>
  </div>
</div>
```

## Responsive Design Guidelines

### Breakpoint Strategy
```css
/* Mobile First Approach */
.container-responsive {
  padding: 1rem;
}

@media (min-width: 576px) {
  .container-responsive {
    padding: 1.5rem;
  }
}

@media (min-width: 768px) {
  .container-responsive {
    padding: 2rem;
  }
}

@media (min-width: 992px) {
  .container-responsive {
    padding: 2.5rem;
  }
}

@media (min-width: 1200px) {
  .container-responsive {
    padding: 3rem;
  }
}
```

### Mobile Navigation
```html
<!-- Mobile-Optimized Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">{{ 'nav.menu'|trans }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <nav class="nav nav-pills flex-column">
      <a class="nav-link active" href="{{ path('admin_dashboard') }}">
        <i class="bi bi-speedometer2 me-2"></i>{{ 'nav.dashboard'|trans }}
      </a>
      <!-- More nav items... -->
    </nav>
  </div>
</div>
```

## Accessibility Standards

### ARIA Implementation
```html
<!-- Proper ARIA Labels -->
<button class="btn btn-primary" 
        type="button" 
        aria-label="{{ 'button.save.aria'|trans }}"
        aria-describedby="saveHelp">
  <i class="bi bi-check-circle" aria-hidden="true"></i>
  {{ 'button.save'|trans }}
</button>
<div id="saveHelp" class="sr-only">
  {{ 'button.save.help'|trans }}
</div>

<!-- Skip Navigation -->
<a class="sr-only sr-only-focusable" href="#main-content">
  {{ 'accessibility.skip_nav'|trans }}
</a>

<!-- Proper Form Labels -->
<div class="mb-3">
  <label for="email" class="form-label required">
    {{ 'form.email'|trans }}
  </label>
  <input type="email" 
         class="form-control" 
         id="email" 
         name="email" 
         required 
         aria-describedby="emailHelp" 
         aria-invalid="false">
  <div id="emailHelp" class="form-text">
    {{ 'form.email.help'|trans }}
  </div>
</div>
```

### Focus Management
```css
/* Custom Focus Styles */
.btn:focus,
.form-control:focus,
.form-select:focus {
  outline: 2px solid var(--primary);
  outline-offset: 2px;
  box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
  .btn {
    border-width: 2px;
  }
  
  .card {
    border-width: 2px;
  }
}
```

## Performance Optimization

### Image Optimization
```html
<!-- Responsive Images with WebP Support -->
<picture>
  <source srcset="{{ asset('images/hero.webp') }}" type="image/webp">
  <img src="{{ asset('images/hero.jpg') }}" 
       class="img-fluid" 
       alt="{{ 'hero.alt'|trans }}"
       loading="lazy"
       width="800" 
       height="400">
</picture>
```

### Critical CSS
```css
/* Inline Critical CSS for Above-the-Fold Content */
.critical-header {
  background-color: var(--primary);
  color: white;
  padding: 1rem 0;
}

.critical-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.critical-content {
  min-height: 50vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
```

## Animation and Transitions

### Subtle Interactions
```css
/* Professional Hover Effects */
.btn {
  transition: all 0.15s ease-in-out;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card {
  transition: box-shadow 0.15s ease-in-out;
}

.card:hover {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

/* Loading Animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.fade-in {
  animation: fadeIn 0.3s ease-out;
}
```

## Design System Tokens

### Spacing Scale
```css
:root {
  --space-xs: 0.25rem;   /* 4px */
  --space-sm: 0.5rem;    /* 8px */
  --space-md: 1rem;      /* 16px */
  --space-lg: 1.5rem;    /* 24px */
  --space-xl: 2rem;      /* 32px */
  --space-2xl: 3rem;     /* 48px */
  --space-3xl: 4rem;     /* 64px */
}
```

### Shadow System
```css
:root {
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}
```

## Quality Checklist

Before considering any design complete:

- ✅ Mobile-first responsive design
- ✅ WCAG AA accessibility compliance
- ✅ Consistent with design system
- ✅ Professional visual hierarchy
- ✅ Proper loading and error states
- ✅ Optimized for performance
- ✅ Cross-browser compatibility
- ✅ Proper ARIA labels and roles
- ✅ Keyboard navigation support
- ✅ Multi-language layout considerations

Remember: Design is not just how it looks, but how it works. Every pixel should serve a purpose and enhance the user experience.
