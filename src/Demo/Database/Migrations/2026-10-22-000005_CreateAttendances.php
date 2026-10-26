<?php

namespace Kpcrudy\Demo\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Junction table between users and events with its own data (status,
 * registration date, notes). It has its own primary key so it can be
 * managed with a CRUD, and it is also used as an N-N relation (attendees).
 */
class CreateAttendances extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'unsigned' => true],
            'event_id'      => ['type' => 'INT', 'unsigned' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['registered', 'attended', 'absent', 'cancelled'], 'default' => 'registered'],
            'registered_at' => ['type' => 'DATETIME'],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'event_id']);
        $this->forge->addForeignKey('user_id', 'kpdemo_users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('event_id', 'kpdemo_events', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('kpdemo_attendances');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpdemo_attendances', true);
    }
}
