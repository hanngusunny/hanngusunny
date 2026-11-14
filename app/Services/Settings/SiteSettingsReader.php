<?php

namespace App\Services\Settings;

class SiteSettingsReader
{
    public function __construct(private readonly SiteSettingsService $settings)
    {
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->settings->get($key, $default);
    }
}
