<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a JSON column to store per-user server sorting/view preferences.
     *
     * Structure: { "team_<id>": { "sort": "name_asc"|"name_desc"|"custom", "view": "grid"|"list", "custom_order": [id,...] } }
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('server_sort_preference')->nullable()->after('project_sort_preference');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('server_sort_preference');
        });
    }
};
