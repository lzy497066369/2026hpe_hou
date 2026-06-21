<?php

use App\Support\DatabaseColumn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! DatabaseColumn::exists('users', 'claim_type')) {
                $table->string('claim_type')->nullable()->after('work_address_code');
            }

            if (! DatabaseColumn::exists('users', 'receiver_name')) {
                $table->string('receiver_name')->nullable()->after('claim_type');
            }

            if (! DatabaseColumn::exists('users', 'receiver_phone')) {
                $table->string('receiver_phone')->nullable()->after('receiver_name');
            }

            if (! DatabaseColumn::exists('users', 'receiver_address')) {
                $table->string('receiver_address')->nullable()->after('receiver_phone');
            }

            if (! DatabaseColumn::exists('users', 'pickup_name')) {
                $table->string('pickup_name')->nullable()->after('receiver_address');
            }

            if (! DatabaseColumn::exists('users', 'pickup_phone')) {
                $table->string('pickup_phone')->nullable()->after('pickup_name');
            }

            if (! DatabaseColumn::exists('users', 'pickup_employee_no')) {
                $table->string('pickup_employee_no')->nullable()->after('pickup_phone');
            }

            if (! DatabaseColumn::exists('users', 'pickup_address')) {
                $table->string('pickup_address')->nullable()->after('pickup_employee_no');
            }

            if (! DatabaseColumn::exists('users', 'pickup_remark')) {
                $table->string('pickup_remark')->nullable()->after('pickup_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'pickup_remark',
                'pickup_address',
                'pickup_employee_no',
                'pickup_phone',
                'pickup_name',
                'receiver_address',
                'receiver_phone',
                'receiver_name',
                'claim_type',
            ] as $column) {
                if (DatabaseColumn::exists('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
