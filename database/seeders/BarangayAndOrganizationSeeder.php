<?php

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Models\Barangay;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class BarangayAndOrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            'BRGY-BALABAG' => 'Balabag',
            'BRGY-MANOCMANOC' => 'Manoc-Manoc',
            'BRGY-YAPAK' => 'Yapak',
        ];

        $barangayModels = [];

        foreach ($barangays as $code => $name) {
            $barangayModels[$code] = Barangay::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'municipality' => 'Malay',
                    'province' => 'Aklan',
                    'is_active' => true,
                ]
            );
        }

        $organizations = [
            [
                'code' => 'LGU-BALABAG',
                'type' => OrganizationType::BarangayLgu,
                'name' => 'Boracay Police Assistance Booth - Station 1 & 2 (Central)',
                'address' => 'White Beach Station 1-2, Boracay Island, Malay, Aklan',
                'jurisdictions' => ['BRGY-BALABAG' => true],
            ],
            [
                'code' => 'LGU-MANOCMANOC',
                'type' => OrganizationType::BarangayLgu,
                'name' => 'Boracay Police Assistance Booth - Station 3 & Cagban',
                'address' => 'White Beach Station 3 / Cagban Jetty Port, Boracay Island, Malay, Aklan',
                'jurisdictions' => ['BRGY-MANOCMANOC' => true],
            ],
            [
                'code' => 'LGU-YAPAK',
                'type' => OrganizationType::BarangayLgu,
                'name' => 'Boracay Police Assistance Booth - Puka & North Beach',
                'address' => 'Puka Shell Beach / North Coast, Boracay Island, Malay, Aklan',
                'jurisdictions' => ['BRGY-YAPAK' => true],
            ],
            [
                'code' => 'PNP-MALAY-BTAC',
                'type' => OrganizationType::PoliceStation,
                'name' => 'Malay Municipal Police Station - Boracay Tourist Assistance Center',
                'address' => 'Main Road, Boracay Island, Malay, Aklan',
                'jurisdictions' => [
                    'BRGY-BALABAG' => false,
                    'BRGY-MANOCMANOC' => false,
                    'BRGY-YAPAK' => false,
                ],
            ],
        ];

        foreach ($organizations as $data) {
            $organization = Organization::query()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'type' => $data['type'],
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'is_active' => true,
                ]
            );

            $syncData = [];
            foreach ($data['jurisdictions'] as $barangayCode => $isDefault) {
                $syncData[$barangayModels[$barangayCode]->id] = ['is_default' => $isDefault];
            }

            $organization->barangays()->sync($syncData);
        }
    }
}
