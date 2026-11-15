<?php

namespace App\Support;

class JitsiMeetLink
{
    public static function roomName(string $appId, string $roomCode, bool $jaasReady, int $breakoutIndex = 0): string
    {
        $roomCode = trim($roomCode);

        if ($roomCode === '') {
            return '';
        }

        if ($breakoutIndex > 0) {
            $roomCode .= '-bo-'.$breakoutIndex;
        }

        if ($jaasReady && trim($appId) !== '') {
            return trim($appId).'/'.$roomCode;
        }

        return $roomCode;
    }

    public static function domain(bool $jaasReady, ?string $configuredDomain = null): string
    {
        $configuredDomain = trim((string) $configuredDomain);

        if ($configuredDomain !== '') {
            return $configuredDomain;
        }

        return $jaasReady ? '8x8.vc' : 'meet.jit.si';
    }

    public static function baseMeetUrl(string $domain, string $roomName): string
    {
        return 'https://'.trim($domain, '/').'/'.ltrim($roomName, '/');
    }

    public const APP_SCHEME = 'org.jitsi.meet';

    public static function appDeepLink(string $domain, string $roomName, string $jwt): string
    {
        return self::APP_SCHEME.'://'.trim($domain, '/').'/'.ltrim($roomName, '/').'?jwt='.rawurlencode($jwt);
    }

    public static function webJoinUrl(string $domain, string $roomName, string $jwt, ?string $subject = null): string
    {
        $toolbarJson = json_encode([
            'microphone',
            'camera',
            'desktop',
            'screensharing',
            'hangup',
            'chat',
            'raisehand',
            'overflowmenu',
            'settings',
        ], JSON_UNESCAPED_SLASHES);

        $hashParts = [
            'config.prejoinPageEnabled=false',
            'config.disableDeepLinking=true',
            'config.p2p.enabled=false',
            'config.disableFilmstrip=false',
            'config.startWithAudioMuted=false',
            'config.disableScreensharing=false',
            'config.enableClosePage=false',
            'config.toolbarButtons='.$toolbarJson,
            'interfaceConfig.MOBILE_APP_PROMO=false',
            'interfaceConfig.SHOW_JITSI_WATERMARK=false',
            'interfaceConfig.TOOLBAR_BUTTONS='.$toolbarJson,
        ];

        if ($subject !== null && trim($subject) !== '') {
            $hashParts[] = 'config.subject='.rawurlencode(trim($subject));
        }

        return self::baseMeetUrl($domain, $roomName)
            .'?jwt='.rawurlencode($jwt)
            .'#'.implode('&', $hashParts);
    }
}
