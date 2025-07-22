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
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 32)->index();
            $table->json('value')->nullable();
            $table->string('group', 16)->index()->default('default');
            $table->nullableUuidMorphs('settable');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name', 'group', 'settable_type', 'settable_id'], 'unique_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
