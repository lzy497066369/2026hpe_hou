<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
        });

        DB::table('users')
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->where(function ($query): void {
                $query->whereNull('city')->orWhere('city', '');
            })
            ->update([
                'city' => DB::raw('address'),
                'address' => null,
            ]);

        Schema::table('prize_claims', function (Blueprint $table): void {
            if (! Schema::hasColumn('prize_claims', 'pickup_address')) {
                $table->string('pickup_address')->nullable()->after('pickup_employee_no');
            }
        });
    }

    public function down(): void
    {
        DB::table('users')
            ->whereNull('address')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->update([
                'address' => DB::raw('city'),
            ]);

        Schema::table('prize_claims', function (Blueprint $table): void {
            if (Schema::hasColumn('prize_claims', 'pickup_address')) {
                $table->dropColumn('pickup_address');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'city')) {
                $table->dropColumn('city');
            }
        });
    }
};
