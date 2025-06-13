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
        Schema::create('income_report', function (Blueprint $table) {
            $table->id('income_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('ticketing_id')->unique(); // tambahkan unique() di sini
            $table->unsignedBigInteger('expenditure_id');
            $table->datetime('booking_date')->nullable();
            $table->decimal('income', 25, 2)->nullable();
            $table->decimal('expediture', 25, 2)->nullable();
            $table->decimal('cash', 25, 2)->nullable();
            $table->timestamps();
        
            $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade');
            $table->foreign('ticketing_id')->references('id')->on('ticketings')->onDelete('cascade');
            $table->foreign('expenditure_id')->references('expenditure_id')->on('expenditure_report')->onDelete('cascade');
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('income_report', function (Blueprint $table) {
        //     $table->dropForeign(['salaries_id']); // Hapus foreign key salaries_id
        //     $table->dropForeign(['ticketing_id']); // Hapus foreign key salaries_id
        //     $table->dropForeign(['expenditure_id']); // Hapus foreign key salaries_id
        // });
        Schema::dropIfExists('income_report');
    }
};
