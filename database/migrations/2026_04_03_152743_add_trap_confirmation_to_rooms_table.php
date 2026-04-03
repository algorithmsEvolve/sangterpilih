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
        Schema::table('rooms', function (Blueprint $table) {
            $table->json('pending_trap_confirmations')->nullable()->after('turn_multiplier_player_id');
            $table->unsignedBigInteger('trap_target_player_id')->nullable()->after('pending_trap_confirmations');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['pending_trap_confirmations', 'trap_target_player_id']);
        });
    }
};
