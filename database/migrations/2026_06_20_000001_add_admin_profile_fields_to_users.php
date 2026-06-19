<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->after('name');
            $table->string('work_city')->nullable()->after('address');
            $table->string('mail_code')->nullable()->after('work_city');
            $table->string('work_address_code')->nullable()->after('mail_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'username',
                'work_city',
                'mail_code',
                'work_address_code',
            ]);
        });
    }
};
