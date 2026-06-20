<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->update([
                'name' => DB::raw("CASE WHEN name IS NULL OR name = '' THEN username ELSE name END"),
            ]);

        DB::table('users')
            ->whereNotNull('mail_code')
            ->where('mail_code', '!=', '')
            ->update([
                'email' => DB::raw("CASE WHEN email IS NULL OR email = '' THEN mail_code ELSE email END"),
            ]);

        DB::table('users')
            ->whereNotNull('work_city')
            ->where('work_city', '!=', '')
            ->update([
                'address' => DB::raw("CASE WHEN address IS NULL OR address = '' THEN work_city ELSE address END"),
            ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'username',
                'work_city',
                'mail_code',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->after('name');
            $table->string('work_city')->nullable()->after('address');
            $table->string('mail_code')->nullable()->after('work_address_code');
        });

        DB::table('users')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->update(['username' => DB::raw('name')]);

        DB::table('users')
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->update(['work_city' => DB::raw('address')]);

        DB::table('users')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->update(['mail_code' => DB::raw('email')]);
    }
};
