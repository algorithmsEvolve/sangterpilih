<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedInteger('current_round')->default(1)->after('current_turn_player_id');
            $table->unsignedInteger('total_rounds')->default(5)->after('current_round');
            $table->unsignedInteger('turn_index')->default(0)->after('total_rounds');
            $table->json('active_turn_snapshot')->nullable()->after('turn_index');
            $table->boolean('turn_has_skip')->default(false)->after('active_turn_snapshot');
            $table->unsignedBigInteger('turn_multiplier_player_id')->nullable()->after('turn_has_skip');
            $table->unsignedTinyInteger('last_dice_result')->nullable()->after('turn_multiplier_player_id');
            $table->string('last_roller_name')->nullable()->after('last_dice_result');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->boolean('has_rolled_this_turn')->default(false)->after('has_rolled');
            $table->json('inventory')->nullable()->after('has_rolled_this_turn');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['has_rolled_this_turn', 'inventory']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn([
                'current_round',
                'total_rounds',
                'turn_index',
                'active_turn_snapshot',
                'turn_has_skip',
                'turn_multiplier_player_id',
                'last_dice_result',
                'last_roller_name',
            ]);
        });
    }
};
