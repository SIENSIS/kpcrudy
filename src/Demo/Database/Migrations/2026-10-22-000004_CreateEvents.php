<?php

namespace Kpcrudy\Demo\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEvents extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'summary'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'starts_at'   => ['type' => 'DATETIME'],
            'ends_at'     => ['type' => 'DATETIME', 'null' => true],
            'location'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'capacity'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'price'       => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0],
            'poster'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'cancelled'], 'default' => 'draft'],
            'created_by'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('starts_at');
        // RESTRICT: a category with events cannot be deleted (the CRUD explains why with a hook)
        $this->forge->addForeignKey('category_id', 'kpdemo_categories', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('created_by', 'kpdemo_users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('kpdemo_events');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpdemo_events', true);
    }
}
