<?php

declare(strict_types=1);

namespace Topol\EmailTemplates\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Mockery;
use Topol\EmailTemplates\ApiClient;
use Topol\EmailTemplates\EmailTemplates;
use Topol\EmailTemplates\Tests\TestCase;
use Topol\EmailTemplates\TopolMailable;

class EmailTemplatesTest extends TestCase
{
    protected EmailTemplates $emailTemplates;
    protected ApiClient $mockApiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApiClient = Mockery::mock(ApiClient::class);
        $this->emailTemplates = new EmailTemplates($this->mockApiClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_fetch_template(): void
    {
        $templateId = 'template-123';
        $expectedData = [
            'id' => $templateId,
            'subject' => 'Test Subject',
            'data' => ['html' => '<html>Test</html>'],
        ];

        $this->mockApiClient
            ->shouldReceive('fetchTemplate')
            ->once()
            ->with($templateId)
            ->andReturn($expectedData);

        $result = $this->emailTemplates->fetchTemplate($templateId);

        $this->assertEquals($expectedData, $result);
    }

    /** @test */
    public function it_can_clear_cache_for_specific_template(): void
    {
        $templateId = 'template-123';

        $this->mockApiClient
            ->shouldReceive('clearCache')
            ->once()
            ->with($templateId);

        $this->emailTemplates->clearCache($templateId);

        $this->assertTrue(true); // Assert that the method was called without errors
    }

    /** @test */
    public function it_can_clear_all_cache(): void
    {
        $this->mockApiClient
            ->shouldReceive('clearAllCache')
            ->once();

        $this->emailTemplates->clearAllCache();

        $this->assertTrue(true); // Assert that the method was called without errors
    }

    /** @test */
    public function it_can_create_mailable_instance(): void
    {
        $templateId = 'template-123';
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $mailable = $this->emailTemplates->mailable($templateId, $data);

        $this->assertInstanceOf(TopolMailable::class, $mailable);
        $this->assertEquals($templateId, $mailable->templateId);
        $this->assertEquals($data, $mailable->data);
    }

    /** @test */
    public function it_can_create_mailable_without_data(): void
    {
        $templateId = 'template-123';

        $mailable = $this->emailTemplates->mailable($templateId);

        $this->assertInstanceOf(TopolMailable::class, $mailable);
        $this->assertEquals($templateId, $mailable->templateId);
        $this->assertEquals([], $mailable->data);
    }

    /** @test */
    public function it_uses_injected_api_client(): void
    {
        $templateId = 'template-123';
        $expectedData = ['id' => $templateId];

        $this->mockApiClient
            ->shouldReceive('fetchTemplate')
            ->once()
            ->with($templateId)
            ->andReturn($expectedData);

        $result = $this->emailTemplates->fetchTemplate($templateId);

        $this->assertEquals($expectedData, $result);
    }

    /** @test */
    public function it_can_be_instantiated_without_api_client(): void
    {
        Http::fake([
            'https://api.topol.io/templates/*' => Http::response(['id' => 'test'], 200),
        ]);

        $emailTemplates = new EmailTemplates;
        $result = $emailTemplates->fetchTemplate('template-123');

        $this->assertIsArray($result);
        $this->assertEquals('test', $result['id']);
    }

    /** @test */
    public function it_is_registered_as_singleton_in_service_container(): void
    {
        $instance1 = app('email-templates');
        $instance2 = app('email-templates');

        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(EmailTemplates::class, $instance1);
    }

    /** @test */
    public function api_client_is_registered_as_singleton(): void
    {
        $instance1 = app(ApiClient::class);
        $instance2 = app(ApiClient::class);

        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(ApiClient::class, $instance1);
    }
}
