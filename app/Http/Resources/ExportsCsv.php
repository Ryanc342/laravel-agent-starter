<?php

namespace App\Http\Resources;

/**
 * A Resource whose list can be exported as a CSV file (part of the Resource
 * part; the listing part's export option reads it). Its columns are column
 * heading => the name of one of its own fields, so the file holds what the
 * page gets, built for the same viewer. A named field must hold text, a
 * number or null.
 */
interface ExportsCsv
{
    /**
     * @return array<string, string>
     */
    public static function csvColumns(): array;
}
