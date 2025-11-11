<?php

declare(strict_types=1);

namespace Topol\EmailTemplates;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Topol\EmailTemplates\Exceptions\ApiException;
use Topol\EmailTemplates\Exceptions\TemplateNotFoundException;

class TopolMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param string $templateId Template ID to fetch from API
     * @param array<string, mixed> $data Additional data to pass to the template
     */
    public function __construct(
        public readonly string $templateId,
        public readonly array $data = []
    ) {}

    /**
     * Build the message.
     *
     * @return $this
     * @throws TemplateNotFoundException
     * @throws ApiException
     */
    public function build()
    {
        // Fetch template from API
        $apiClient = app(ApiClient::class);
        $template = $apiClient->fetchTemplate($this->templateId);

        // Set subject from template or use provided one
        if (isset($template['subject']) && is_string($template['subject'])) {
            $this->subject($this->replaceVariables($template['subject'], $this->data));
        }

        // Set from address if provided in template
        if (isset($template['from_email']) && is_string($template['from_email'])) {
            $fromName = isset($template['from_name']) && is_string($template['from_name']) ? $template['from_name'] : null;
            $this->from($template['from_email'], $fromName);
        }

        // Set reply-to if provided in template
        if (isset($template['reply_to']) && is_string($template['reply_to'])) {
            $this->replyTo($template['reply_to']);
        }

        // Get HTML content
        $htmlContent = '';
        if (isset($template['data']) && is_array($template['data']) && isset($template['data']['html']) && is_string($template['data']['html'])) {
            $htmlContent = $template['data']['html'];
        }
        $htmlContent = $this->replaceVariables($htmlContent, $this->data);

        // Set the view with HTML content
        $this->html($htmlContent);

        // If there's a text version, use it
        if (isset($template['text']) && is_string($template['text'])) {
            $textContent = $this->replaceVariables($template['text'], $this->data);
            $this->text($textContent);
        }

        return $this;
    }

    /**
     * Replace variables in content with actual data
     *
     * @param string $content
     * @param array<string, mixed> $data
     * @return string
     */
    protected function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            // Support both {{variable}} and {variable} syntax
            $valueStr = is_scalar($value) ? (string) $value : '';
            $content = str_replace(['{{'.$key.'}}', '{'.$key.'}'], $valueStr, $content);
        }

        return $content;
    }
}

