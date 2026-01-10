<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocumentTemplate;

class DocumentTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'type' => 'invoice',
                'key' => 'invoice_default',
                'name' => 'Default Invoice Template',
                'blade_view' => 'pdf.invoice.default',
                'description' => 'Standard invoice template with school branding',
                'is_active' => true,
            ],
            [
                'type' => 'voucher',
                'key' => 'voucher_default',
                'name' => 'Default Voucher/Challan Template',
                'blade_view' => 'pdf.voucher.default',
                'description' => 'Standard payment challan/voucher template',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            DocumentTemplate::firstOrCreate(
                ['key' => $template['key']],
                $template
            );
        }

        $this->command->info('Document templates seeded successfully');
    }
}
