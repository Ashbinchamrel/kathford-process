<?php

namespace App\Services\Planning;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StrategyMatrix
{
    public static function validate(array $data, array $rows): void
    {
        Validator::make(['matrix' => $data, 'rows' => $rows], [
            'matrix.starts_on' => 'required|date_format:Y-m-d', 'matrix.ends_on' => 'required|date_format:Y-m-d|after:matrix.starts_on',
            'matrix.semesters' => 'required|array|list|min:1|max:12', 'matrix.semesters.*.label' => 'required|string|max:100',
            'matrix.semesters.*.starts_on' => 'required|date_format:Y-m-d', 'matrix.semesters.*.ends_on' => 'required|date_format:Y-m-d',
            'rows' => 'required|array|list|min:1|max:200', 'rows.*.priority' => 'required|string|max:2000', 'rows.*.goal' => 'nullable|string|max:5000', 'rows.*.strategy' => 'nullable|string|max:5000',
            'rows.*.targets' => 'required|array|list|max:12', 'rows.*.targets.*' => 'nullable|string|max:5000',
        ])->validate();
        $last = null;
        foreach ($data['semesters'] as $s) {
            if ($s['starts_on'] < $data['starts_on'] || $s['ends_on'] > $data['ends_on'] || $s['starts_on'] > $s['ends_on'] || ($last && $s['starts_on'] <= $last)) {
                self::fail('Semester dates must be in order, not overlap, and fall inside the Strategic Plan period.');
            }
            $last = $s['ends_on'];
        }
        foreach ($rows as $row) {
            if (count($row['targets']) !== count($data['semesters'])) {
                self::fail('Each strategy row must have one target cell per semester. Empty target cells are allowed.');
            }
        }
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['strategy' => $message]);
    }
}
