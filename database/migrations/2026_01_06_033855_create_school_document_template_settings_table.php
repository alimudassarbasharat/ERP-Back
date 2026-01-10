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
        Schema::create('school_document_template_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->enum('type', ['invoice', 'voucher']);
            $table->foreignId('template_id')->constrained('document_templates')->onDelete('cascade');
            $table->json('config_json')->nullable()->comment('Template styling and configuration');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            // Note: Application logic should ensure only one default per school per type
            $table->index(['school_id', 'type', 'is_default']);
            $table->index(['school_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_document_template_settings');
    }
};
