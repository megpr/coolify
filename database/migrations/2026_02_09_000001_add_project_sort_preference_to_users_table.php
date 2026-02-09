<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a JSON column to store per-user project sorting/view preferences.
     *
     * Structure: { "team_<id>": { "sort": "name_asc"|"name_desc"|"custom", "view": "grid"|"list", "custom_order": [id,...] } }
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('project_sort_preference')->nullable()->after('marketing_emails');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('project_sort_preference');
        });
    }
};
