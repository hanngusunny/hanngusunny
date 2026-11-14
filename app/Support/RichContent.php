<?php

namespace App\Support;

class RichContent
{
    public function renderComment(?string $html): string
    {
        return HtmlContent::render($html);
    }

    public function hasComment(?string $html): bool
    {
        return HtmlContent::has($html);
    }

    public function sanitizeComment(?string $html): string
    {
        return HtmlContent::sanitize($html);
    }
}
