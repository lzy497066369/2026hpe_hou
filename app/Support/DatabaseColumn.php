<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseColumn
{
    public static function tableExists(string $table): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return Schema::hasTable($table);
        }

        return DB::table('information_schema.tables')
            ->whereRaw('table_schema = database()')
            ->where('table_name', $table)
            ->exists();
    }

    public static function exists(string $table, string $column): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        return DB::table('information_schema.columns')
            ->whereRaw('table_schema = database()')
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }
}
