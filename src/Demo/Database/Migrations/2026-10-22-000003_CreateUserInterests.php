<?php

namespace Kpcrudy\Demo\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pure junction table (users <-> categories), edited as an N-N relation
 * from the user and profile forms.
 */
class CreateUserInterests extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'user_id'     => ['type' => 'INT', 'unsigned' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['user_id', 'category_id']);
        $this->forge->addForeignKey('user_id', 'kpdemo_users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'kpdemo_categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('kpdemo_user_interests');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpdemo_user_interests', true);
    }
}
