<?php

namespace App\Services;

use App\Support\CityAnchors;

class GeocodingService
{
    public function labelFor(?float $latitude, ?float $longitude): ?string
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return $this->nearestAnchor($latitude, $longitude)['label'];
    }

    /**
     * @return array{lat: float, lng: float, label: string}
     */
    public function nearestAnchor(float $latitude, float $longitude): array
    {
        $anchors = CityAnchors::all();
        $nearest = $anchors[0];
        $bestDistance = $this->haversineKm($latitude, $longitude, $nearest['lat'], $nearest['lng']);

        foreach ($anchors as $anchor) {
            $distance = $this->haversineKm($latitude, $longitude, $anchor['lat'], $anchor['lng']);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $nearest = $anchor;
            }
        }

        return $nearest;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
