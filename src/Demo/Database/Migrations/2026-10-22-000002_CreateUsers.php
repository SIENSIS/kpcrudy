<?php

namespace Kpcrudy\Demo\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Users. deleted_at enables the trash (soft deletes) in the CRUD.
 * Roles: admin (everything), editor (events and categories, with limits set
 * by Libraries\DemoPermissions) and user.
 */
class CreateUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'username'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'password'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'last_name'  => ['type' => 'VARCHAR', 'constraint' => 120],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'avatar'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'birthdate'  => ['type' => 'DATE', 'null' => true],
            'bio'        => ['type' => 'TEXT', 'null' => true],
            'role'       => ['type' => 'ENUM', 'constraint' => ['admin', 'editor', 'user'], 'default' => 'user'],
            'active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('kpdemo_users');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpdemo_users', true);
    }
}
