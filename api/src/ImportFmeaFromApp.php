<?php

declare(strict_types=1);

namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;

final class ImportFmeaFromApp
{
    public function import(string $path, bool $replace = true): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('Nie znaleziono pliku: ' . $path);
        }
        $book = IOFactory::load($path);
        $sheet = ExcelImportSheet::named($book, ['Rejestr FMEA']) ?? $book->getActiveSheet();
        $header = ExcelImportSheet::headerRow($sheet, ExcelLayout::labels(ExcelLayout::FMEA_REGISTER));
        if ($header === null) {
            throw new \RuntimeException('Nie rozpoznano arkusza rejestru FMEA w formacie eksportu aplikacji (brak nagłówków kolumn).');
        }
        $cols = ExcelImportSheet::columnMap($sheet, $header, ExcelLayout::FMEA_REGISTER);
        if (!isset($cols['nr_fmea'])) {
            throw new \RuntimeException('Brak kolumny „Nr FMEA” w pliku eksportu.');
        }
        $historyByNr = $this->loadHistory($book);
        $db = Database::open();
        $db->init();
        $register = new Register($db);
        $stamp = Support::now();
        $inserted = 0;
        $reviews = 0;
        $highest = (int) $sheet->getHighestDataRow();
        $db->pdo->beginTransaction();
        if ($replace) {
            $db->pdo->exec('DELETE FROM reviews');
            $db->pdo->exec('DELETE FROM fmea');
            $db->pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('fmea', 'reviews')");
        }
        for ($row = $header + 1; $row <= $highest; $row++) {
            $nr = ExcelImportSheet::text($sheet, $cols, 'nr_fmea', $row);
            if ($nr === '') {
                continue;
            }
            $nr = rtrim($nr, '.');
            $osoba = $register->rememberResponsible(ExcelImportSheet::text($sheet, $cols, 'osoba_wiodaca', $row));
            $uwagi = ExcelImportSheet::text($sheet, $cols, 'uwagi', $row);
            $part = ExcelImportSheet::text($sheet, $cols, 'part_numbers', $row);
            $projekt = ExcelImportSheet::text($sheet, $cols, 'projekt', $row);
            $rodzaj = ExcelImportSheet::text($sheet, $cols, 'rodzaj_wyrobu', $row);
            $rows = $historyByNr[Support::normNr($nr)] ?? $historyByNr[$nr] ?? [];
            if ($rows === []) {
                $rows = $this->syntheticReviews($sheet, $cols, $row);
            }
            usort($rows, static fn (array $a, array $b) => $a['review_date'] <=> $b['review_date']);
            $last = $rows === [] ? null : $rows[array_key_last($rows)];
            $lastDate = $last['review_date'] ?? null;
            $nextDue = $lastDate ? Support::addMonths($lastDate, 12) : null;
            $warning = $lastDate ? Support::addMonths($lastDate, 11) : null;
            $db->exec(
                'INSERT INTO fmea (
                    nr_fmea, part_numbers, projekt, rodzaj_wyrobu, osoba_wiodaca,
                    last_full_date, next_due_date, warning_date,
                    last_review_date, last_review_type, last_review_reason,
                    aktywne, uwagi, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $nr,
                    $part,
                    $projekt,
                    $rodzaj,
                    $osoba,
                    Support::iso($lastDate),
                    Support::iso($nextDue),
                    Support::iso($warning),
                    $last ? Support::iso($last['review_date']) : null,
                    $last['review_type'] ?? null,
                    $last['reason'] ?? null,
                    1,
                    $uwagi,
                    $stamp,
                    $stamp,
                ]
            );
            $id = $db->lastId();
            $inserted++;
            foreach ($rows as $review) {
                $db->exec(
                    'INSERT INTO reviews (
                        fmea_id, review_date, review_type, reason,
                        next_due_after, warning_after, comment, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $id,
                        Support::iso($review['review_date']),
                        $review['review_type'],
                        $review['reason'],
                        Support::iso($review['due']),
                        Support::iso($review['warning']),
                        $review['comment'],
                        $stamp,
                    ]
                );
                $reviews++;
            }
        }
        $db->pdo->commit();
        return [
            'fmea' => $inserted,
            'reviews' => $reviews,
            'users' => $register->addedUsers(),
            'format' => 'app',
            'source' => $path,
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function loadHistory(\PhpOffice\PhpSpreadsheet\Spreadsheet $book): array
    {
        $sheet = ExcelImportSheet::named($book, ['Historia przeglądów']);
        if ($sheet === null) {
            return [];
        }
        $header = ExcelImportSheet::headerRow($sheet, ExcelLayout::labels(ExcelLayout::FMEA_HISTORY));
        if ($header === null || !isset(ExcelImportSheet::columnMap($sheet, $header, ExcelLayout::FMEA_HISTORY)['nr_fmea'])) {
            return [];
        }
        $cols = ExcelImportSheet::columnMap($sheet, $header, ExcelLayout::FMEA_HISTORY);
        $groups = [];
        $highest = (int) $sheet->getHighestDataRow();
        for ($row = $header + 1; $row <= $highest; $row++) {
            $nr = ExcelImportSheet::text($sheet, $cols, 'nr_fmea', $row);
            if ($nr === '') {
                continue;
            }
            $nr = rtrim($nr, '.');
            $reviewDate = ExcelBook::parseDate(ExcelImportSheet::value($sheet, $cols, 'review_date', $row));
            if ($reviewDate === null) {
                continue;
            }
            $due = ExcelBook::parseDate(ExcelImportSheet::value($sheet, $cols, 'next_due_after', $row))
                ?? Support::addMonths($reviewDate, 12);
            $warning = Support::addMonths($reviewDate, 11);
            $typeLabel = ExcelImportSheet::text($sheet, $cols, 'review_type', $row);
            $key = Support::normNr($nr);
            $groups[$key][] = [
                'review_date' => $reviewDate,
                'review_type' => ExcelLayout::reviewTypeKey($typeLabel),
                'reason' => ExcelImportSheet::text($sheet, $cols, 'reason', $row) ?: 'Import z eksportu',
                'warning' => $warning,
                'due' => $due,
                'comment' => ExcelImportSheet::text($sheet, $cols, 'comment', $row),
            ];
        }
        return $groups;
    }

    /**
     * @param array<string, int> $cols
     * @return list<array<string, mixed>>
     */
    private function syntheticReviews(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $cols, int $row): array
    {
        $reviewDate = ExcelBook::parseDate(ExcelImportSheet::value($sheet, $cols, 'last_full_date', $row))
            ?? ExcelBook::parseDate(ExcelImportSheet::value($sheet, $cols, 'current_fmea_date', $row));
        if ($reviewDate === null) {
            return [];
        }
        $due = ExcelBook::parseDate(ExcelImportSheet::value($sheet, $cols, 'next_due_date', $row))
            ?? Support::addMonths($reviewDate, 12);
        $warning = Support::addMonths($reviewDate, 11);
        return [[
            'review_date' => $reviewDate,
            'review_type' => 'pelny',
            'reason' => ExcelImportSheet::text($sheet, $cols, 'last_review_reason', $row) ?: 'Import z eksportu',
            'warning' => $warning,
            'due' => $due,
            'comment' => '',
        ]];
    }
}
