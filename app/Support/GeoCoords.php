<?php

namespace App\Support;

class GeoCoords
{
    /**
     * @return array{map_lat: float|null, map_lng: float|null, map_region: string}
     */
    public static function parse(?string $lat, ?string $lng, ?string $region): array
    {
        $region = trim((string) $region);
        $latStr = trim((string) $lat);
        $lngStr = trim((string) $lng);
        if ($latStr === '' || $lngStr === '') {
            return ['map_lat' => null, 'map_lng' => null, 'map_region' => $region];
        }
        if (! is_numeric($latStr) || ! is_numeric($lngStr)) {
            return ['map_lat' => null, 'map_lng' => null, 'map_region' => $region];
        }
        $la = (float) $latStr;
        $lo = (float) $lngStr;
        if ($la < -90 || $la > 90 || $lo < -180 || $lo > 180) {
            return ['map_lat' => null, 'map_lng' => null, 'map_region' => $region];
        }

        return ['map_lat' => $la, 'map_lng' => $lo, 'map_region' => $region];
    }
}
