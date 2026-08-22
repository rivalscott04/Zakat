<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\Report;
use App\Models\ReportParameter;
use App\Reports\ReportRegistry;
use Illuminate\Database\Seeder;

/**
 * PRD 19C §7 — laporan bawaan sistem dibuat sekali dengan organization_id NULL
 * supaya kodenya stabil dan sama untuk semua organisasi.
 */
class ReportCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ReportRegistry::REPORTS as $code => $definition) {
            $report = Report::query()->whereNull('organization_id')->where('report_code', $code)->first() ?? new Report;

            $report->fill([
                'report_code' => $code,
                'name' => $definition['name'],
                'description' => $definition['description'],
                'category' => $definition['category']->value,
                'report_type' => 'TABULAR',
                'data_source' => $definition['data_source'],
                'visibility' => $definition['visibility']->value,
            ]);
            $report->organization_id = null;
            $report->status = EntityStatus::Active;
            $report->is_system = true;
            $report->save();

            foreach ($definition['parameters'] as $order => $parameter) {
                ReportParameter::query()->updateOrCreate(
                    ['report_id' => $report->getKey(), 'parameter_code' => $parameter['parameter_code']],
                    [
                        'label' => $parameter['label'],
                        'type' => $parameter['type']->value,
                        'required' => $parameter['required'] ?? false,
                        'default_value' => $parameter['default_value'] ?? null,
                        'options_source' => $parameter['options_source'] ?? null,
                        'sort_order' => $order,
                    ],
                );
            }
        }
    }
}
