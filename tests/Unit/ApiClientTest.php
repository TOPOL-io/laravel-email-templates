<?php

declare(strict_types=1);

namespace Topol\EmailTemplates\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Topol\EmailTemplates\ApiClient;
use Topol\EmailTemplates\Exceptions\ApiException;
use Topol\EmailTemplates\Exceptions\TemplateNotFoundException;
use Topol\EmailTemplates\Tests\TestCase;

class ApiClientTest extends TestCase
{
    protected ApiClient $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = new ApiClient;
        Cache::flush();
    }

    /** @test */
    public function it_can_fetch_template_from_api(): void
    {
        $templateId = 'template-123';
        $expectedData = [
            'id' => $templateId,
            'subject' => 'Test Subject',
            'from_email' => 'test@example.com',
            'from_name' => 'Test Sender',
            'data' => [
                'html' => '<html><body>Test</body></html>',
            ],
        ];

        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response($expectedData, 200),
        ]);

        $result = $this->apiClient->fetchTemplate($templateId);

        $this->assertEquals($expectedData, $result);
        Http::assertSent(function ($request) use ($templateId) {
            return $request->url() === "https://app.topol.io/api/templates/{$templateId}"
                && $request->hasHeader('Authorization', 'Bearer test-api-key')
                && $request->hasHeader('Accept', 'application/json');
        });
    }

    /** @test */
    public function it_throws_exception_when_template_not_found(): void
    {
        $templateId = 'non-existent';

        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response(null, 404),
        ]);

        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage("Template with ID {$templateId} not found");

        $this->apiClient->fetchTemplate($templateId);
    }

    /** @test */
    public function it_throws_exception_on_api_error(): void
    {
        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response('Server Error', 500),
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/API request failed with status 500/');

        $this->apiClient->fetchTemplate('template-123');
    }

    /** @test */
    public function it_caches_template_when_cache_is_enabled(): void
    {
        $templateId = 'template-123';
        $expectedData = [
            'id' => $templateId,
            'subject' => 'Test Subject',
            'data' => ['html' => '<html>Test</html>'],
        ];

        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response($expectedData, 200),
        ]);

        // First call - should hit API
        $result1 = $this->apiClient->fetchTemplate($templateId);
        $this->assertEquals($expectedData, $result1);

        // Second call - should use cache
        $result2 = $this->apiClient->fetchTemplate($templateId);
        $this->assertEquals($expectedData, $result2);

        // Verify API was only called once
        Http::assertSentCount(1);

        // Verify cache was used
        $cacheKey = 'topol_email_template_'.$templateId;
        $this->assertEquals($expectedData, Cache::get($cacheKey));
    }

    /** @test */
    public function it_does_not_cache_when_cache_is_disabled(): void
    {
        config(['email-templates.cache.enabled' => false]);
        $apiClient = new ApiClient;

        $templateId = 'template-123';
        $expectedData = [
            'id' => $templateId,
            'subject' => 'Test Subject',
            'data' => ['html' => '<html>Test</html>'],
        ];

        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response($expectedData, 200),
        ]);

        // First call
        $apiClient->fetchTemplate($templateId);

        // Second call - should hit API again
        $apiClient->fetchTemplate($templateId);

        // Verify API was called twice
        Http::assertSentCount(2);

        // Verify nothing was cached
        $cacheKey = 'topol_email_template_'.$templateId;
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_can_clear_specific_template_cache(): void
    {
        $templateId = 'template-123';
        $expectedData = ['id' => $templateId, 'subject' => 'Test'];

        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response($expectedData, 200),
        ]);

        // Fetch and cache template
        $this->apiClient->fetchTemplate($templateId);

        $cacheKey = 'topol_email_template_'.$templateId;
        $this->assertNotNull(Cache::get($cacheKey));

        // Clear cache
        $this->apiClient->clearCache($templateId);

        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_can_clear_all_cache(): void
    {
        $templateId1 = 'template-123';
        $templateId2 = 'template-456';

        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response(['id' => 'test'], 200),
        ]);

        // Fetch and cache multiple templates
        $this->apiClient->fetchTemplate($templateId1);
        $this->apiClient->fetchTemplate($templateId2);

        // Verify both are cached
        $this->assertNotNull(Cache::get('topol_email_template_'.$templateId1));
        $this->assertNotNull(Cache::get('topol_email_template_'.$templateId2));

        // Clear all cache
        $this->apiClient->clearAllCache();

        // Verify cache is cleared
        $this->assertNull(Cache::get('topol_email_template_'.$templateId1));
        $this->assertNull(Cache::get('topol_email_template_'.$templateId2));
    }

    /** @test */
    public function it_uses_custom_cache_prefix(): void
    {
        config(['email-templates.cache.prefix' => 'custom_prefix_']);
        $apiClient = new ApiClient;

        $templateId = 'template-123';
        $expectedData = ['id' => $templateId];

        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response($expectedData, 200),
        ]);

        $apiClient->fetchTemplate($templateId);

        $this->assertNotNull(Cache::get('custom_prefix_'.$templateId));
        $this->assertNull(Cache::get('topol_email_template_'.$templateId));
    }

    /** @test */
    public function it_respects_cache_ttl_configuration(): void
    {
        $customTtl = 7200;
        config(['email-templates.cache.ttl' => $customTtl]);
        $apiClient = new ApiClient;

        $templateId = 'template-123';
        $expectedData = ['id' => $templateId];

        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response($expectedData, 200),
        ]);

        Cache::shouldReceive('get')
            ->once()
            ->with('topol_email_template_'.$templateId)
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once()
            ->with('topol_email_template_'.$templateId, $expectedData, $customTtl);

        $apiClient->fetchTemplate($templateId);
    }

    /** @test */
    public function it_handles_network_exceptions(): void
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/Failed to fetch template/');

        $this->apiClient->fetchTemplate('template-123');
    }

    /** @test */
    public function it_includes_authorization_header_when_api_key_is_set(): void
    {
        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response(['id' => 'test'], 200),
        ]);

        $this->apiClient->fetchTemplate('template-123');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    /** @test */
    public function it_works_without_api_key(): void
    {
        config(['email-templates.api_key' => null]);
        $apiClient = new ApiClient;

        Http::fake([
            'https://app.topol.io/api/templates/*' => Http::response(['id' => 'test'], 200),
        ]);

        $apiClient->fetchTemplate('template-123');

        Http::assertSent(function ($request) {
            return ! $request->hasHeader('Authorization');
        });
    }
}
