<?php

namespace App\Services\Planning;

class Dates
{
    public static function bs(?string $ad): string
    {
        if (! $ad) {
            return '';
        }try {
            $date = new \DateTimeImmutable(substr($ad, 0, 10), new \DateTimeZone('UTC'));
        } catch (\Throwable) {
            return '';
        }
        $epoch = new \DateTimeImmutable('1943-04-14', new \DateTimeZone('UTC'));
        $days = (int) $epoch->diff($date)->format('%r%a');
        if ($days < 0) {
            return '';
        }
        static $map;
        $map ??= json_decode(file_get_contents(resource_path('data/nepali-months.json')), true);
        foreach ($map as $year => $months) {
            foreach ($months as $index => $length) {
                if ($days < $length) {
                    return sprintf('%d-%02d-%02d BS', $year, $index + 1, $days + 1);
                }$days -= $length;
            }
        }

        return '';
    }
}
