<?php

use App\Enums\ROLE;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->enum('name', array_column(ROLE::cases(), 'value'))->change(); // Update ENUM values
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->enum('name', ['admin', 'user'])->change(); // Revert to original ENUM values
        });
    }
};
