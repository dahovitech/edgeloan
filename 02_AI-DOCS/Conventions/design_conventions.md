# Design Conventions for EdgeLoan

## Design System Foundation

### Brand Identity
- **Primary Color:** `#0066cc` (Professional Blue)
- **Secondary Color:** `#6c757d` (Neutral Gray)
- **Success:** `#198754` (Forest Green)
- **Warning:** `#ffc107` (Amber)
- **Danger:** `#dc3545` (Crimson Red)
- **Info:** `#0dcaf0` (Cyan Blue)

### Typography System
- **Primary Font:** -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif
- **Font Scale:** 14px base, 1.5 line height
- **Weights:** 400 (regular), 500 (medium), 600 (semibold), 700 (bold)

### Spacing Scale
- **Base Unit:** 4px
- **Scale:** 4px, 8px, 12px, 16px, 24px, 32px, 48px, 64px
- **Bootstrap Classes:** `.p-1` through `.p-5`, `.m-1` through `.m-5`

## Component Standards

### Navigation Design
```html
<!-- Primary Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="{{ path('admin_dashboard') }}">
      <i class="bi bi-bank2 fs-4 me-2"></i>
      <span class="fw-bold">EdgeLoan</span>
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link {{ app.request.get('_route') starts with 'admin_dashboard' ? 'active' }}" 
             href="{{ path('admin_dashboard') }}">
            <i class="bi bi-speedometer2 me-2"></i>{{ 'nav.dashboard'|trans }}
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-people me-2"></i>{{ 'nav.users'|trans }}
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ path('admin_user_index') }}">
              <i class="bi bi-list me-2"></i>{{ 'nav.user_list'|trans }}
            </a></li>
            <li><a class="dropdown-item" href="{{ path('admin_user_new') }}">
              <i class="bi bi-person-plus me-2"></i>{{ 'nav.add_user'|trans }}
            </a></li>
          </ul>
        </li>
      </ul>
      
      <!-- User Menu -->
      <div class="navbar-nav">
        <div class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
            <div class="avatar avatar-sm me-2">
              <img src="{{ app.user.avatarUrl ?? '/images/default-avatar.png' }}" 
                   alt="{{ app.user.username }}" 
                   class="rounded-circle">
            </div>
            <span class="d-none d-md-inline">{{ app.user.username }}</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ path('admin_profile') }}">
              <i class="bi bi-person me-2"></i>{{ 'nav.profile'|trans }}
            </a></li>
            <li><a class="dropdown-item" href="{{ path('admin_settings') }}">
              <i class="bi bi-gear me-2"></i>{{ 'nav.settings'|trans }}
            </a></li>
            <li><hr class="dropdown-divider"></li>
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
<!-- Standard Card Layout -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
    <h5 class="card-title mb-0 d-flex align-items-center">
      <i class="bi bi-folder2 text-primary me-2"></i>
      {{ title|trans }}
    </h5>
    <div class="card-tools">
      {% if can_create %}
      <a href="{{ create_url }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>{{ 'button.add'|trans }}
      </a>
      {% endif %}
    </div>
  </div>
  
  {% if has_filters %}
  <div class="card-body border-bottom bg-light py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label for="search" class="form-label small">{{ 'filter.search'|trans }}</label>
        <input type="text" class="form-control form-control-sm" 
               id="search" name="search" 
               value="{{ app.request.get('search') }}"
               placeholder="{{ 'filter.search.placeholder'|trans }}">
      </div>
      <div class="col-md-3">
        <label for="status" class="form-label small">{{ 'filter.status'|trans }}</label>
        <select class="form-select form-select-sm" id="status" name="status">
          <option value="">{{ 'filter.all'|trans }}</option>
          {% for status in available_statuses %}
          <option value="{{ status }}" {{ app.request.get('status') == status ? 'selected' }}>
            {{ ('status.' ~ status)|trans }}
          </option>
          {% endfor %}
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-search me-1"></i>{{ 'button.filter'|trans }}
        </button>
      </div>
    </form>
  </div>
  {% endif %}
  
  <div class="card-body p-0">
    <!-- Card content -->
  </div>
</div>
```

### Data Tables
```html
<!-- Professional Data Table -->
<div class="table-responsive">
  <table class="table table-hover mb-0">
    <thead class="table-light">
      <tr>
        <th class="border-0 ps-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="selectAll" 
                   aria-label="{{ 'table.select_all'|trans }}">
          </div>
        </th>
        <th class="border-0">{{ 'table.name'|trans }}</th>
        <th class="border-0">{{ 'table.email'|trans }}</th>
        <th class="border-0">{{ 'table.status'|trans }}</th>
        <th class="border-0">{{ 'table.created'|trans }}</th>
        <th class="border-0 text-end pe-3">{{ 'table.actions'|trans }}</th>
      </tr>
    </thead>
    <tbody>
      {% for item in items %}
      <tr class="align-middle">
        <td class="ps-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" 
                   value="{{ item.id }}" 
                   data-bulk-item
                   aria-label="{{ 'table.select_item'|trans({'%name%': item.name}) }}">
          </div>
        </td>
        <td>
          <div class="d-flex align-items-center">
            <div class="avatar avatar-sm me-3">
              {% if item.avatar %}
                <img src="{{ item.avatar }}" alt="{{ item.name }}" class="rounded-circle">
              {% else %}
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                  {{ item.name|first|upper }}
                </div>
              {% endif %}
            </div>
            <div>
              <div class="fw-medium">{{ item.name }}</div>
              {% if item.description %}
              <small class="text-muted">{{ item.description|u.truncate(50) }}</small>
              {% endif %}
            </div>
          </div>
        </td>
        <td>{{ item.email }}</td>
        <td>
          <span class="badge bg-{{ item.statusColor }} bg-opacity-15 text-{{ item.statusColor }} border border-{{ item.statusColor }}">
            {{ ('status.' ~ item.status)|trans }}
          </span>
        </td>
        <td>
          <time datetime="{{ item.createdAt|date('c') }}" 
                class="text-muted small"
                title="{{ item.createdAt|date('d/m/Y H:i:s') }}">
            {{ item.createdAt|time_diff }}
          </time>
        </td>
        <td class="text-end pe-3">
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                    type="button" 
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="{{ 'table.actions_for'|trans({'%name%': item.name}) }}">
              <i class="bi bi-three-dots"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="{{ path('admin_item_show', {id: item.id}) }}">
                  <i class="bi bi-eye me-2"></i>{{ 'action.view'|trans }}
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="{{ path('admin_item_edit', {id: item.id}) }}">
                  <i class="bi bi-pencil me-2"></i>{{ 'action.edit'|trans }}
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" 
                   href="{{ path('admin_item_delete', {id: item.id}) }}"
                   data-action="delete"
                   data-item-id="{{ item.id }}"
                   data-item-name="{{ item.name }}">
                  <i class="bi bi-trash me-2"></i>{{ 'action.delete'|trans }}
                </a>
              </li>
            </ul>
          </div>
        </td>
      </tr>
      {% else %}
      <tr>
        <td colspan="6" class="text-center py-5">
          <div class="empty-state">
            <i class="bi bi-inbox display-4 text-muted mb-3"></i>
            <h5 class="text-muted">{{ 'table.no_results'|trans }}</h5>
            <p class="text-muted mb-4">{{ 'table.no_results.description'|trans }}</p>
            {% if can_create %}
            <a href="{{ create_url }}" class="btn btn-primary">
              <i class="bi bi-plus-circle me-2"></i>{{ 'button.create_first'|trans }}
            </a>
            {% endif %}
          </div>
        </td>
      </tr>
      {% endfor %}
    </tbody>
  </table>
</div>

{% if items|length > 0 %}
<!-- Pagination -->
<div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center py-3">
  <div class="small text-muted">
    {{ 'pagination.showing'|trans({
      '%start%': (current_page - 1) * per_page + 1,
      '%end%': min(current_page * per_page, total_items),
      '%total%': total_items
    }) }}
  </div>
  
  {% if total_pages > 1 %}
  <nav aria-label="{{ 'pagination.navigation'|trans }}">
    <ul class="pagination pagination-sm mb-0">
      <li class="page-item {{ current_page <= 1 ? 'disabled' }}">
        <a class="page-link" 
           href="{{ current_page > 1 ? path(app.request.get('_route'), app.request.query.all|merge({page: current_page - 1})) : '#' }}"
           {{ current_page <= 1 ? 'aria-disabled="true" tabindex="-1"' }}>
          <i class="bi bi-chevron-left"></i>
          <span class="visually-hidden">{{ 'pagination.previous'|trans }}</span>
        </a>
      </li>
      
      {% for page in pagination_pages %}
        {% if page == '...' %}
        <li class="page-item disabled">
          <span class="page-link">…</span>
        </li>
        {% else %}
        <li class="page-item {{ page == current_page ? 'active' }}">
          <a class="page-link" 
             href="{{ path(app.request.get('_route'), app.request.query.all|merge({page: page})) }}"
             {{ page == current_page ? 'aria-current="page"' }}>
            {{ page }}
          </a>
        </li>
        {% endif %}
      {% endfor %}
      
      <li class="page-item {{ current_page >= total_pages ? 'disabled' }}">
        <a class="page-link" 
           href="{{ current_page < total_pages ? path(app.request.get('_route'), app.request.query.all|merge({page: current_page + 1})) : '#' }}"
           {{ current_page >= total_pages ? 'aria-disabled="true" tabindex="-1"' }}>
          <span class="visually-hidden">{{ 'pagination.next'|trans }}</span>
          <i class="bi bi-chevron-right"></i>
        </a>
      </li>
    </ul>
  </nav>
  {% endif %}
</div>
{% endif %}
```

### Form Components
```html
<!-- Professional Form Layout -->
{{ form_start(form, {'attr': {'class': 'needs-validation', 'novalidate': true}}) }}
  <div class="row g-3">
    <div class="col-md-6">
      <label for="{{ form.name.vars.id }}" class="form-label required">
        <i class="bi bi-person me-1"></i>
        {{ form.name.vars.label|trans }}
      </label>
      {{ form_widget(form.name, {
        'attr': {
          'class': 'form-control',
          'placeholder': form.name.vars.attr.placeholder|default(''),
          'aria-describedby': form.name.vars.id ~ 'Help'
        }
      }) }}
      {% if form.name.vars.help %}
      <div id="{{ form.name.vars.id }}Help" class="form-text">
        <i class="bi bi-info-circle me-1"></i>{{ form.name.vars.help|trans }}
      </div>
      {% endif %}
      <div class="invalid-feedback">
        {{ 'form.name.required'|trans }}
      </div>
    </div>
    
    <div class="col-md-6">
      <label for="{{ form.email.vars.id }}" class="form-label required">
        <i class="bi bi-envelope me-1"></i>
        {{ form.email.vars.label|trans }}
      </label>
      {{ form_widget(form.email, {
        'attr': {
          'class': 'form-control',
          'placeholder': 'email.placeholder'|trans
        }
      }) }}
      <div class="invalid-feedback">
        {{ 'form.email.invalid'|trans }}
      </div>
    </div>
  </div>
  
  <div class="row g-3 mt-2">
    <div class="col-12">
      <label for="{{ form.description.vars.id }}" class="form-label">
        <i class="bi bi-card-text me-1"></i>
        {{ form.description.vars.label|trans }}
      </label>
      {{ form_widget(form.description, {
        'attr': {
          'class': 'form-control',
          'rows': 4,
          'placeholder': 'description.placeholder'|trans
        }
      }) }}
    </div>
  </div>
  
  <!-- File Upload Section -->
  {% if form.avatar is defined %}
  <div class="row g-3 mt-2">
    <div class="col-12">
      <label for="{{ form.avatar.vars.id }}" class="form-label">
        <i class="bi bi-image me-1"></i>
        {{ form.avatar.vars.label|trans }}
      </label>
      <div class="file-upload-area border border-2 border-dashed rounded p-4 text-center">
        {{ form_widget(form.avatar, {
          'attr': {
            'class': 'form-control d-none',
            'accept': 'image/*'
          }
        }) }}
        <div class="upload-placeholder">
          <i class="bi bi-cloud-upload display-6 text-muted mb-2"></i>
          <p class="mb-2">{{ 'form.avatar.drag_drop'|trans }}</p>
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('{{ form.avatar.vars.id }}').click()">
            <i class="bi bi-folder2-open me-1"></i>{{ 'form.avatar.browse'|trans }}
          </button>
        </div>
        <div class="upload-preview d-none">
          <img src="" alt="Preview" class="img-thumbnail mb-2" style="max-height: 150px;">
          <p class="mb-0 small text-muted"></p>
        </div>
      </div>
      <div class="form-text">
        <i class="bi bi-info-circle me-1"></i>
        {{ 'form.avatar.help'|trans }}
      </div>
    </div>
  </div>
  {% endif %}
  
  <!-- Action Buttons -->
  <div class="row g-3 mt-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center">
        <a href="{{ back_url }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>{{ 'button.back'|trans }}
        </a>
        <div class="btn-group">
          {% if not is_new %}
          <button type="button" class="btn btn-outline-danger" data-action="delete">
            <i class="bi bi-trash me-1"></i>{{ 'button.delete'|trans }}
          </button>
          {% endif %}
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i>
            {{ is_new ? 'button.create'|trans : 'button.save'|trans }}
          </button>
        </div>
      </div>
    </div>
  </div>
{{ form_end(form) }}
```

### Modal Components
```html
<!-- Confirmation Modal Template -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title d-flex align-items-center">
          <i class="bi bi-exclamation-triangle text-warning me-2"></i>
          {{ 'modal.confirm.title'|trans }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ 'button.close'|trans }}"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="mb-0 modal-message">{{ 'modal.confirm.message'|trans }}</p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i>{{ 'button.cancel'|trans }}
        </button>
        <button type="button" class="btn btn-danger" id="confirmAction">
          <i class="bi bi-check-circle me-1"></i>{{ 'button.confirm'|trans }}
        </button>
      </div>
    </div>
  </div>
</div>
```

## State Management

### Loading States
```html
<!-- Loading Spinner -->
<div class="loading-state text-center py-5">
  <div class="spinner-border text-primary mb-3" role="status">
    <span class="visually-hidden">{{ 'loading'|trans }}</span>
  </div>
  <p class="text-muted mb-0">{{ 'loading.message'|trans }}</p>
</div>

<!-- Skeleton Loading -->
<div class="skeleton-loader">
  {% for i in 1..5 %}
  <div class="card mb-3">
    <div class="card-body">
      <div class="placeholder-glow">
        <div class="d-flex align-items-center mb-3">
          <div class="placeholder bg-secondary rounded-circle me-3" style="width: 48px; height: 48px;"></div>
          <div class="flex-grow-1">
            <span class="placeholder col-4 mb-1"></span>
            <span class="placeholder col-6"></span>
          </div>
        </div>
        <span class="placeholder col-8 mb-2"></span>
        <span class="placeholder col-6"></span>
      </div>
    </div>
  </div>
  {% endfor %}
</div>
```

### Error States
```html
<!-- Error Display -->
<div class="alert alert-danger border-0 shadow-sm" role="alert">
  <div class="d-flex align-items-start">
    <i class="bi bi-exclamation-triangle-fill fs-4 text-danger me-3 flex-shrink-0"></i>
    <div class="flex-grow-1">
      <h6 class="alert-heading mb-1">{{ 'error.title'|trans }}</h6>
      <p class="mb-2">{{ error_message|trans }}</p>
      {% if error_details %}
      <details class="small">
        <summary class="text-muted">{{ 'error.details'|trans }}</summary>
        <pre class="mt-2 mb-0 text-muted">{{ error_details }}</pre>
      </details>
      {% endif %}
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ 'button.close'|trans }}"></button>
  </div>
</div>

<!-- 404 Error Page -->
<div class="error-page text-center py-5">
  <div class="error-illustration mb-4">
    <i class="bi bi-exclamation-circle display-1 text-muted"></i>
  </div>
  <h1 class="display-4 mb-3">{{ 'error.404.title'|trans }}</h1>
  <p class="lead text-muted mb-4">{{ 'error.404.message'|trans }}</p>
  <div class="d-flex gap-3 justify-content-center">
    <button onclick="history.back()" class="btn btn-outline-primary">
      <i class="bi bi-arrow-left me-2"></i>{{ 'button.go_back'|trans }}
    </button>
    <a href="{{ path('admin_dashboard') }}" class="btn btn-primary">
      <i class="bi bi-house me-2"></i>{{ 'button.dashboard'|trans }}
    </a>
  </div>
</div>
```

### Success States
```html
<!-- Success Message -->
<div class="alert alert-success border-0 shadow-sm alert-dismissible" role="alert">
  <div class="d-flex align-items-center">
    <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
    <div class="flex-grow-1">
      <h6 class="alert-heading mb-1">{{ 'success.title'|trans }}</h6>
      <p class="mb-0">{{ success_message|trans }}</p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ 'button.close'|trans }}"></button>
  </div>
</div>

<!-- Toast Notification -->
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div class="toast align-items-center text-white bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body d-flex align-items-center">
        <i class="bi bi-check-circle me-2"></i>
        {{ 'toast.success'|trans }}
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
```

## Responsive Design Patterns

### Mobile Navigation
```html
<!-- Mobile Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title d-flex align-items-center">
      <i class="bi bi-bank2 text-primary me-2"></i>EdgeLoan
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <nav class="nav nav-pills flex-column p-3">
      <a class="nav-link active d-flex align-items-center mb-1" href="{{ path('admin_dashboard') }}">
        <i class="bi bi-speedometer2 me-3"></i>{{ 'nav.dashboard'|trans }}
      </a>
      <a class="nav-link d-flex align-items-center mb-1" href="{{ path('admin_user_index') }}">
        <i class="bi bi-people me-3"></i>{{ 'nav.users'|trans }}
      </a>
      <!-- More navigation items -->
    </nav>
  </div>
</div>
```

### Responsive Cards
```css
/* Responsive Card Adjustments */
@media (max-width: 768px) {
  .card {
    margin-bottom: 1rem;
  }
  
  .card-header {
    flex-direction: column;
    align-items: stretch !important;
    gap: 0.75rem;
  }
  
  .card-tools {
    align-self: stretch;
  }
  
  .card-tools .btn {
    width: 100%;
  }
}

/* Mobile Table Enhancements */
@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }
  
  .table td, .table th {
    padding: 0.5rem 0.75rem;
  }
  
  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
}
```

## Accessibility Standards

### ARIA Implementation
```html
<!-- Accessible Form Controls -->
<div class="mb-3">
  <label for="searchInput" class="form-label">
    {{ 'search.label'|trans }}
  </label>
  <div class="input-group">
    <input type="search" 
           class="form-control" 
           id="searchInput"
           name="search"
           aria-describedby="searchHelp"
           aria-label="{{ 'search.aria_label'|trans }}"
           placeholder="{{ 'search.placeholder'|trans }}">
    <button class="btn btn-outline-secondary" 
            type="button"
            aria-label="{{ 'search.button.aria_label'|trans }}">
      <i class="bi bi-search" aria-hidden="true"></i>
    </button>
  </div>
  <div id="searchHelp" class="form-text">
    {{ 'search.help'|trans }}
  </div>
</div>

<!-- Skip Navigation -->
<a class="visually-hidden-focusable" href="#main-content">
  {{ 'accessibility.skip_to_main'|trans }}
</a>

<!-- Screen Reader Support -->
<div class="visually-hidden" id="live-region" aria-live="polite" aria-atomic="true"></div>
```

### Focus Management
```css
/* Enhanced Focus Styles */
:focus-visible {
  outline: 2px solid var(--bs-primary) !important;
  outline-offset: 2px;
  border-radius: 0.25rem;
}

.btn:focus-visible,
.form-control:focus,
.form-select:focus {
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
  .btn,
  .card,
  .form-control,
  .form-select {
    border-width: 2px;
  }
  
  .text-muted {
    color: var(--bs-dark) !important;
  }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

## Performance Optimization

### Critical CSS
```css
/* Above-the-fold critical styles */
.navbar {
  background-color: #0066cc !important;
}

.navbar-brand {
  font-weight: 700;
  color: white !important;
}

.card {
  border: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-primary {
  background-color: #0066cc;
  border-color: #0066cc;
}
```

### Image Optimization
```html
<!-- Responsive Images -->
<picture>
  <source srcset="{{ asset('images/hero-mobile.webp') }}" 
          media="(max-width: 768px)" 
          type="image/webp">
  <source srcset="{{ asset('images/hero-desktop.webp') }}" 
          type="image/webp">
  <img src="{{ asset('images/hero-desktop.jpg') }}" 
       alt="{{ 'hero.alt'|trans }}"
       class="img-fluid"
       loading="lazy"
       width="1200" 
       height="600">
</picture>
```

These design conventions ensure a consistent, professional, and accessible user experience across all EdgeLoan interfaces while maintaining high performance and usability standards.
