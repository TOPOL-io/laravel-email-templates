<?php

declare(strict_types=1);

namespace Topol\EmailTemplates;

use Topol\EmailTemplates\Exceptions\ApiException;
use Topol\EmailTemplates\Exceptions\TemplateNotFoundException;

class EmailTemplates
{
    protected ApiClient $apiClient;

    public function __construct(?ApiClient $apiClient = null)
    {
        $this->apiClient = $apiClient ?? app(ApiClient::class);
    }

    /**
     * Fetch a template from the API
     *
     * @param string|int $templateId
     * @return array<string, mixed>
     * @throws TemplateNotFoundException
     * @throws ApiException
     */
    public function fetchTemplate(string|int $templateId): array
    {
        return $this->apiClient->fetchTemplate($templateId);
    }

    /**
     * Clear cached template
     *
     * @param string|int $templateId
     * @return void
     */
    public function clearCache(string|int $templateId): void
    {
        $this->apiClient->clearCache($templateId);
    }

    /**
     * Clear all cached templates
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        $this->apiClient->clearAllCache();
    }

    /**
     * Create a new TopolMailable instance
     *
     * @param string $templateId
     * @param array<string, mixed> $data
     * @return TopolMailable
     */
    public function mailable(string $templateId, array $data = []): TopolMailable
    {
        return new TopolMailable($templateId, $data);
    }
}
