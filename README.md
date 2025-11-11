# Laravel Email Templates by Topol.io

[![Latest Version on Packagist](https://img.shields.io/packagist/v/topol/laravel-email-templates.svg?style=flat-square)](https://packagist.org/packages/topol/laravel-email-templates)
[![Total Downloads](https://img.shields.io/packagist/dt/topol/laravel-email-templates.svg?style=flat-square)](https://packagist.org/packages/topol/laravel-email-templates)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/TOPOL-io/laravel-email-templates/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/TOPOL-io/laravel-email-templates/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](https://opensource.org/licenses/MIT)

A modern Laravel package that seamlessly integrates with [Topol.io](https://topol.io) email templates. Fetch, cache, and send beautiful email templates with automatic variable replacement and full Laravel Mail integration.

## Requirements

- **PHP 8.4+**
- **Laravel 12.x**
- Topol.io API access

## Features

- 🚀 **Easy Integration** - Hooks directly into Laravel's Mail system
- 📧 **Multiple Usage Patterns** - Facade, Mailable, or Trait-based approaches
- 💾 **Built-in Caching** - Reduces API calls with configurable cache
- 🔄 **Variable Replacement** - Supports `{{variable}}` and `{variable}` syntax
- ⚡ **Queue Support** - Send emails immediately or queue them
- 🛠️ **Artisan Commands** - Test API connection easily
- 🎯 **Type-safe** - Full IDE autocomplete support

## Installation

Install the package via composer:

```bash
composer require topol/laravel-email-templates
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Topol\EmailTemplates\EmailTemplatesServiceProvider" --tag="config"
```

Add these environment variables to your `.env` file:

```env
TOPOL_API_URL=https://api.topol.io
TOPOL_API_KEY=your-api-key
TOPOL_CACHE_ENABLED=true
TOPOL_CACHE_TTL=3600
```

## Quick Start

### Basic Usage

```php
use Illuminate\Support\Facades\Mail;
use Topol\EmailTemplates\TopolMailable;

// Send an email with template variables
Mail::to('user@example.com')->send(
    new TopolMailable('template-123', [
        'name' => 'John Doe',
        'company' => 'Acme Inc'
    ])
);
```

## Usage Examples

### 1. Using TopolMailable (Recommended)

The simplest way to send emails with Topol templates:

```php
use Illuminate\Support\Facades\Mail;
use Topol\EmailTemplates\TopolMailable;

// Basic usage
Mail::to('user@example.com')
    ->send(new TopolMailable('welcome-email', [
        'name' => 'John Doe',
        'activation_link' => 'https://example.com/activate'
    ]));

// With multiple recipients
Mail::to('user@example.com')
    ->cc('manager@example.com')
    ->bcc('archive@example.com')
    ->send(new TopolMailable('newsletter', ['month' => 'November']));

// Queue the email for better performance
Mail::to('user@example.com')
    ->queue(new TopolMailable('welcome-email', ['name' => 'John']));
```

### 2. Using the Facade

For more control over template fetching:

```php
use Topol\EmailTemplates\Facades\EmailTemplates;

// Fetch a template
$template = EmailTemplates::getTemplate('template-123');

// Clear cached template
EmailTemplates::clearCache('template-123');

// Clear all cached templates
EmailTemplates::clearAllCache();

// Create a mailable from template
$mailable = EmailTemplates::mailable('template-123', ['name' => 'John']);
Mail::to('user@example.com')->send($mailable);
```

### 3. Custom Mailable Class

Create your own mailable class for reusable email templates:

```php
namespace App\Mail;

use Topol\EmailTemplates\TopolMailable;

class WelcomeEmail extends TopolMailable
{
    public function __construct(public string $userName, public string $activationLink)
    {
        parent::__construct('welcome-email', [
            'name' => $this->userName,
            'activation_link' => $this->activationLink,
        ]);
    }
}

// Usage
Mail::to('user@example.com')->send(
    new WelcomeEmail('John Doe', 'https://example.com/activate/abc123')
);
```

## Configuration

The package configuration file is located at `config/email-templates.php`:

```php
return [
    // Topol API URL
    'api_url' => env('TOPOL_API_URL', 'https://api.topol.io'),

    // Your Topol API key
    'api_key' => env('TOPOL_API_KEY'),

    // Request timeout in seconds
    'timeout' => env('TOPOL_TIMEOUT', 30),

    // Cache settings
    'cache' => [
        'enabled' => env('TOPOL_CACHE_ENABLED', true),
        'ttl' => env('TOPOL_CACHE_TTL', 3600), // 1 hour
        'prefix' => env('TOPOL_CACHE_PREFIX', 'topol_email_template_'),
    ],
];
```

### Environment Variables

Add these to your `.env` file:

```env
TOPOL_API_URL=https://api.topol.io
TOPOL_API_KEY=your-api-key-here
TOPOL_CACHE_ENABLED=true
TOPOL_CACHE_TTL=3600
TOPOL_TIMEOUT=30
```

## API Response Format

Your Topol API endpoint should return templates in this JSON format:

```json
{
    "id": "template-123",
    "subject": "Welcome {{name}}!",
    "from_email": "noreply@example.com",
    "from_name": "Your Company",
    "reply_to": "support@example.com",
    "html": "<html><body>Hello {{name}}!</body></html>",
    "text": "Hello {{name}}!"
}
```

### Variable Replacement

The package supports both `{{variable}}` and `{variable}` syntax for template variables:

```php
// Template content
"Hello {{name}}, welcome to {company}!"

// Variables
['name' => 'John', 'company' => 'Acme Inc']

// Result
"Hello John, welcome to Acme Inc!"
```

## Caching

Templates are automatically cached to reduce API calls:

```php
use Topol\EmailTemplates\Facades\EmailTemplates;

// First call fetches from API and caches
$template = EmailTemplates::getTemplate('template-123');

// Subsequent calls use cached version
$template = EmailTemplates::getTemplate('template-123');

// Clear specific template cache
EmailTemplates::clearCache('template-123');

// Clear all template caches
EmailTemplates::clearAllCache();
```

Cache duration is controlled by `TOPOL_CACHE_TTL` (in seconds).

## Testing

The package includes a comprehensive test suite:

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis (PHPStan at max level)
composer phpstan

# Check code style
composer pint-test

# Fix code style
composer pint
```

## Error Handling

The package throws specific exceptions for different error scenarios:

```php
use Topol\EmailTemplates\Exceptions\ApiException;
use Topol\EmailTemplates\Exceptions\TemplateNotFoundException;

try {
    $template = EmailTemplates::getTemplate('template-123');
} catch (TemplateNotFoundException $e) {
    // Template not found (404)
    Log::error('Template not found: ' . $e->getMessage());
} catch (ApiException $e) {
    // Other API errors (500, network issues, etc.)
    Log::error('API error: ' . $e->getMessage());
}
```

## Advanced Usage

### Queuing Emails

```php
use Illuminate\Support\Facades\Mail;
use Topol\EmailTemplates\TopolMailable;

// Queue email for background processing
Mail::to('user@example.com')
    ->queue(new TopolMailable('welcome-email', ['name' => 'John']));

// Queue with delay
Mail::to('user@example.com')
    ->later(now()->addMinutes(10), new TopolMailable('reminder', ['task' => 'Review']));
```

### Custom From Address

```php
// Override template's from address
Mail::to('user@example.com')
    ->from('custom@example.com', 'Custom Sender')
    ->send(new TopolMailable('template-123', ['name' => 'John']));
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security-related issues, please email jakub@topol.io instead of using the issue tracker.

## Credits

- [Jakub Gause](https://github.com/topol)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

Made with ❤️ by [Topol.io](https://topol.io)
