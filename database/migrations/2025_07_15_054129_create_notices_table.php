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
        // Use the Schema facade to create a new table named 'notices'.
        Schema::create('notices', function (Blueprint $table) {
            // The `id` column is an auto-incrementing primary key.
            $table->id();

            // The `notice_title` column will store the title of the notice.
            $table->string('notice_title');
            $table->text('content');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('status')->default(0); // Added status (0: Active, 1: Inactive)

            // The `target_user` column can store an ID or a specific group name
            // to indicate which user(s) the notice is for. It is nullable,
            // meaning some notices can be for everyone.
            $table->string('target_user')->nullable();

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Link to the user who created the notice

            // The `timestamps` method automatically adds `created_at` and `updated_at` columns.
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
