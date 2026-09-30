<?php

namespace App\Services\Content;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class SanitizeHtml
{
    public function handle(?string $html): string
    {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowMediaSchemes(['http', 'https'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(200_000);

        return (new HtmlSanitizer($config))->sanitize($html ?? '');
    }
}
