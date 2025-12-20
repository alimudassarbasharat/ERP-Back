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
        // Create channel_users table (for our new implementation)
        if (!Schema::hasTable('channel_users')) {
            Schema::create('channel_users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('channel_id');
                $table->unsignedBigInteger('user_id');
                $table->string('role')->default('member'); // admin, member
                $table->timestamp('last_read_at')->nullable();
                $table->integer('unread_count')->default(0);
                $table->boolean('is_muted')->default(false);
                $table->json('notification_preferences')->nullable();
                $table->timestamps();
                $table->unique(['channel_id', 'user_id']);
                $table->index('user_id');
            });
        }

        // Update channels table if needed
        if (Schema::hasTable('channels') && !Schema::hasColumn('channels', 'created_by')) {
            Schema::table('channels', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('type');
                $table->boolean('is_archived')->default(false)->after('is_active');
            });
        }

        // Create messages table if it doesn't exist
        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('channel_id');
                $table->unsignedBigInteger('user_id');
                $table->text('content');
                $table->string('type')->default('text'); // text, file, system
                $table->json('metadata')->nullable(); // for rich content, mentions, etc
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->boolean('is_edited')->default(false);
                $table->timestamp('edited_at')->nullable();
                $table->boolean('is_deleted')->default(false);
                $table->timestamps();
                $table->index(['channel_id', 'created_at']);
                $table->index('parent_id');
                $table->index('user_id');
            });
        }

        // Create direct message conversations
        if (!Schema::hasTable('direct_message_conversations')) {
            Schema::create('direct_message_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable(); // For group DMs
                $table->boolean('is_group')->default(false);
                $table->timestamps();
            });
        }

        // Direct message participants
        if (!Schema::hasTable('direct_message_participants')) {
            Schema::create('direct_message_participants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('last_read_at')->nullable();
                $table->integer('unread_count')->default(0);
                $table->boolean('is_muted')->default(false);
                $table->timestamps();
                $table->unique(['conversation_id', 'user_id']);
                $table->index('user_id');
            });
        }

        // Direct messages
        if (!Schema::hasTable('direct_messages')) {
            Schema::create('direct_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id');
                $table->text('content');
                $table->string('type')->default('text');
                $table->json('metadata')->nullable();
                $table->boolean('is_edited')->default(false);
                $table->timestamp('edited_at')->nullable();
                $table->boolean('is_deleted')->default(false);
                $table->timestamps();
                $table->index(['conversation_id', 'created_at']);
                $table->index('user_id');
            });
        }

        // Update message_reactions table if needed
        if (!Schema::hasTable('message_reactions')) {
            Schema::create('message_reactions', function (Blueprint $table) {
                $table->id();
                $table->morphs('reactable'); // Can be used for both messages and direct_messages
                $table->unsignedBigInteger('user_id');
                $table->string('emoji');
                $table->timestamps();
                $table->unique(['reactable_id', 'reactable_type', 'user_id', 'emoji'], 'unique_reaction');
            });
        }

        // Update message_attachments table if needed
        if (!Schema::hasTable('message_attachments')) {
            Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
                $table->unsignedBigInteger('message_id');
                $table->string('filename');
                $table->string('original_name');
                $table->string('file_path');
                $table->string('file_url')->nullable();
                $table->string('mime_type');
                $table->bigInteger('file_size');
                $table->json('metadata')->nullable(); // thumbnails, dimensions, etc
            $table->timestamps();
        });
        }
        
        // Create direct_message_attachments table
        if (!Schema::hasTable('direct_message_attachments')) {
            Schema::create('direct_message_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('direct_message_id');
                $table->string('filename');
                $table->string('original_name');
                $table->string('file_path');
                $table->string('file_url')->nullable();
                $table->string('mime_type');
                $table->bigInteger('file_size');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // User presence
        if (!Schema::hasTable('user_presence')) {
            Schema::create('user_presence', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('status')->default('offline'); // online, away, busy, offline
                $table->string('status_text')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
                $table->unique('user_id');
            });
        }

        // Typing indicators
        if (!Schema::hasTable('typing_indicators')) {
            Schema::create('typing_indicators', function (Blueprint $table) {
                $table->id();
                $table->morphs('typeable'); // channel_id or conversation_id
                $table->unsignedBigInteger('user_id');
                $table->timestamp('started_at');
                $table->index(['typeable_id', 'typeable_type', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typing_indicators');
        Schema::dropIfExists('user_presence');
        Schema::dropIfExists('direct_message_attachments');
        
        // Only drop if we created them
        if (!Schema::hasColumn('message_attachments', 'created_at')) {
            Schema::dropIfExists('message_attachments');
        }
        if (!Schema::hasColumn('message_reactions', 'created_at')) {
            Schema::dropIfExists('message_reactions');
        }
        
        Schema::dropIfExists('direct_messages');
        Schema::dropIfExists('direct_message_participants');
        Schema::dropIfExists('direct_message_conversations');
        
        if (!Schema::hasColumn('messages', 'created_at')) {
            Schema::dropIfExists('messages');
        }
        
        Schema::dropIfExists('channel_users');
        
        // Remove columns we added
        if (Schema::hasTable('channels')) {
            Schema::table('channels', function (Blueprint $table) {
                if (Schema::hasColumn('channels', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('channels', 'is_archived')) {
                    $table->dropColumn('is_archived');
                }
            });
        }
    }
};