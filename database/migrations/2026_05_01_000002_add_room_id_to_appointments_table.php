<?php

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
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('service_id')->constrained('rooms')->nullOnDelete();
            $table->index(['room_id', 'appt_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropIndex(['room_id', 'appt_date']);
            $table->dropColumn('room_id');
        });
    }
};
