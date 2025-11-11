<?php

declare(strict_types=1);

namespace Topol\EmailTemplates;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Topol\EmailTemplates\Exceptions\ApiException;
use Topol\EmailTemplates\Exceptions\TemplateNotFoundException;

class ApiClient
{
    protected string $apiUrl;
    protected ?string $apiKey;
    protected int $timeout;
    protected array $cacheConfig;

    public function __construct()
    {
        $this->apiUrl = config('email-templates.api_url');
        $this->apiKey = config('email-templates.api_key');
        $this->timeout = config('email-templates.timeout', 30);
        $this->cacheConfig = config('email-templates.cache', []);
    }

    /**
     * Fetch email template by ID from the API
     *
     * @param string|int $templateId
     * @return array
     * @throws TemplateNotFoundException
     * @throws ApiException
     */
    public function fetchTemplate($templateId): array
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

            if (!$response->successful()) {
                throw new ApiException(
                    "API request failed with status {$response->status()}: {$response->body()}"
                );
            }

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
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->apiKey) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        return $headers;
    }

    /**
     * Check if cache is enabled
     *
     * @return bool
     */
    protected function isCacheEnabled(): bool
    {
        return $this->cacheConfig['enabled'] ?? false;
    }

    /**
     * Get template from cache
     *
     * @param string|int $templateId
     * @return array|null
     */
    protected function getFromCache($templateId): ?array
    {
        $key = $this->getCacheKey($templateId);
        return Cache::get($key);
    }

    /**
     * Save template to cache
     *
     * @param string|int $templateId
     * @param array $data
     * @return void
     */
    protected function saveToCache($templateId, array $data): void
    {
        $key = $this->getCacheKey($templateId);
        $ttl = $this->cacheConfig['ttl'] ?? 3600;
        Cache::put($key, $data, $ttl);
    }

    /**
     * Generate cache key for template
     *
     * @param string|int $templateId
     * @return string
     */
    protected function getCacheKey($templateId): string
    {
        $prefix = $this->cacheConfig['prefix'] ?? 'topol_email_template_';
        return $prefix . $templateId;
    }

    /**
     * Clear cached template
     *
     * @param string|int $templateId
     * @return void
     */
    public function clearCache($templateId): void
    {
        $key = $this->getCacheKey($templateId);
        Cache::forget($key);
    }

    /**
     * Clear all cached templates
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        // This is a simple implementation - you might want to use cache tags for better management
        Cache::flush();
    }
}

