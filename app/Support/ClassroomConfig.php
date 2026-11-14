<?php

namespace App\Support;

class ClassroomConfig
{
    public function presenceTimeout(): int
    {
        return max(30, (int) config('classroom.presence_timeout', 90));
    }

    public function presenceIntervalMs(): int
    {
        return max(5000, (int) config('classroom.presence_interval_ms', 20000));
    }

    public function jaasAppId(): string
    {
        return trim((string) config('classroom.jaas_app_id', ''));
    }

    public function jaasKid(): string
    {
        return trim((string) config('classroom.jaas_kid', ''));
    }

    public function jitsiDomain(): string
    {
        return trim((string) config('classroom.jitsi_domain', ''));
    }

    public function jaasJwtTtl(): int
    {
        return max(300, (int) config('classroom.jaas_jwt_ttl', 7200));
    }

    public function isJaasConfigured(): bool
    {
        return (bool) config('classroom.jaas_enabled', false)
            && $this->jaasAppId() !== ''
            && $this->jaasKid() !== '';
    }

    public function isJaasReady(): bool
    {
        return $this->isJaasConfigured() && $this->privateKeyPem() !== '';
    }

    public function privateKeyPem(): string
    {
        $inline = trim((string) config('classroom.jaas_private_key', ''));

        if ($inline !== '') {
            return str_replace('\\n', "\n", $inline);
        }

        $path = trim((string) config('classroom.jaas_private_key_path', ''));

        if ($path !== '' && ! preg_match('#^(?:[A-Za-z]:[\\\\/]|/|\\\\)#', $path)) {
            $path = base_path($path);
        }

        if ($path === '' || ! is_file($path)) {
            $path = storage_path('app/jaas/private.pk');
        }

        if (! is_file($path)) {
            return '';
        }

        $pem = @file_get_contents($path);

        return is_string($pem) ? trim($pem) : '';
    }

    public function effectiveDomain(): string
    {
        return JitsiMeetLink::domain($this->isJaasReady(), $this->jitsiDomain() !== '' ? $this->jitsiDomain() : null);
    }

    /**
     * @return list<string>
     */
    public function jaasSetupIssues(): array
    {
        if (! (bool) config('classroom.jaas_enabled', false)) {
            return [];
        }

        $issues = [];

        if ($this->jaasAppId() === '') {
            $issues[] = 'Thiếu classroom.jaas_app_id.';
        }

        if ($this->jaasKid() === '') {
            $issues[] = 'Thiếu classroom.jaas_kid.';
        }

        if ($this->privateKeyPem() === '') {
            $issues[] = 'Thiếu private key JaaS (storage/app/jaas/private.pk).';
        }

        return $issues;
    }
}
