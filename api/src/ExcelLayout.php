<?php

declare(strict_types=1);

namespace App;

/** Kolumny zgodne z ExcelExport — import i eksport używają tych samych etykiet. */
final class ExcelLayout
{
    /** @var list<array{0: string, 1: string, 2: int}> */
    public const FMEA_REGISTER = [
        ['nr_fmea', 'Nr FMEA', 12],
        ['part_numbers', 'Wyroby gotowe (Part number)', 42],
        ['projekt', 'Projekt', 18],
        ['rodzaj_wyrobu', 'Rodzaj wyrobu', 26],
        ['osoba_wiodaca', 'Osoba wiodąca', 18],
        ['current_fmea_date', 'Data obecnego FMEA', 18],
        ['last_review_reason', 'Powód ost. rewizji', 34],
        ['next_due_date', 'Termin kolejnej rewizji', 20],
        ['status', 'Status', 18],
        ['last_full_date', 'Ostatni pełny FMEA', 18],
        ['days_since_full', 'Dni od pełnego FMEA', 19],
        ['uwagi', 'Uwagi', 30],
    ];

    /** @var list<array{0: string, 1: string, 2: int}> */
    public const FMEA_HISTORY = [
        ['nr_fmea', 'Nr FMEA', 12],
        ['projekt', 'Projekt', 18],
        ['review_date', 'Data przeglądu', 15],
        ['review_type', 'Typ przeglądu', 15],
        ['reason', 'Powód rewizji', 40],
        ['next_due_after', 'Termin po przeglądzie', 20],
        ['comment', 'Komentarz', 30],
    ];

    /** @var list<array{0: string, 1: string, 2: int}> */
    public const CP_REGISTER = [
        ['nr_planu', 'Plan kontroli nr', 22],
        ['nr_fmea', 'Numer FMEA', 14],
        ['osoba_wiodaca', 'Odpowiedzialny QE', 20],
        ['symbol_wyrobu', 'Symbol wyrobu', 22],
        ['model', 'Model samochodu', 22],
        ['nr_rysunku', 'Nr rysunku lub części', 24],
        ['linia_mes', 'Linia MES', 16],
        ['data_rewizji', 'Data rewizji', 16],
        ['next_due_date', 'Termin kolejnej rewizji', 22],
        ['status', 'Status', 18],
        ['nazwa_wyrobu', 'Nazwa wyrobu', 28],
        ['nr_zmiany', 'Nr zmiany', 16],
        ['data_zatwierdzenia', 'Data zatwierdzenia', 18],
        ['nr_rewizji', 'Nr rewizji', 14],
        ['uwagi', 'Uwagi', 28],
        ['data_anulowania', 'Data anulowania', 16],
    ];

    /** @var list<array{0: string, 1: string, 2: int}> */
    public const CP_HISTORY = [
        ['nr_planu', 'Plan kontroli nr', 22],
        ['nr_fmea', 'Numer FMEA', 14],
        ['review_date', 'Data rewizji', 16],
        ['review_type', 'Typ', 14],
        ['reason', 'Powód rewizji', 36],
        ['next_due_after', 'Termin po zapisie', 20],
    ];

    /** @return list<string> */
    public static function labels(array $layout): array
    {
        return array_map(static fn (array $col) => $col[1], $layout);
    }

    public static function reviewTypeKey(string $label): string
    {
        $fold = Support::fold($label);
        if (str_contains($fold, 'zdarzeni')) {
            return 'zdarzeniowy';
        }
        return 'pelny';
    }
}
