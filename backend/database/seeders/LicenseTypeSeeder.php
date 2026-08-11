<?php

namespace Database\Seeders;

use App\Models\LicenseType;
use Illuminate\Database\Seeder;

class LicenseTypeSeeder extends Seeder
{
    /**
     * The legal license designations supported by the catalog.
     *
     * @var list<array{code: string, name: string, description: string}>
     */
    private const LICENSES = [
        [
            'code' => 'public_domain',
            'name' => 'Public Domain',
            'description' => 'Works whose intellectual property rights have expired or been forfeited.',
        ],
        [
            'code' => 'cc0',
            'name' => 'CC0 1.0 Universal',
            'description' => 'Dedication to the public domain with no rights reserved.',
        ],
        [
            'code' => 'cc-by',
            'name' => 'Creative Commons Attribution',
            'description' => 'Free to share and adapt with attribution.',
        ],
        [
            'code' => 'cc-by-sa',
            'name' => 'Creative Commons Attribution-ShareAlike',
            'description' => 'Free to share and adapt with attribution and share-alike terms.',
        ],
        [
            'code' => 'gfdl',
            'name' => 'GNU Free Documentation License',
            'description' => 'Copyleft license for free documentation.',
        ],
        [
            'code' => 'author_permission',
            'name' => 'Author Permission',
            'description' => 'Published with the explicit permission of the rights holder.',
        ],
    ];

    /**
     * Seed the application's license types.
     */
    public function run(): void
    {
        foreach (self::LICENSES as $license) {
            LicenseType::updateOrCreate(['code' => $license['code']], $license);
        }
    }
}
