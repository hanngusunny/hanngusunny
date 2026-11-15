<?php

namespace App\Support;

class JitsiJwt
{
    private const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @param  array{id?: string, name?: string, email?: string}  $user
     */
    public function generateForJaasRoom(
        string $appId,
        string $kid,
        string $privateKeyPem,
        array $user,
        bool $moderator,
        int $ttlSeconds = 7200
    ): string {
        $now = time();
        $userId = $this->normalizeUserId((string) ($user['id'] ?? ''));

        $payload = [
            'aud' => 'jitsi',
            'iss' => 'chat',
            'sub' => $appId,
            'room' => '*',
            'exp' => $now + $ttlSeconds,
            'nbf' => $now - 10,
            'context' => [
                'user' => [
                    'id' => $userId,
                    'name' => (string) ($user['name'] ?? 'User'),
                    'email' => (string) ($user['email'] ?? ''),
                    'moderator' => (bool) $moderator,
                ],
                'features' => [
                    'recording' => (bool) $moderator,
                    'livestreaming' => false,
                    'transcription' => false,
                    'outbound-call' => false,
                    'screen-sharing' => true,
                ],
                'room' => [
                    'regex' => false,
                ],
            ],
        ];

        $header = [
            'alg' => 'RS256',
            'kid' => $kid,
            'typ' => 'JWT',
        ];

        return $this->encode($header, $payload, $privateKeyPem);
    }

    private function normalizeUserId(string $userId): string
    {
        $userId = trim($userId);

        if ($userId === '') {
            return $this->randomUuid();
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $userId) === 1) {
            return strtolower($userId);
        }

        $hash = md5('sunny-jaas-user-'.$userId);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }

    private function randomUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $header, array $payload, string $privateKeyPem): string
    {
        $privateKeyPem = $this->normalizePrivateKeyPem($privateKeyPem);
        $segments = [
            $this->base64UrlEncode((string) json_encode($header, self::JSON_FLAGS)),
            $this->base64UrlEncode((string) json_encode($payload, self::JSON_FLAGS)),
        ];
        $input = implode('.', $segments);
        $key = openssl_pkey_get_private($privateKeyPem);

        if ($key === false) {
            throw new \RuntimeException('Không đọc được private key JaaS.');
        }

        $signature = '';

        if (! openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Không ký được JWT JaaS.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function normalizePrivateKeyPem(string $privateKeyPem): string
    {
        $privateKeyPem = trim(str_replace("\r\n", "\n", $privateKeyPem));

        if ($privateKeyPem === '') {
            return '';
        }

        if (str_contains($privateKeyPem, 'BEGIN PRIVATE KEY') || str_contains($privateKeyPem, 'BEGIN RSA PRIVATE KEY')) {
            return $privateKeyPem;
        }

        $decoded = base64_decode($privateKeyPem, true);

        if ($decoded !== false) {
            $wrapped = chunk_split(base64_encode($decoded), 64, "\n");

            return "-----BEGIN PRIVATE KEY-----\n".$wrapped."-----END PRIVATE KEY-----\n";
        }

        return $privateKeyPem;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
