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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            $table->string('name');
            $table->enum('designation', [
                'Head Teacher',
                'Senior Teacher',
                'Junior Teacher',
                'Assistant Teacher'
            ])->default('Junior Teacher');

            $table->string('address');
            $table->string('joining_number')->unique();
            $table->string('image')->nullable();
            $table->string('subject_taught');
            $table->tinyInteger('status')->default(1)->comment('1 = Active, 0 = Inactive');

            $table->string('phone_number')->unique();
            $table->string('qualification')->nullable();
            $table->date('joining_date')->nullable();

            // === Class Teacher Assignment (All Nullable) ===
            $table->foreignId('class_id')->nullable()->constrained('class_names')->onDelete('set null');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
            $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('set null');

            $table->timestamps();

            // Optional: Ensure one user is only one teacher
            $table->unique('user_id');

            // Optional: Prevent same class+section+group from having multiple class teachers
            $table->unique(['class_id', 'section_id', 'group_id'], 'unique_class_teacher');
        });
    }
  
    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
