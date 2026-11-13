<?php

declare(strict_types=1);

namespace App;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ExcelImportSheet
{
    /** @param list<string> $titles */
    public static function named(Spreadsheet $book, array $titles): ?Worksheet
    {
        foreach ($book->getWorksheetIterator() as $sheet) {
            foreach ($titles as $title) {
                if (Support::fold($sheet->getTitle()) === Support::fold($title)) {
                    return $sheet;
                }
            }
        }
        return null;
    }

    /** @param list<string> $labels */
    public static function headerRow(Worksheet $sheet, array $labels, int $maxRow = 25): ?int
    {
        $folded = array_map(static fn (string $label) => Support::fold($label), $labels);
        $need = min(4, count($folded));
        for ($row = 1; $row <= $maxRow; $row++) {
            $hits = 0;
            $highest = Coordinate::columnIndexFromString($sheet->getHighestDataColumn($row));
            for ($col = 1; $col <= $highest; $col++) {
                $text = Support::fold(ExcelBook::clean(self::readCell($sheet, $col, $row)));
                if ($text !== '' && in_array($text, $folded, true)) {
                    $hits++;
                }
            }
            if ($hits >= $need) {
                return $row;
            }
        }
        return null;
    }

    /**
     * @param list<array{0: string, 1: string, 2: int}> $layout
     * @return array<string, int>
     */
    public static function columnMap(Worksheet $sheet, int $headerRow, array $layout): array
    {
        $byLabel = [];
        foreach ($layout as [$key, $label]) {
            $byLabel[Support::fold($label)] = $key;
        }
        $map = [];
        $highest = Coordinate::columnIndexFromString($sheet->getHighestDataColumn($headerRow));
        for ($col = 1; $col <= $highest; $col++) {
            $text = Support::fold(ExcelBook::clean(self::readCell($sheet, $col, $headerRow)));
            if ($text !== '' && isset($byLabel[$text])) {
                $map[$byLabel[$text]] = $col;
            }
        }
        return $map;
    }

    /** @param array<string, int> $map */
    public static function value(Worksheet $sheet, array $map, string $key, int $row): mixed
    {
        if (!isset($map[$key])) {
            return null;
        }
        return self::readCell($sheet, $map[$key], $row);
    }

    /** @param array<string, int> $map */
    public static function text(Worksheet $sheet, array $map, string $key, int $row): string
    {
        return ExcelBook::clean(self::value($sheet, $map, $key, $row));
    }

    private static function readCell(Worksheet $sheet, int $col, int $row): mixed
    {
        return ExcelBook::cellValue($sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row));
    }
}
