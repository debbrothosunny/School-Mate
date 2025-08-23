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
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->date('notice_date')->nullable(); // Changed from published_at to notice_date
            $table->integer('status')->default(0); // Added status (0: Active, 1: Inactive)
            $table->json('target_user')->nullable(); // Changed from target_audience to target_user
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Link to the user who created the notice
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
