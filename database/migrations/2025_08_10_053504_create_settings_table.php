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
            $table->id(); // Primary key for the table

            // Core school information
            $table->string('school_name'); // The official name of the school
            $table->text('address')->nullable(); // The school's address, nullable to allow for initial setup
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();

            // Principal and signature information
            $table->string('principal_name'); // The name of the principal
            // This column stores the file path to the signature image, NOT the image itself.
            $table->string('principal_signature')->nullable();

            // School logo
            // This column stores the file path to the logo image.
            $table->string('school_logo')->nullable();

            // Additional useful information
            $table->string('current_session')->nullable(); // e.g., '2024-2025'

            $table->timestamps(); // Automatically adds `created_at` and `updated_at` columns
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
