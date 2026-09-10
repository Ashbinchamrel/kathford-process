<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class VendorRateImport
{
    public const HEADERS = ['item_name', 'unit', 'unit_rate', 'valid_from', 'valid_until', 'specification', 'is_active'];

    public function read(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path, [IOFactory::READER_XLSX, IOFactory::READER_XLS, IOFactory::READER_CSV]);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);
        try {
            $sheet = $book->getSheet(0);
            if ($sheet->getHighestDataRow() > 1001) {
                throw ValidationException::withMessages(['rates_file' => 'Upload at most 1,000 rates at a time.']);
            }
            $rows = $sheet->toArray(null, false, false, false);
            $headers = array_map(fn($v) => strtolower(trim((string) $v)), array_shift($rows) ?? []);
            if (array_diff(array_slice(self::HEADERS, 0, 5), $headers) || count($headers) !== count(array_unique($headers))) {
                throw ValidationException::withMessages(['rates_file' => 'Use the template headers, including item_name, unit, unit_rate, valid_from and valid_until.']);
            }
            $result = []; $errors = []; $seen = [];
            foreach ($rows as $index => $values) {
                if (!count(array_filter($values, fn($v) => $v !== null && $v !== ''))) continue;
                $data = [];
                foreach ($headers as $column => $header) {
                    if (in_array($header, self::HEADERS, true)) $data[$header] = is_string($values[$column] ?? null) ? trim($values[$column]) : ($values[$column] ?? null);
                }
                foreach (['valid_from', 'valid_until'] as $field) {
                    if (is_numeric($data[$field] ?? null)) $data[$field] = Date::excelToDateTimeObject($data[$field])->format('Y-m-d');
                    if (($data[$field] ?? '') === '') $data[$field] = null;
                }
                $active = strtolower((string) ($data['is_active'] ?? '1'));
                $data['is_active'] = match ($active) { '', '1', 'yes', 'true' => true, '0', 'no', 'false' => false, default => $active };
                $validator = Validator::make($data, [
                    'item_name'=>'required|string|max:255', 'unit'=>'required|string|max:50',
                    'unit_rate'=>'required|numeric|min:0.01|max:9999999999999.99',
                    'valid_from'=>'required|date_format:Y-m-d', 'valid_until'=>'required|date_format:Y-m-d|after_or_equal:valid_from',
                    'specification'=>'nullable|string|max:3000', 'is_active'=>'required|boolean',
                ]);
                $row = $index + 2;
                foreach ($validator->errors()->all() as $message) $errors[] = "Row {$row}: {$message}";
                $key = strtolower(json_encode([$data['item_name'] ?? '', $data['unit'] ?? '', $data['valid_from'] ?? '', $data['valid_until'] ?? '']));
                if (isset($seen[$key])) $errors[] = "Row {$row}: duplicate item, unit and validity period.";
                $seen[$key] = true;
                $result[] = $data;
            }
            if ($errors) throw ValidationException::withMessages(['rates_file' => array_slice($errors, 0, 30)]);
            if (!$result) throw ValidationException::withMessages(['rates_file' => 'The file contains no rates.']);
            return $result;
        } finally {
            $book->disconnectWorksheets();
        }
    }
}
