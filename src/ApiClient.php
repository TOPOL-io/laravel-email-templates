<?php

declare(strict_types=1);

namespace Topol\EmailTemplates;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Topol\EmailTemplates\Exceptions\ApiException;
use Topol\EmailTemplates\Exceptions\TemplateNotFoundException;

class ApiClient
{
    protected string $apiUrl = 'https://app.topol.io/api';
    protected ?string $apiKey;
    protected int $timeout;
    /** @var array<string, mixed> */
    protected array $cacheConfig;

    public function __construct()
    {
        $apiKey = config('email-templates.api_key');
        $this->apiKey = is_string($apiKey) ? $apiKey : null;

        $timeout = config('email-templates.timeout', 30);
        $this->timeout = is_int($timeout) ? $timeout : 30;

        $cacheConfig = config('email-templates.cache', []);
        /** @var array<string, mixed> $validatedConfig */
        $validatedConfig = is_array($cacheConfig) ? $cacheConfig : [];
        $this->cacheConfig = $validatedConfig;
    }

    /**
     * Fetch email template by ID from the API
     *
     * @return array<string, mixed>
     *
     * @throws TemplateNotFoundException
     * @throws ApiException
     */
    public function fetchTemplate(string|int $templateId): array
    {
        // Check cache first if enabled
        if ($this->isCacheEnabled()) {
            $cached = $this->getFromCache($templateId);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getAuthHeaders())
                ->get("{$this->apiUrl}/templates/{$templateId}");

            if ($response->status() === 404) {
                throw new TemplateNotFoundException("Template with ID {$templateId} not found");
            }

            if (! $response->successful()) {
                throw new ApiException(
                    "API request failed with status {$response->status()}: {$response->body()}"
                );
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();

            // Cache the result if enabled
            if ($this->isCacheEnabled()) {
                $this->saveToCache($templateId, $data);
            }

            return $data;
        } catch (TemplateNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ApiException("Failed to fetch template: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get authentication headers for API requests
     *
     * @return array<string, string>
     */
    protected function getAuthHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->apiKey) {
            $headers['Authorization'] = 'Bearer '.$this->apiKey;
        }

        return $headers;
    }

    /**
     * Check if cache is enabled
     */
    protected function isCacheEnabled(): bool
    {
        return (bool) ($this->cacheConfig['enabled'] ?? false);
    }

    /**
     * Get template from cache
     *
     * @return array<string, mixed>|null
     */
    protected function getFromCache(string|int $templateId): ?array
    {
        $key = $this->getCacheKey($templateId);
        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($key);

        return $cached;
    }

    /**
     * Save template to cache
     *
     * @param  array<string, mixed>  $data
     */
    protected function saveToCache(string|int $templateId, array $data): void
    {
        $key = $this->getCacheKey($templateId);
        $ttl = $this->cacheConfig['ttl'] ?? 3600;
        $ttlInt = is_int($ttl) ? $ttl : 3600;
        Cache::put($key, $data, $ttlInt);
    }

    /**
     * Generate cache key for template
     */
    protected function getCacheKey(string|int $templateId): string
    {
        $prefix = $this->cacheConfig['prefix'] ?? 'topol_email_template_';
        $prefixStr = is_string($prefix) ? $prefix : 'topol_email_template_';

        return $prefixStr.(string) $templateId;
    }

    /**
     * Clear cached template
     */
    public function clearCache(string|int $templateId): void
    {
        $key = $this->getCacheKey($templateId);
        Cache::forget($key);
    }

    /**
     * Clear all cached templates
     */
    public function clearAllCache(): void
    {
        // This is a simple implementation - you might want to use cache tags for better management
        Cache::flush();
    }
}
