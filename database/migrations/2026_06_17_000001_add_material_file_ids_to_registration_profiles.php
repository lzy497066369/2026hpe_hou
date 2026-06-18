<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_profiles', function (Blueprint $table): void {
            $table->longText('material_file_ids')->nullable()->after('material_file_id');
        });
    }

    public function down(): void
    {
        Schema::table('registration_profiles', function (Blueprint $table): void {
            $table->dropColumn('material_file_ids');
        });
    }
};
