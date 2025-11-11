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
     * @param array<mixed> $data Additional data to pass to the template
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
        if (isset($template['subject'])) {
            $this->subject($this->replaceVariables($template['subject'], $this->data));
        }

        // Set from address if provided in template
        if (isset($template['from_email'])) {
            $fromName = $template['from_name'] ?? null;
            $this->from($template['from_email'], $fromName);
        }

        // Set reply-to if provided in template
        if (isset($template['reply_to'])) {
            $this->replyTo($template['reply_to']);
        }

        // Get HTML content
        $htmlContent = $template['data']['html'] ?? '';
        $htmlContent = $this->replaceVariables($htmlContent, $this->data);

        // Set the view with HTML content
        $this->html($htmlContent);

        // If there's a text version, use it
        if (isset($template['text'])) {
            $textContent = $this->replaceVariables($template['text'], $this->data);
            $this->text($textContent);
        }

        return $this;
    }

    /**
     * Replace variables in content with actual data
     *
     * @param string $content
     * @param array $data
     * @return string
     */
    protected function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            // Support both {{variable}} and {variable} syntax
            $content = str_replace(['{{'.$key.'}}', '{'.$key.'}'], $value, $content);
        }

        return $content;
    }
}

