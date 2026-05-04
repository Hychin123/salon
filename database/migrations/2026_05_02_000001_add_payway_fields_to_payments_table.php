<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payway_tran_id')->nullable()->index()->after('method');
            $table->string('payway_status')->nullable()->after('payway_tran_id');
            $table->string('payway_apv')->nullable()->after('payway_status');
            $table->timestamp('payway_requested_at')->nullable()->after('payway_apv');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payway_tran_id', 'payway_status', 'payway_apv', 'payway_requested_at']);
        });
    }
};
