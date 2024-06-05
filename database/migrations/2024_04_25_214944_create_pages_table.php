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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->uuid("uuid");
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->string(("avatar"))->nullable();
            $table->string("thumbnail")->nullable();
            $table->string("name");
            $table->string("description")->nullable();
            $table->string("location")->nullable();
            $table->string("type")->nullable();
            $table->unsignedBigInteger("follwers")->default(0);
            $table->boolean("is_private")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
