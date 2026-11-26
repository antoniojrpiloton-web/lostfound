<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $barangays = Barangay::query()->pluck('id', 'code');
        $organizations = Organization::query()->pluck('id', 'code');

        $locations = [
            ['barangay' => 'BRGY-BALABAG', 'org' => 'LGU-BALABAG', 'name' => 'Station 1 Beachfront', 'type' => 'beach', 'lat' => 11.9690000, 'lng' => 121.9212000],
            ['barangay' => 'BRGY-BALABAG', 'org' => 'LGU-BALABAG', 'name' => 'Station 2 Beachfront', 'type' => 'beach', 'lat' => 11.9615000, 'lng' => 121.9255000],
            ['barangay' => 'BRGY-BALABAG', 'org' => 'LGU-BALABAG', 'name' => "D'Mall de Boracay", 'type' => 'commercial', 'lat' => 11.9628000, 'lng' => 121.9267000],
            ['barangay' => 'BRGY-BALABAG', 'org' => 'LGU-BALABAG', 'name' => 'Bulabog Beach', 'type' => 'beach', 'lat' => 11.9647000, 'lng' => 121.9308000],
            ['barangay' => 'BRGY-BALABAG', 'org' => 'PNP-MALAY-BTAC', 'name' => 'Boracay Tourist Assistance Center Area', 'type' => 'landmark', 'lat' => 11.9640000, 'lng' => 121.9261000],
            ['barangay' => 'BRGY-MANOCMANOC', 'org' => 'LGU-MANOCMANOC', 'name' => 'Station 3 Beachfront', 'type' => 'beach', 'lat' => 11.9535000, 'lng' => 121.9304000],
            ['barangay' => 'BRGY-MANOCMANOC', 'org' => 'LGU-MANOCMANOC', 'name' => 'Cagban Jetty Port', 'type' => 'port', 'lat' => 11.9396000, 'lng' => 121.9467000],
            ['barangay' => 'BRGY-MANOCMANOC', 'org' => 'LGU-MANOCMANOC', 'name' => 'Tambisaan Jetty Port', 'type' => 'port', 'lat' => 11.9451000, 'lng' => 121.9502000],
            ['barangay' => 'BRGY-MANOCMANOC', 'org' => 'LGU-MANOCMANOC', 'name' => 'Tulubhan Beach', 'type' => 'beach', 'lat' => 11.9524000, 'lng' => 121.9435000],
            ['barangay' => 'BRGY-YAPAK', 'org' => 'LGU-YAPAK', 'name' => 'Puka Shell Beach', 'type' => 'beach', 'lat' => 11.9953000, 'lng' => 121.9126000],
            ['barangay' => 'BRGY-YAPAK', 'org' => 'LGU-YAPAK', 'name' => 'Ilig-Iligan Beach', 'type' => 'beach', 'lat' => 11.9888000, 'lng' => 121.9267000],
            ['barangay' => 'BRGY-YAPAK', 'org' => 'LGU-YAPAK', 'name' => 'Diniwid Beach', 'type' => 'beach', 'lat' => 11.9765000, 'lng' => 121.9114000],
            ['barangay' => 'BRGY-YAPAK', 'org' => 'LGU-YAPAK', 'name' => 'CityMall Boracay', 'type' => 'commercial', 'lat' => 11.9789000, 'lng' => 121.9175000],
        ];

        foreach ($locations as $loc) {
            Location::query()->updateOrCreate(
                [
                    'barangay_id' => $barangays[$loc['barangay']],
                    'name' => $loc['name'],
                ],
                [
                    'organization_id' => $organizations[$loc['org']] ?? null,
                    'type' => $loc['type'],
                    'latitude' => $loc['lat'],
                    'longitude' => $loc['lng'],
                    'is_active' => true,
                ]
            );
        }
    }
}
