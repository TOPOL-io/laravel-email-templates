# Changelog

All notable changes to `topol/email-templates` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0-beta.1] - 2025-11-11

### Initial Beta Release

This is the first beta release of the Topol Email Templates package for Laravel. The package is in active development and the API may change before the stable 1.0.0 release.

### Added

#### Core Features
- **ApiClient** - HTTP client for fetching email templates from Topol.io API
  - Bearer token authentication support
  - Configurable timeout settings
  - Comprehensive error handling with custom exceptions
- **EmailTemplates** - Main service class for template management
  - Template fetching with automatic caching
  - Cache management (clear specific or all cached templates)
  - Mailable factory method
- **TopolMailable** - Laravel Mailable implementation
  - Automatic template fetching from API
  - Variable replacement supporting both `{{variable}}` and `{variable}` syntax
  - Support for subject, from, reply-to from template data
  - HTML and text content support
  - Queue support via `Queueable` trait
  - Serialization support via `SerializesModels` trait
- **EmailTemplatesServiceProvider** - Laravel service provider
  - Auto-discovery support
  - Configuration publishing
  - Singleton service registration
- **EmailTemplatesFacade** - Laravel facade for easy access

#### Caching System
- Built-in template caching with configurable TTL
- Customizable cache key prefix
- Cache enable/disable toggle
- Individual and bulk cache clearing

#### Configuration
- Comprehensive configuration file (`config/email-templates.php`)
  - API URL configuration
  - API key authentication
  - Request timeout settings
  - Cache settings (enabled, TTL, prefix)
- Environment variable support for all configuration options

#### Exception Handling
- `ApiException` - For API communication errors
- `TemplateNotFoundException` - For 404 responses

#### Developer Tools
- **PHPUnit Test Suite** - 37 comprehensive tests with 71 assertions
  - Unit tests for `ApiClient`, `EmailTemplates`, and `TopolMailable`
  - Feature tests for email sending scenarios
  - 100% passing test coverage
  - Orchestra Testbench integration for Laravel package testing
- **Larastan (PHPStan)** - Static analysis at maximum level
  - Zero errors at max level
  - Full type safety with proper type hints
  - Configured for Laravel-specific analysis
- **Laravel Pint** - Code style enforcement
  - Laravel preset configuration
  - Automatic code formatting
  - Pre-configured rules for consistency
- **Composer Scripts** for easy development workflow:
  - `composer test` - Run PHPUnit tests
  - `composer test-coverage` - Generate HTML coverage report
  - `composer phpstan` - Run static analysis
  - `composer phpstan-baseline` - Generate PHPStan baseline
  - `composer pint` - Format code
  - `composer pint-test` - Check code style without fixing

#### Documentation
- Comprehensive README with usage examples
- Multiple usage patterns documented (Facade, Mailable, Trait)
- API response format specification
- Installation and configuration guide
- Quick start examples

### Requirements
- PHP 8.4
- Laravel 12.x
- Guzzle HTTP 7.x

### Dependencies
- `illuminate/support`: ^12.0
- `illuminate/mail`: ^12.0
- `illuminate/http`: ^12.0
- `guzzlehttp/guzzle`: ^7.0

### Development Dependencies
- `orchestra/testbench`: ^10.0
- `phpunit/phpunit`: ^11.0
- `larastan/larastan`: ^3.0
- `laravel/pint`: ^1.25

### Technical Details
- PSR-4 autoloading
- Strict type declarations throughout
- Full PHPDoc coverage
- Laravel package auto-discovery
- Singleton pattern for service instances

[Unreleased]: https://github.com/topol/email-templates/compare/v0.1.0-beta.1...HEAD
[0.1.0-beta.1]: https://github.com/topol/email-templates/releases/tag/v0.1.0-beta.1
