# Topol Email Templates for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/topol/email-templates.svg?style=flat-square)](https://packagist.org/packages/topol/email-templates)
[![Total Downloads](https://img.shields.io/packagist/dt/topol/email-templates.svg?style=flat-square)](https://packagist.org/packages/topol/email-templates)
![GitHub Actions](https://github.com/topol/email-templates/actions/workflows/main.yml/badge.svg)

A Laravel package that seamlessly integrates with Topol.io email templates. This package hooks into Laravel's email sending system to fetch email templates from the Topol API by ID, with built-in caching and multiple usage patterns.

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
composer require topol/email-templates
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

### Using TopolMailable

```php
use Illuminate\Support\Facades\Mail;
use Topol\EmailTemplates\TopolMailable;

Mail::to('user@example.com')->send(
    new TopolMailable('template-123', ['name' => 'John Doe'])
);
```

## API Response Format

Your Topol API should return templates in this format:

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

## Advanced Usage

For more examples including queueing, attachments, multiple recipients, and error handling, see [USAGE_EXAMPLES.md](USAGE_EXAMPLES.md).

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email jakub@topol.io instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
