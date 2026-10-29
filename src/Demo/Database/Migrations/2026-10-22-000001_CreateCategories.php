<?php

namespace Kpcrudy\Demo\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Event categories. No soft deletes: the CRUD shows no trash for this table.
 * The visible texts (title, description) are translated in
 * kpdemo_category_translations; "name" is the internal, unique name.
 */
class CreateCategories extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 80],
            'color'       => ['type' => 'VARCHAR', 'constraint' => 7, 'default' => '#0d6efd'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('kpdemo_categories');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpdemo_categories', true);
    }
}
