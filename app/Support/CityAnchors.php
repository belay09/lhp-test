<?php

namespace App\Support;

class CityAnchors
{
    /**
     * Anchor coordinates and human-readable labels for major cities across the
     * US, Canada, Mexico, Europe, and a few global hubs. Seeded events are
     * jittered around one of these anchors.
     *
     * @return list<array{lat: float, lng: float, label: string}>
     */
    public static function all(): array
    {
        return [
            // United States
            ['lat' => 40.7128, 'lng' => -74.0060, 'label' => 'New York, US'],
            ['lat' => 34.0522, 'lng' => -118.2437, 'label' => 'Los Angeles, US'],
            ['lat' => 41.8781, 'lng' => -87.6298, 'label' => 'Chicago, US'],
            ['lat' => 29.7604, 'lng' => -95.3698, 'label' => 'Houston, US'],
            ['lat' => 33.4484, 'lng' => -112.0740, 'label' => 'Phoenix, US'],
            ['lat' => 39.9526, 'lng' => -75.1652, 'label' => 'Philadelphia, US'],
            ['lat' => 29.4241, 'lng' => -98.4936, 'label' => 'San Antonio, US'],
            ['lat' => 32.7157, 'lng' => -117.1611, 'label' => 'San Diego, US'],
            ['lat' => 32.7767, 'lng' => -96.7970, 'label' => 'Dallas, US'],
            ['lat' => 37.3382, 'lng' => -121.8863, 'label' => 'San Jose, US'],
            ['lat' => 30.2672, 'lng' => -97.7431, 'label' => 'Austin, US'],
            ['lat' => 37.7749, 'lng' => -122.4194, 'label' => 'San Francisco, US'],
            ['lat' => 47.6062, 'lng' => -122.3321, 'label' => 'Seattle, US'],
            ['lat' => 39.7392, 'lng' => -104.9903, 'label' => 'Denver, US'],
            ['lat' => 42.3601, 'lng' => -71.0589, 'label' => 'Boston, US'],
            ['lat' => 36.1699, 'lng' => -115.1398, 'label' => 'Las Vegas, US'],
            ['lat' => 25.7617, 'lng' => -80.1918, 'label' => 'Miami, US'],
            ['lat' => 33.7490, 'lng' => -84.3880, 'label' => 'Atlanta, US'],
            ['lat' => 38.9072, 'lng' => -77.0369, 'label' => 'Washington, US'],
            ['lat' => 36.1627, 'lng' => -86.7816, 'label' => 'Nashville, US'],
            ['lat' => 45.5152, 'lng' => -122.6784, 'label' => 'Portland, US'],
            ['lat' => 29.9511, 'lng' => -90.0715, 'label' => 'New Orleans, US'],
            // Canada
            ['lat' => 43.6532, 'lng' => -79.3832, 'label' => 'Toronto, CA'],
            ['lat' => 45.5019, 'lng' => -73.5674, 'label' => 'Montreal, CA'],
            ['lat' => 49.2827, 'lng' => -123.1207, 'label' => 'Vancouver, CA'],
            ['lat' => 51.0447, 'lng' => -114.0719, 'label' => 'Calgary, CA'],
            ['lat' => 45.4215, 'lng' => -75.6972, 'label' => 'Ottawa, CA'],
            ['lat' => 53.5461, 'lng' => -113.4938, 'label' => 'Edmonton, CA'],
            ['lat' => 46.8139, 'lng' => -71.2080, 'label' => 'Quebec City, CA'],
            ['lat' => 49.8951, 'lng' => -97.1384, 'label' => 'Winnipeg, CA'],
            // Mexico
            ['lat' => 19.4326, 'lng' => -99.1332, 'label' => 'Mexico City, MX'],
            ['lat' => 20.6597, 'lng' => -103.3496, 'label' => 'Guadalajara, MX'],
            ['lat' => 25.6866, 'lng' => -100.3161, 'label' => 'Monterrey, MX'],
            ['lat' => 19.0414, 'lng' => -98.2063, 'label' => 'Puebla, MX'],
            ['lat' => 32.5149, 'lng' => -117.0382, 'label' => 'Tijuana, MX'],
            ['lat' => 21.1619, 'lng' => -86.8515, 'label' => 'Cancun, MX'],
            ['lat' => 20.9674, 'lng' => -89.5926, 'label' => 'Merida, MX'],
            // Europe
            ['lat' => 51.5074, 'lng' => -0.1278, 'label' => 'London, UK'],
            ['lat' => 48.8566, 'lng' => 2.3522, 'label' => 'Paris, FR'],
            ['lat' => 52.5200, 'lng' => 13.4050, 'label' => 'Berlin, DE'],
            ['lat' => 40.4168, 'lng' => -3.7038, 'label' => 'Madrid, ES'],
            ['lat' => 41.9028, 'lng' => 12.4964, 'label' => 'Rome, IT'],
            ['lat' => 52.3676, 'lng' => 4.9041, 'label' => 'Amsterdam, NL'],
            ['lat' => 41.3851, 'lng' => 2.1734, 'label' => 'Barcelona, ES'],
            ['lat' => 48.1351, 'lng' => 11.5820, 'label' => 'Munich, DE'],
            ['lat' => 45.4642, 'lng' => 9.1900, 'label' => 'Milan, IT'],
            ['lat' => 48.2082, 'lng' => 16.3738, 'label' => 'Vienna, AT'],
            ['lat' => 50.0755, 'lng' => 14.4378, 'label' => 'Prague, CZ'],
            ['lat' => 38.7223, 'lng' => -9.1393, 'label' => 'Lisbon, PT'],
            ['lat' => 53.3498, 'lng' => -6.2603, 'label' => 'Dublin, IE'],
            ['lat' => 55.6761, 'lng' => 12.5683, 'label' => 'Copenhagen, DK'],
            ['lat' => 59.3293, 'lng' => 18.0686, 'label' => 'Stockholm, SE'],
            ['lat' => 59.9139, 'lng' => 10.7522, 'label' => 'Oslo, NO'],
            ['lat' => 60.1699, 'lng' => 24.9384, 'label' => 'Helsinki, FI'],
            ['lat' => 50.8503, 'lng' => 4.3517, 'label' => 'Brussels, BE'],
            ['lat' => 47.3769, 'lng' => 8.5417, 'label' => 'Zurich, CH'],
            ['lat' => 52.2297, 'lng' => 21.0122, 'label' => 'Warsaw, PL'],
            ['lat' => 47.4979, 'lng' => 19.0402, 'label' => 'Budapest, HU'],
            ['lat' => 37.9838, 'lng' => 23.7275, 'label' => 'Athens, GR'],
            ['lat' => 45.7640, 'lng' => 4.8357, 'label' => 'Lyon, FR'],
            ['lat' => 53.5511, 'lng' => 9.9937, 'label' => 'Hamburg, DE'],
            ['lat' => 53.4808, 'lng' => -2.2426, 'label' => 'Manchester, UK'],
            ['lat' => 55.9533, 'lng' => -3.1883, 'label' => 'Edinburgh, UK'],
            ['lat' => 50.1109, 'lng' => 8.6821, 'label' => 'Frankfurt, DE'],
            ['lat' => 50.0647, 'lng' => 19.9450, 'label' => 'Krakow, PL'],
            ['lat' => 41.1579, 'lng' => -8.6291, 'label' => 'Porto, PT'],
            ['lat' => 40.8518, 'lng' => 14.2681, 'label' => 'Naples, IT'],
            // Global hubs
            ['lat' => 35.6762, 'lng' => 139.6503, 'label' => 'Tokyo, JP'],
            ['lat' => 37.5665, 'lng' => 126.9780, 'label' => 'Seoul, KR'],
            ['lat' => 1.3521, 'lng' => 103.8198, 'label' => 'Singapore, SG'],
            ['lat' => -33.8688, 'lng' => 151.2093, 'label' => 'Sydney, AU'],
            ['lat' => -37.8136, 'lng' => 144.9631, 'label' => 'Melbourne, AU'],
            ['lat' => 25.2048, 'lng' => 55.2708, 'label' => 'Dubai, AE'],
            ['lat' => -23.5505, 'lng' => -46.6333, 'label' => 'Sao Paulo, BR'],
            ['lat' => -34.6037, 'lng' => -58.3816, 'label' => 'Buenos Aires, AR'],
        ];
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    public static function coordinates(): array
    {
        return array_map(
            fn (array $anchor) => [$anchor['lat'], $anchor['lng']],
            self::all(),
        );
    }
}
