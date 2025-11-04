<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssetCategory;
use App\Models\Asset;
use App\Models\Program;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Asset Categories
        $categories = [
            [
                'name' => 'Computers & Laptops',
                'code' => 'COMP',
                'default_depreciation_rate' => 25.00,
                'depreciation_method' => 'declining_balance',
                'default_useful_life_years' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Projectors & AV Equipment',
                'code' => 'PROJ',
                'default_depreciation_rate' => 20.00,
                'depreciation_method' => 'straight_line',
                'default_useful_life_years' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Furniture',
                'code' => 'FURN',
                'default_depreciation_rate' => 10.00,
                'depreciation_method' => 'straight_line',
                'default_useful_life_years' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Vehicles',
                'code' => 'VEH',
                'default_depreciation_rate' => 20.00,
                'depreciation_method' => 'declining_balance',
                'default_useful_life_years' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Office Equipment',
                'code' => 'OFFC',
                'default_depreciation_rate' => 15.00,
                'depreciation_method' => 'straight_line',
                'default_useful_life_years' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Lab Equipment',
                'code' => 'LAB',
                'default_depreciation_rate' => 20.00,
                'depreciation_method' => 'straight_line',
                'default_useful_life_years' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $categoryData) {
            AssetCategory::firstOrCreate(
                ['code' => $categoryData['code']],
                $categoryData
            );
        }

        // Get categories for asset creation
        $compCategory = AssetCategory::where('code', 'COMP')->first();
        $projCategory = AssetCategory::where('code', 'PROJ')->first();
        $furnCategory = AssetCategory::where('code', 'FURN')->first();
        $vehCategory = AssetCategory::where('code', 'VEH')->first();
        $offcCategory = AssetCategory::where('code', 'OFFC')->first();
        $labCategory = AssetCategory::where('code', 'LAB')->first();

        // Get a program if exists
        $program = Program::first();

        // Create Sample Assets
        $assets = [
            // Computers
            [
                'asset_category_id' => $compCategory->id,
                'program_id' => $program?->id,
                'asset_tag' => 'COMP-0001',
                'name' => 'Dell Latitude 7420 Laptop',
                'description' => 'Business laptop for instructors',
                'brand' => 'Dell',
                'model' => 'Latitude 7420',
                'serial_number' => 'DL7420-2023-001',
                'purchase_price' => 3500000,
                'purchase_date' => now()->subMonths(18),
                'supplier' => 'Dell Uganda',
                'salvage_value' => 500000,
                'depreciation_rate' => 25.00,
                'depreciation_method' => 'declining_balance',
                'useful_life_years' => 4,
                'accumulated_depreciation' => 0,
                'current_book_value' => 3500000,
                'status' => 'active',
                'location' => 'Computer Lab A',
                'warranty_expiry' => now()->addMonths(6),
            ],
            [
                'asset_category_id' => $compCategory->id,
                'program_id' => $program?->id,
                'asset_tag' => 'COMP-0002',
                'name' => 'HP EliteBook 840 G8',
                'description' => 'Staff laptop',
                'brand' => 'HP',
                'model' => 'EliteBook 840 G8',
                'serial_number' => 'HP840-2023-045',
                'purchase_price' => 4200000,
                'purchase_date' => now()->subMonths(12),
                'supplier' => 'HP Uganda',
                'salvage_value' => 600000,
                'depreciation_rate' => 25.00,
                'depreciation_method' => 'declining_balance',
                'useful_life_years' => 4,
                'accumulated_depreciation' => 0,
                'current_book_value' => 4200000,
                'status' => 'active',
                'location' => 'Administration Office',
                'warranty_expiry' => now()->addYears(1),
            ],
            
            // Projectors
            [
                'asset_category_id' => $projCategory->id,
                'program_id' => $program?->id,
                'asset_tag' => 'PROJ-0001',
                'name' => 'Epson EB-2250U Projector',
                'description' => 'Main lecture hall projector',
                'brand' => 'Epson',
                'model' => 'EB-2250U',
                'serial_number' => 'EP2250-2022-101',
                'purchase_price' => 2800000,
                'purchase_date' => now()->subMonths(24),
                'supplier' => 'Epson East Africa',
                'salvage_value' => 300000,
                'depreciation_rate' => 20.00,
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 5,
                'accumulated_depreciation' => 0,
                'current_book_value' => 2800000,
                'status' => 'active',
                'location' => 'Lecture Hall 1',
                'warranty_expiry' => now()->subMonths(6),
            ],
            
            // Furniture
            [
                'asset_category_id' => $furnCategory->id,
                'asset_tag' => 'FURN-0001',
                'name' => 'Executive Office Desk',
                'description' => 'Wooden executive desk',
                'brand' => 'Office Plus',
                'model' => 'Executive Pro 2000',
                'purchase_price' => 1500000,
                'purchase_date' => now()->subYears(2),
                'supplier' => 'Office Plus Uganda',
                'salvage_value' => 200000,
                'depreciation_rate' => 10.00,
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 10,
                'accumulated_depreciation' => 0,
                'current_book_value' => 1500000,
                'status' => 'active',
                'location' => 'Director Office',
            ],
            [
                'asset_category_id' => $furnCategory->id,
                'asset_tag' => 'FURN-0002',
                'name' => 'Student Desk Set (50 units)',
                'description' => 'Classroom student desks and chairs',
                'brand' => 'School Furniture Co',
                'purchase_price' => 12500000,
                'purchase_date' => now()->subYears(3),
                'supplier' => 'School Furniture Company',
                'salvage_value' => 1000000,
                'depreciation_rate' => 10.00,
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 10,
                'accumulated_depreciation' => 0,
                'current_book_value' => 12500000,
                'status' => 'active',
                'location' => 'Classroom Block A',
            ],
            
            // Vehicles
            [
                'asset_category_id' => $vehCategory->id,
                'asset_tag' => 'VEH-0001',
                'name' => 'Toyota Hiace Minibus',
                'description' => 'Student transport vehicle',
                'brand' => 'Toyota',
                'model' => 'Hiace 2020',
                'serial_number' => 'TH2020-UAY-789',
                'purchase_price' => 95000000,
                'purchase_date' => now()->subYears(2)->subMonths(6),
                'supplier' => 'Toyota Uganda',
                'invoice_number' => 'TU-2021-4567',
                'salvage_value' => 15000000,
                'depreciation_rate' => 20.00,
                'depreciation_method' => 'declining_balance',
                'useful_life_years' => 5,
                'accumulated_depreciation' => 0,
                'current_book_value' => 95000000,
                'status' => 'active',
                'location' => 'Main Parking Lot',
                'warranty_expiry' => now()->subYears(1),
            ],
            
            // Office Equipment
            [
                'asset_category_id' => $offcCategory->id,
                'asset_tag' => 'OFFC-0001',
                'name' => 'Canon ImageRunner 2625 Printer',
                'description' => 'Multi-function printer/copier',
                'brand' => 'Canon',
                'model' => 'ImageRunner 2625',
                'serial_number' => 'CN2625-2023-234',
                'purchase_price' => 3200000,
                'purchase_date' => now()->subMonths(8),
                'supplier' => 'Canon Uganda',
                'salvage_value' => 400000,
                'depreciation_rate' => 15.00,
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 7,
                'accumulated_depreciation' => 0,
                'current_book_value' => 3200000,
                'status' => 'active',
                'location' => 'Print Room',
                'warranty_expiry' => now()->addMonths(4),
            ],
            
            // Lab Equipment
            [
                'asset_category_id' => $labCategory->id,
                'program_id' => $program?->id,
                'asset_tag' => 'LAB-0001',
                'name' => 'Digital Oscilloscope',
                'description' => 'Electronics lab oscilloscope',
                'brand' => 'Tektronix',
                'model' => 'TBS2104',
                'serial_number' => 'TK2104-2022-567',
                'purchase_price' => 4500000,
                'purchase_date' => now()->subYears(1)->subMonths(6),
                'supplier' => 'Scientific Equipment Ltd',
                'salvage_value' => 500000,
                'depreciation_rate' => 20.00,
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 5,
                'accumulated_depreciation' => 0,
                'current_book_value' => 4500000,
                'status' => 'active',
                'location' => 'Electronics Lab',
                'warranty_expiry' => now()->subMonths(3),
            ],
        ];

        foreach ($assets as $assetData) {
            $asset = Asset::create($assetData);
            // Update depreciation calculations
            $asset->updateDepreciation();
        }
    }
}
