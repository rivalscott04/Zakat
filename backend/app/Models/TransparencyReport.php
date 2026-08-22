<?php

namespace App\Models;

use App\Enums\TransparencyReportStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasBusinessNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PRD 18O §23.
 *
 * PRD 18O §24 dan PRD 19C §6 sama-sama memakai kode RPT. Keduanya memang
 * berbagi satu urutan nomor, jadi nomornya tetap unik lintas modul.
 */
#[Fillable(['title', 'period_start', 'period_end', 'report_type', 'snapshot_id', 'document_id', 'notes'])]
class TransparencyReport extends Model
{
    use BelongsToOrganization, HasBusinessNumber, HasUlids;

    public static function businessCode(): string
    {
        return 'RPT';
    }

    public function businessNumberColumn(): string
    {
        return 'report_number';
    }

    protected function casts(): array
    {
        return [
            'status' => TransparencyReportStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(TransparencySnapshot::class, 'snapshot_id');
    }
}
