<?php

namespace App\Reports;

use App\Enums\ReportExportFormat;
use ZipArchive;

/**
 * PRD 19I §26 — penulis berkas CSV, XLSX, dan PDF.
 *
 * Ditulis sendiri memakai ZipArchive bawaan PHP agar tidak menambah dependensi
 * hanya untuk mengekspor satu tabel datar.
 *
 * ponytail: keduanya sengaja minimal. XLSX tanpa gaya, rumus, atau beberapa
 * sheet; PDF tanpa penataan kolom proporsional dan hanya font inti Helvetica
 * dengan WinAnsi. Kalau nanti butuh laporan berformat kaya, ganti dengan
 * openspout dan dompdf, antarmuka kelas ini tidak perlu berubah.
 */
class ReportFileWriter
{
    private const PDF_ROWS_PER_PAGE = 40;

    /**
     * @param  array<int, array{key: string, label: string, type: string}>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function write(ReportExportFormat $format, string $title, array $columns, array $rows): string
    {
        return match ($format) {
            ReportExportFormat::Csv => $this->csv($columns, $rows),
            ReportExportFormat::Xlsx => $this->xlsx($title, $columns, $rows),
            ReportExportFormat::Pdf => $this->pdf($title, $columns, $rows),
        };
    }

    private function csv(array $columns, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        // BOM supaya Excel membaca UTF-8 dengan benar.
        fwrite($handle, "\xEF\xBB\xBF");
        // Escape char dikosongkan: PHP 8.4 mengubah defaultnya, dan CSV standar
        // memang tidak mengenal backslash escape.
        fputcsv($handle, array_column($columns, 'label'), ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, $this->values($columns, $row), ',', '"', '');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    private function xlsx(string $title, array $columns, array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'zetra_xlsx_');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
            <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
            <Default Extension="xml" ContentType="application/xml"/>
            <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
            <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
            </Types>
            XML);

        $zip->addFromString('_rels/.rels', <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
            <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
            </Relationships>
            XML);

        $sheetName = htmlspecialchars(substr($title, 0, 28), ENT_XML1);

        $zip->addFromString('xl/workbook.xml', <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
                      xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
            <sheets><sheet name="{$sheetName}" sheetId="1" r:id="rId1"/></sheets>
            </workbook>
            XML);

        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
            <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
            </Relationships>
            XML);

        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml($columns, $rows));
        $zip->close();

        $content = file_get_contents($path);
        unlink($path);

        return $content;
    }

    private function sheetXml(array $columns, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $xml .= $this->sheetRow(1, array_column($columns, 'label'), $columns);

        foreach ($rows as $index => $row) {
            $xml .= $this->sheetRow($index + 2, $this->values($columns, $row), $columns);
        }

        return $xml.'</sheetData></worksheet>';
    }

    private function sheetRow(int $number, array $values, array $columns): string
    {
        $cells = '';

        foreach (array_values($values) as $index => $value) {
            $reference = $this->columnLetter($index).$number;
            $numeric = $number > 1 && in_array($columns[$index]['type'] ?? 'text', ['money', 'number'], true) && is_numeric($value);

            $cells .= $numeric
                ? '<c r="'.$reference.'"><v>'.$value.'</v></c>'
                : '<c r="'.$reference.'" t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
        }

        return '<row r="'.$number.'">'.$cells.'</row>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        for ($current = $index; $current >= 0; $current = intdiv($current, 26) - 1) {
            $letter = chr(65 + $current % 26).$letter;
        }

        return $letter;
    }

    private function pdf(string $title, array $columns, array $rows): string
    {
        $lines = [$title, str_repeat('=', min(strlen($title), 90)), ''];
        $width = max(1, intdiv(96, max(count($columns), 1)));

        $lines[] = $this->pdfLine(array_column($columns, 'label'), $width);
        $lines[] = str_repeat('-', min($width * count($columns), 96));

        foreach ($rows as $row) {
            $lines[] = $this->pdfLine($this->values($columns, $row), $width);
        }

        $pages = array_chunk($lines, self::PDF_ROWS_PER_PAGE);

        return $this->pdfDocument($pages === [] ? [[$title]] : $pages);
    }

    private function pdfLine(array $values, int $width): string
    {
        return implode(' ', array_map(
            fn ($value) => str_pad(mb_substr((string) $value, 0, $width - 1), $width - 1),
            array_values($values),
        ));
    }

    /** @param array<int, array<int, string>> $pages */
    private function pdfDocument(array $pages): string
    {
        $objects = [];
        $pageCount = count($pages);
        // 1 katalog, 2 pages, lalu tiap halaman dua objek: page dan content.
        $pageIds = array_map(fn (int $index) => 3 + $index * 2, range(0, $pageCount - 1));

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids ['
            .implode(' ', array_map(fn (int $id) => "{$id} 0 R", $pageIds))
            ."] /Count {$pageCount} >>";

        $fontId = 3 + $pageCount * 2;

        foreach ($pages as $index => $lines) {
            $pageId = $pageIds[$index];
            $contentId = $pageId + 1;

            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] '
                ."/Resources << /Font << /F1 {$fontId} 0 R >> >> /Contents {$contentId} 0 R >>";

            $stream = "BT /F1 8 Tf 30 560 Td 10 TL\n";

            foreach ($lines as $line) {
                $stream .= '('.$this->pdfEscape($line).") Tj T*\n";
            }

            $stream .= 'ET';

            $objects[$contentId] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
        }

        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        for ($id = 1; $id <= $fontId; $id++) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$objects[$id]}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref'."\n0 ".($fontId + 1)."\n0000000000 65535 f \n";

        for ($id = 1; $id <= $fontId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        return $pdf.'trailer'."\n<< /Size ".($fontId + 1)." /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";
    }

    private function pdfEscape(string $value): string
    {
        $latin = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $latin);
    }

    /** @return array<int, string> */
    private function values(array $columns, array $row): array
    {
        return array_map(fn (array $column) => (string) ($row[$column['key']] ?? ''), $columns);
    }
}
