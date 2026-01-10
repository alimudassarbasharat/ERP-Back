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
        Schema::table('schools', function (Blueprint $table) {
            // Core Identity (extending existing name and code)
            $table->string('logo')->nullable()->after('code');
            $table->enum('school_type', ['school', 'college', 'academy'])->nullable()->after('logo');
            $table->string('tagline')->nullable()->after('school_type');
            
            // Contact Information
            $table->string('phone_primary')->nullable()->after('tagline');
            $table->string('phone_secondary')->nullable()->after('phone_primary');
            $table->string('email')->nullable()->after('phone_secondary');
            $table->string('website')->nullable()->after('email');
            
            // Address Information
            $table->string('country')->default('Pakistan')->after('website');
            $table->string('state_province')->nullable()->after('country');
            $table->string('city')->nullable()->after('state_province');
            $table->text('address_line_1')->nullable()->after('city');
            $table->text('address_line_2')->nullable()->after('address_line_1');
            $table->string('postal_code')->nullable()->after('address_line_2');
            
            // Branding/Theme
            $table->string('primary_color')->default('#e91e63')->after('postal_code'); // Pink default
            $table->string('secondary_color')->default('#f8f9fa')->after('primary_color'); // Light default
            $table->text('invoice_footer_text')->nullable()->after('secondary_color');
            $table->text('report_header_text')->nullable()->after('invoice_footer_text');
            
            // Academic Defaults
            $table->string('timezone')->default('Asia/Karachi')->after('report_header_text');
            $table->string('currency')->default('PKR')->after('timezone');
            $table->string('date_format')->default('d/m/Y')->after('currency');
            $table->enum('week_start_day', ['monday', 'sunday'])->default('monday')->after('date_format');
            
            // Communication Settings
            $table->string('default_whatsapp_sender')->nullable()->after('week_start_day');
            $table->string('default_sms_sender')->nullable()->after('default_whatsapp_sender');
            $table->string('default_email_sender_name')->nullable()->after('default_sms_sender');
            $table->json('notification_channels_enabled')->nullable()->after('default_email_sender_name');
            
            // Security/Audit
            $table->unsignedBigInteger('created_by')->nullable()->after('notification_channels_enabled');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->timestamp('completed_at')->nullable()->after('updated_by');
            
            // Multi-tenant support (string to match users/admins table)
            $table->string('merchant_id')->nullable()->after('completed_at');
            
            // Add indexes for performance
            $table->index(['completed_at', 'merchant_id']);
            $table->index('merchant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'logo', 'school_type', 'tagline',
                'phone_primary', 'phone_secondary', 'email', 'website',
                'country', 'state_province', 'city', 'address_line_1', 'address_line_2', 'postal_code',
                'primary_color', 'secondary_color', 'invoice_footer_text', 'report_header_text',
                'timezone', 'currency', 'date_format', 'week_start_day',
                'default_whatsapp_sender', 'default_sms_sender', 'default_email_sender_name', 
                'notification_channels_enabled',
                'created_by', 'updated_by', 'completed_at', 'merchant_id'
            ]);
        });
    }
};