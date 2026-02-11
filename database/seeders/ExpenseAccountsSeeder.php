<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ExpenseAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenseAccounts = [
            // 5000 – Administrative Expenses
            ['code' => '5000', 'name' => 'Office Rent', 'type' => 'expense', 'description' => 'Monthly office rental expenses'],
            ['code' => '5001', 'name' => 'Utilities', 'type' => 'expense', 'description' => 'Electricity, water, and other utilities'],
            ['code' => '5002', 'name' => 'Internet & Communication', 'type' => 'expense', 'description' => 'Internet, phone, and communication costs'],
            ['code' => '5003', 'name' => 'Office Supplies & Stationery', 'type' => 'expense', 'description' => 'Pens, paper, and office consumables'],
            ['code' => '5004', 'name' => 'Printing & Photocopying', 'type' => 'expense', 'description' => 'Printing and copying services'],
            ['code' => '5005', 'name' => 'Repairs & Maintenance (General)', 'type' => 'expense', 'description' => 'General repairs and maintenance'],
            ['code' => '5006', 'name' => 'Cleaning & Sanitation', 'type' => 'expense', 'description' => 'Cleaning services and supplies'],
            ['code' => '5007', 'name' => 'Insurance', 'type' => 'expense', 'description' => 'Insurance premiums'],
            ['code' => '5008', 'name' => 'Licenses & Permits', 'type' => 'expense', 'description' => 'Business licenses and permits'],
            ['code' => '5009', 'name' => 'Bank Charges', 'type' => 'expense', 'description' => 'Bank fees and charges'],

            // 5100 – Staff & Facilitator Costs
            ['code' => '5100', 'name' => 'Salaries & Wages', 'type' => 'expense', 'description' => 'Employee salaries and wages'],
            ['code' => '5101', 'name' => 'Facilitator Payments', 'type' => 'expense', 'description' => 'Payments to trainers and facilitators'],
            ['code' => '5102', 'name' => 'NSSF & PAYE Contributions', 'type' => 'expense', 'description' => 'Statutory employee contributions'],
            ['code' => '5103', 'name' => 'Staff Welfare', 'type' => 'expense', 'description' => 'Staff welfare and benefits'],
            ['code' => '5104', 'name' => 'Staff Training & Capacity Building', 'type' => 'expense', 'description' => 'Employee training and development'],
            ['code' => '5105', 'name' => 'Staff Transport Allowance', 'type' => 'expense', 'description' => 'Staff transportation allowances'],
            ['code' => '5106', 'name' => 'Team Building & Staff Retreats', 'type' => 'expense', 'description' => 'Team building activities and retreats'],

            // 5200 – Program Expenses
            ['code' => '5200', 'name' => 'Training Materials', 'type' => 'expense', 'description' => 'Educational and training materials'],
            ['code' => '5201', 'name' => 'Software & Licenses', 'type' => 'expense', 'description' => 'Software licenses for programs'],
            ['code' => '5202', 'name' => 'Venue & Equipment Hire', 'type' => 'expense', 'description' => 'Venue and equipment rental'],
            ['code' => '5203', 'name' => 'Meals & Refreshments (Students)', 'type' => 'expense', 'description' => 'Student meals and refreshments'],
            ['code' => '5204', 'name' => 'Certificates & Printing', 'type' => 'expense', 'description' => 'Certificates and program printing'],
            ['code' => '5205', 'name' => 'Prizes & Awards', 'type' => 'expense', 'description' => 'Student prizes and awards'],
            ['code' => '5206', 'name' => 'Program Logistics', 'type' => 'expense', 'description' => 'Program logistics and coordination'],
            ['code' => '5207', 'name' => 'Internet for Labs', 'type' => 'expense', 'description' => 'Internet connectivity for labs'],
            ['code' => '5208', 'name' => 'Curriculum Development', 'type' => 'expense', 'description' => 'Curriculum design and development'],

            // 5300 – Marketing & Outreach
            ['code' => '5300', 'name' => 'Advertising & Promotions', 'type' => 'expense', 'description' => 'Advertising and promotional activities'],
            ['code' => '5301', 'name' => 'Social Media Marketing', 'type' => 'expense', 'description' => 'Social media advertising and content'],
            ['code' => '5302', 'name' => 'Public Relations & Branding', 'type' => 'expense', 'description' => 'PR and brand development'],
            ['code' => '5303', 'name' => 'Photography & Videography', 'type' => 'expense', 'description' => 'Photography and video services'],
            ['code' => '5304', 'name' => 'Website Hosting & Maintenance', 'type' => 'expense', 'description' => 'Website hosting and maintenance'],
            ['code' => '5305', 'name' => 'School Outreach & Engagements', 'type' => 'expense', 'description' => 'School visits and outreach programs'],

            // 5400 – Transport & Travel
            ['code' => '5400', 'name' => 'Fuel & Lubricants', 'type' => 'expense', 'description' => 'Vehicle fuel and lubricants'],
            ['code' => '5401', 'name' => 'Vehicle Repairs & Maintenance', 'type' => 'expense', 'description' => 'Vehicle repairs and servicing'],
            ['code' => '5402', 'name' => 'Driver Allowances', 'type' => 'expense', 'description' => 'Driver allowances and wages'],
            ['code' => '5403', 'name' => 'Transport for Events', 'type' => 'expense', 'description' => 'Transportation for events and activities'],
            ['code' => '5404', 'name' => 'Travel & Accommodation', 'type' => 'expense', 'description' => 'Travel and accommodation expenses'],

            // 5500 – ICT & Technical Infrastructure
            ['code' => '5500', 'name' => 'Computer & Equipment Maintenance', 'type' => 'expense', 'description' => 'Computer repairs and maintenance'],
            ['code' => '5501', 'name' => 'Software Subscriptions', 'type' => 'expense', 'description' => 'Software subscriptions and licenses'],
            ['code' => '5502', 'name' => 'Website & System Development', 'type' => 'expense', 'description' => 'Website and system development'],
            ['code' => '5503', 'name' => 'Hosting & Cloud Services', 'type' => 'expense', 'description' => 'Cloud hosting and services'],
            ['code' => '5504', 'name' => 'Internet Devices & Accessories', 'type' => 'expense', 'description' => 'Modems, routers, and accessories'],

            // 5600 – Events & Competitions
            ['code' => '5600', 'name' => 'Event Coordination', 'type' => 'expense', 'description' => 'Event planning and coordination'],
            ['code' => '5601', 'name' => 'Stage & Sound', 'type' => 'expense', 'description' => 'Stage setup and sound systems'],
            ['code' => '5602', 'name' => 'Decorations & Branding', 'type' => 'expense', 'description' => 'Event decorations and branding'],
            ['code' => '5603', 'name' => 'Judges\' & Jury Allowances', 'type' => 'expense', 'description' => 'Judges and jury payments'],
            ['code' => '5604', 'name' => 'Media Coverage', 'type' => 'expense', 'description' => 'Media coverage and publicity'],
            ['code' => '5605', 'name' => 'Refreshments', 'type' => 'expense', 'description' => 'Event refreshments and catering'],
            ['code' => '5606', 'name' => 'Guest Tokens & Gifts', 'type' => 'expense', 'description' => 'Guest gifts and tokens of appreciation'],

            // 5700 – Professional Services
            ['code' => '5700', 'name' => 'Audit & Accounting Fees', 'type' => 'expense', 'description' => 'External audit and accounting services'],
            ['code' => '5701', 'name' => 'Consultancy Fees', 'type' => 'expense', 'description' => 'Consultant and advisory fees'],
            ['code' => '5702', 'name' => 'Legal Fees', 'type' => 'expense', 'description' => 'Legal services and counsel'],
            ['code' => '5703', 'name' => 'Interest Expense', 'type' => 'expense', 'description' => 'Interest on loans and financing'],
            ['code' => '5704', 'name' => 'Exchange / Transaction Charges', 'type' => 'expense', 'description' => 'Foreign exchange and transaction fees'],

            // 5800 – Asset & Depreciation
            ['code' => '5800', 'name' => 'Depreciation Expense', 'type' => 'expense', 'description' => 'Asset depreciation charges'],
            ['code' => '5801', 'name' => 'Asset Maintenance', 'type' => 'expense', 'description' => 'Fixed asset maintenance and repairs'],
            ['code' => '5802', 'name' => 'Asset Tagging & Inventory', 'type' => 'expense', 'description' => 'Asset tracking and inventory management'],

            // 5900 – Taxes & Statutory Payments
            ['code' => '5900', 'name' => 'Corporate Taxes', 'type' => 'expense', 'description' => 'Corporate income tax'],
            ['code' => '5901', 'name' => 'Withholding Taxes', 'type' => 'expense', 'description' => 'Withholding tax payments'],
            ['code' => '5902', 'name' => 'Trading License / Local Taxes', 'type' => 'expense', 'description' => 'Trading licenses and local government taxes'],
            ['code' => '5903', 'name' => 'NSSF Employer Contribution', 'type' => 'expense', 'description' => 'Employer NSSF contributions'],

            // 6000 – Miscellaneous
            ['code' => '6000', 'name' => 'Donations & CSR', 'type' => 'expense', 'description' => 'Corporate social responsibility and donations'],
            ['code' => '6001', 'name' => 'Bad Debts / Write-offs', 'type' => 'expense', 'description' => 'Bad debts and write-offs'],
            ['code' => '6002', 'name' => 'Miscellaneous Expenses', 'type' => 'expense', 'description' => 'Other miscellaneous expenses'],
            ['code' => '6003', 'name' => 'Exchange Rate Differences', 'type' => 'expense', 'description' => 'Foreign exchange gains/losses'],
        ];

        foreach ($expenseAccounts as $account) {
            Account::updateOrCreate(
                ['code' => $account['code']],
                $account
            );
        }

        $this->command->info('✓ Created ' . count($expenseAccounts) . ' expense accounts (5000-6003)');
    }
}
