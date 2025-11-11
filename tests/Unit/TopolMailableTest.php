<?php

declare(strict_types=1);

namespace Topol\EmailTemplates\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Topol\EmailTemplates\TopolMailable;
use Topol\EmailTemplates\Tests\TestCase;

class TopolMailableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function it_can_be_instantiated_with_template_id(): void
    {
        $templateId = 'template-123';
        $mailable = new TopolMailable($templateId);

        $this->assertEquals($templateId, $mailable->templateId);
        $this->assertEquals([], $mailable->data);
    }

    /** @test */
    public function it_can_be_instantiated_with_template_id_and_data(): void
    {
        $templateId = 'template-123';
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $mailable = new TopolMailable($templateId, $data);

        $this->assertEquals($templateId, $mailable->templateId);
        $this->assertEquals($data, $mailable->data);
    }

    /** @test */
    public function it_fetches_template_and_builds_email(): void
    {
        $templateId = 'template-123';
        $templateData = [
            'id' => $templateId,
            'subject' => 'Welcome {{name}}!',
            'from_email' => 'noreply@example.com',
            'from_name' => 'Test Company',
            'reply_to' => 'support@example.com',
            'data' => [
                'html' => '<html><body>Hello {{name}}!</body></html>',
            ],
            'text' => 'Hello {{name}}!',
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable($templateId, ['name' => 'John']);
        $mailable->build();

        // Access the rendered HTML
        $rendered = $mailable->render();
        $this->assertEquals('Welcome John!', $mailable->subject);
        $this->assertEquals('noreply@example.com', $mailable->from[0]['address']);
        $this->assertEquals('Test Company', $mailable->from[0]['name']);
        $this->assertEquals('support@example.com', $mailable->replyTo[0]['address']);
        $this->assertEquals('<html><body>Hello John!</body></html>', $rendered);
    }

    /** @test */
    public function it_replaces_variables_in_subject(): void
    {
        $templateData = [
            'subject' => 'Order {{order_number}} for {{customer_name}}',
            'data' => [
                'html' => '<html>Test</html>',
            ],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123', [
            'order_number' => '12345',
            'customer_name' => 'Jane Doe',
        ]);
        $mailable->build();

        $this->assertEquals('Order 12345 for Jane Doe', $mailable->subject);
    }

    /** @test */
    public function it_replaces_variables_in_html_content(): void
    {
        $templateData = [
            'subject' => 'Test',
            'data' => [
                'html' => '<html><body>Hello {{name}}, your order {{order_id}} is ready!</body></html>',
            ],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123', [
            'name' => 'Alice',
            'order_id' => 'ORD-999',
        ]);
        $mailable->build();

        // Access the rendered HTML
        $rendered = $mailable->render();
        $this->assertStringContainsString('Hello Alice', $rendered);
        $this->assertStringContainsString('your order ORD-999 is ready', $rendered);
    }

    /** @test */
    public function it_supports_both_double_and_single_brace_syntax(): void
    {
        $templateData = [
            'subject' => 'Test {{name}}',
            'data' => [
                'html' => '<html><body>{{greeting}} {name}!</body></html>',
            ],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123', [
            'name' => 'Bob',
            'greeting' => 'Hello',
        ]);
        $mailable->build();

        $rendered = $mailable->render();
        $this->assertStringContainsString('Hello Bob!', $rendered);
        $this->assertEquals('Test Bob', $mailable->subject);
    }

    /** @test */
    public function it_handles_template_without_subject(): void
    {
        $templateData = [
            'data' => [
                'html' => '<html><body>Test</body></html>',
            ],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123');
        $mailable->build();

        $this->assertNull($mailable->subject);
    }

    /** @test */
    public function it_handles_template_without_from_email(): void
    {
        $templateData = [
            'subject' => 'Test',
            'data' => [
                'html' => '<html><body>Test</body></html>',
            ],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123');
        $mailable->build();

        $this->assertEmpty($mailable->from);
    }

    /** @test */
    public function it_handles_template_without_reply_to(): void
    {
        $templateData = [
            'subject' => 'Test',
            'from_email' => 'test@example.com',
            'data' => [
                'html' => '<html><body>Test</body></html>',
            ],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123');
        $mailable->build();

        $this->assertEmpty($mailable->replyTo);
    }

    /** @test */
    public function it_handles_template_with_text_version(): void
    {
        $templateData = [
            'subject' => 'Test',
            'data' => [
                'html' => '<html><body>Hello {{name}}</body></html>',
            ],
            'text' => 'Hello {{name}}',
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123', ['name' => 'Charlie']);
        $mailable->build();

        // The text view should be set
        $this->assertNotNull($mailable->textView);
    }

    /** @test */
    public function it_handles_template_without_text_version(): void
    {
        $templateData = [
            'subject' => 'Test',
            'data' => [
                'html' => '<html><body>Test</body></html>',
            ],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123');
        $mailable->build();

        // Should not throw an error
        $this->assertInstanceOf(TopolMailable::class, $mailable);
    }

    /** @test */
    public function it_can_be_queued(): void
    {
        $templateData = [
            'subject' => 'Test',
            'data' => [
                'html' => '<html><body>Test</body></html>',
            ],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123');

        // Check that it uses the Queueable trait
        $this->assertTrue(method_exists($mailable, 'onQueue'));
        $this->assertTrue(method_exists($mailable, 'onConnection'));
    }

    /** @test */
    public function it_is_serializable(): void
    {
        $mailable = new TopolMailable('template-123', ['name' => 'Test']);

        // Check that it uses the SerializesModels trait
        $serialized = serialize($mailable);
        $unserialized = unserialize($serialized);

        $this->assertEquals($mailable->templateId, $unserialized->templateId);
        $this->assertEquals($mailable->data, $unserialized->data);
    }

    /** @test */
    public function it_handles_empty_html_content(): void
    {
        $templateData = [
            'subject' => 'Test',
            'data' => [],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123');
        $mailable->build();

        // Should not throw an error
        $this->assertInstanceOf(TopolMailable::class, $mailable);
    }

    /** @test */
    public function it_handles_multiple_variable_replacements(): void
    {
        $templateData = [
            'subject' => '{{greeting}} {{name}}!',
            'data' => [
                'html' => '<html><body>{{greeting}} {{name}}, {{message}}</body></html>',
            ],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123', [
            'greeting' => 'Hello',
            'name' => 'World',
            'message' => 'Welcome to our service',
        ]);
        $mailable->build();

        $this->assertEquals('Hello World!', $mailable->subject);
        $rendered = $mailable->render();
        $this->assertStringContainsString('Hello World, Welcome to our service', $rendered);
    }

    /** @test */
    public function it_leaves_unreplaced_variables_as_is(): void
    {
        $templateData = [
            'subject' => 'Hello {{name}}',
            'data' => [
                'html' => '<html><body>{{greeting}} {{name}}</body></html>',
            ],
        ];

        Http::fake([
            'https://api.topol.io/templates/*' => Http::response($templateData, 200),
        ]);

        $mailable = new TopolMailable('template-123', ['name' => 'Alice']);
        $mailable->build();

        $this->assertEquals('Hello Alice', $mailable->subject);
        $rendered = $mailable->render();
        $this->assertStringContainsString('{{greeting}}', $rendered);
        $this->assertStringContainsString('Alice', $rendered);
    }
}

