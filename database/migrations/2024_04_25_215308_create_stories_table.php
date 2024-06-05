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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->text("story");
            $table->text("content")->nullable();
            $table->enum("type",['image',"video"])->defaultValue('image');
            $table->enum("status",['published',"expired"])->defaultValue('published');
            $table->unsignedInteger("likes")->default(0);
            $table->unsignedInteger("comments")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
