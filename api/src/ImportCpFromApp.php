<?php

declare(strict_types=1);

namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;

final class ImportCpFromApp
{
    public function import(string $path, bool $replace = true): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('Nie znaleziono pliku: ' . $path);
        }
        $book = IOFactory::load($path);
        $sheet = ExcelImportSheet::named($book, ['Plany kontroli']) ?? $book->getActiveSheet();
        $header = ExcelImportSheet::headerRow($sheet, ExcelLayout::labels(ExcelLayout::CP_REGISTER));
        if ($header === null) {
            throw new \RuntimeException('Nie rozpoznano arkusza planów kontroli w formacie eksportu aplikacji (brak nagłówków kolumn).');
        }
        $cols = ExcelImportSheet::columnMap($sheet, $header, ExcelLayout::CP_REGISTER);
        if (!isset($cols['nr_planu'])) {
            throw new \RuntimeException('Brak kolumny „Plan kontroli nr” w pliku eksportu.');
        }
        $db = Database::open();
        $db->init();
        $register = new Register($db);
        $people = [];
        foreach ($db->all('SELECT nr_fmea, osoba_wiodaca FROM fmea') as $row) {
            $nr = Support::normNr($row['nr_fmea']);
            if ($nr !== '') {
                $people[$nr] = (string) ($row['osoba_wiodaca'] ?? '');
            }
        }
        $stamp = Support::now();
        $inserted = 0;
        $linked = 0;
        $inactive = 0;
        $highest = (int) $sheet->getHighestDataRow();
        $db->pdo->beginTransaction();
        if ($replace) {
            $db->pdo->exec('DELETE FROM cp_reviews');
            $db->pdo->exec('DELETE FROM control_plans');
            $db->pdo->exec("DELETE FROM change_log WHERE entity_type = 'cp'");
            $db->pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('control_plans', 'cp_reviews')");
        }
        for ($row = $header + 1; $row <= $highest; $row++) {
            $nrPlanu = ExcelImportSheet::text($sheet, $cols, 'nr_planu', $row);
            if ($nrPlanu === '') {
                continue;
            }
            $nrFmea = Support::normNr(ExcelImportSheet::text($sheet, $cols, 'nr_fmea', $row));
            $qe = ExcelImportSheet::text($sheet, $cols, 'osoba_wiodaca', $row);
            if ($nrFmea !== '' && isset($people[$nrFmea]) && $qe === '') {
                $qe = $people[$nrFmea];
            }
            $qe = $register->rememberResponsible($qe);
            $approved = ExcelBook::parseDate(ExcelImportSheet::value($sheet, $cols, 'data_zatwierdzenia', $row));
            $revision = ExcelBook::parseDate(ExcelImportSheet::value($sheet, $cols, 'data_rewizji', $row));
            $basis = $revision ?? $approved;
            $nextDue = $basis ? Support::addMonths($basis, 12) : null;
            $warning = $basis ? Support::addMonths($basis, 11) : null;
            $status = 'OK';
            $active = 1;
            $anul = ExcelBook::parseDate(ExcelImportSheet::value($sheet, $cols, 'data_anulowania', $row));
            if ($anul !== null) {
                $active = 0;
                $status = 'ND';
            }
            if ($nrFmea !== '' && isset($people[$nrFmea])) {
                $linked++;
            }
            if ($active === 0) {
                $inactive++;
            }
            $db->exec(
                'INSERT INTO control_plans (
                    nr_planu, nr_fmea, qe, symbol_wyrobu, model, nazwa_wyrobu,
                    nr_rysunku, nr_zmiany, data_zatwierdzenia, nr_rewizji, data_rewizji,
                    status_przegladu, uwagi, linia_mes, data_anulowania,
                    last_full_date, next_due_date, warning_date,
                    last_review_date, last_review_type, last_review_reason,
                    aktywne, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $nrPlanu,
                    $nrFmea,
                    $qe,
                    ExcelImportSheet::text($sheet, $cols, 'symbol_wyrobu', $row),
                    ExcelImportSheet::text($sheet, $cols, 'model', $row),
                    ExcelImportSheet::text($sheet, $cols, 'nazwa_wyrobu', $row),
                    ExcelImportSheet::text($sheet, $cols, 'nr_rysunku', $row),
                    ExcelImportSheet::text($sheet, $cols, 'nr_zmiany', $row),
                    Support::iso($approved),
                    ExcelImportSheet::text($sheet, $cols, 'nr_rewizji', $row),
                    Support::iso($revision),
                    $status,
                    ExcelImportSheet::text($sheet, $cols, 'uwagi', $row),
                    ExcelImportSheet::text($sheet, $cols, 'linia_mes', $row),
                    Support::iso($anul),
                    Support::iso($basis),
                    Support::iso($nextDue),
                    Support::iso($warning),
                    Support::iso($basis),
                    $basis ? 'pelny' : null,
                    $basis ? 'Import z eksportu aplikacji' : null,
                    $active,
                    $stamp,
                    $stamp,
                ]
            );
            if ($basis) {
                $db->exec(
                    'INSERT INTO cp_reviews (
                        plan_id, review_date, review_type, reason,
                        next_due_after, warning_after, comment, created_at
                    ) VALUES (?, ?, \'pelny\', ?, ?, ?, \'\', ?)',
                    [$db->lastId(), Support::iso($basis), 'Import z eksportu aplikacji', Support::iso($nextDue), Support::iso($warning), $stamp]
                );
            }
            $inserted++;
        }
        $db->pdo->commit();
        return [
            'plans' => $inserted,
            'linked' => $linked,
            'inactive' => $inactive,
            'users' => $register->addedUsers(),
            'format' => 'app',
            'source' => $path,
        ];
    }
}
