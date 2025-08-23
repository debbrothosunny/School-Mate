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
        Schema::create('bus_schedules', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('bus_number')->unique(); // Unique identifier for the bus/route
            $table->string('route_name'); // Name of the bus route (e.g., "Main Campus Loop", "Downtown Express")
            $table->time('departure_time'); // Scheduled departure time
            $table->time('arrival_time');   // Scheduled arrival time
            $table->string('driver_name')->nullable(); // Name of the driver, can be null
            $table->integer('capacity')->nullable(); // Seating capacity, can be null
            $table->integer('status')->default(0); // 0: Active, 1: Inactive,
            // New column: Foreign key to the class_names table
            // This assumes a bus schedule can be associated with one specific class.
            // If a bus serves multiple classes, you might need a pivot table.
            $table->foreignId('class_id')
                  ->nullable() // Make it nullable if a schedule might not always be linked to a class
                  ->constrained('class_names') // Assumes your class names table is named 'class_names'
                  ->onDelete('set null'); // If a class is deleted, set this to null

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('bus_schedules');
    }
};
