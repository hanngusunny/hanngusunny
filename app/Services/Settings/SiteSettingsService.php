<?php

namespace App\Services\Settings;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;

class SiteSettingsService
{
    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $out = config('site.defaults', []);
        if (! Schema::hasTable('site_settings')) {
            return $out;
        }

        foreach (SiteSetting::query()->get() as $row) {
            $key = (string) $row->setting_key;
            if (array_key_exists($key, $out) || $key === 'editor_google_fonts') {
                $out[$key] = $row->setting_value === null ? '' : (string) $row->setting_value;
            }
        }

        return $out;
    }

    public function get(string $key, string $default = ''): string
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? (string) $all[$key] : $default;
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $data
     */
    public function saveMany(array $data): bool
    {
        $allowed = array_fill_keys(array_keys(config('site.defaults', [])), true);
        $allowed['editor_google_fonts'] = true;
        $now = now();

        foreach ($data as $key => $value) {
            if (! isset($allowed[$key])) {
                continue;
            }
            SiteSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => $value === null ? '' : (string) $value,
                    'updated_at' => $now,
                ]
            );
        }

        return true;
    }

    public function isMaintenance(): bool
    {
        return $this->get('maintenance_mode', '0') === '1';
    }

    public function comingSoonActive(): bool
    {
        if ($this->get('coming_soon_enabled', '0') !== '1') {
            return false;
        }
        $until = trim($this->get('coming_soon_until', ''));
        if ($until === '') {
            return false;
        }
        $ts = strtotime($until);

        return $ts !== false && time() < $ts;
    }
}
