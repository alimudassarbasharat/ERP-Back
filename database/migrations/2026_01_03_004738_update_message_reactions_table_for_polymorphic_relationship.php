<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if reactable columns already exist
        if (!Schema::hasColumn('message_reactions', 'reactable_id')) {
            // Add polymorphic columns
            Schema::table('message_reactions', function (Blueprint $table) {
                $table->unsignedBigInteger('reactable_id')->nullable()->after('id');
                $table->string('reactable_type')->nullable()->after('reactable_id');
            });

            // Migrate existing data from message_id to reactable_id/reactable_type
            if (Schema::hasColumn('message_reactions', 'message_id')) {
                DB::statement('UPDATE message_reactions SET reactable_id = message_id, reactable_type = ? WHERE message_id IS NOT NULL', ['App\\Models\\Message']);
            }

            // Make reactable columns not nullable after migration
            Schema::table('message_reactions', function (Blueprint $table) {
                $table->unsignedBigInteger('reactable_id')->nullable(false)->change();
                $table->string('reactable_type')->nullable(false)->change();
            });

            // Drop old message_id column if it exists
            if (Schema::hasColumn('message_reactions', 'message_id')) {
                Schema::table('message_reactions', function (Blueprint $table) {
                    $table->dropForeign(['message_id']);
                    $table->dropColumn('message_id');
                });
            }

            // Drop old unique constraint if it exists
            try {
                DB::statement('ALTER TABLE message_reactions DROP CONSTRAINT IF EXISTS message_reactions_message_id_user_id_emoji_unique');
            } catch (\Exception $e) {
                // Constraint might not exist, ignore
            }

            // Add new unique constraint for polymorphic relationship
            Schema::table('message_reactions', function (Blueprint $table) {
                $table->unique(['reactable_id', 'reactable_type', 'user_id', 'emoji'], 'unique_reaction');
                $table->index(['reactable_id', 'reactable_type']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('message_reactions', 'reactable_id')) {
            // Add back message_id column
            Schema::table('message_reactions', function (Blueprint $table) {
                $table->unsignedBigInteger('message_id')->nullable()->after('id');
            });

            // Migrate data back
            DB::statement('UPDATE message_reactions SET message_id = reactable_id WHERE reactable_type = ?', ['App\\Models\\Message']);

            // Drop polymorphic columns
            Schema::table('message_reactions', function (Blueprint $table) {
                $table->dropUnique('unique_reaction');
                $table->dropIndex(['reactable_id', 'reactable_type']);
                $table->dropColumn(['reactable_id', 'reactable_type']);
            });

            // Add foreign key and unique constraint back
            Schema::table('message_reactions', function (Blueprint $table) {
                $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
                $table->unique(['message_id', 'user_id', 'emoji']);
            });
        }
    }
};
