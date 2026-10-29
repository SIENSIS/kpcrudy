<?php

namespace Kpcrudy\Demo\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Translated texts of the categories, one row per language (setTranslations()
 * in Admin\Categories). English is required; the other languages are optional.
 */
class CreateCategoryTranslations extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
            'locale'      => ['type' => 'VARCHAR', 'constraint' => 5],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'description' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['category_id', 'locale']);
        $this->forge->addForeignKey('category_id', 'kpdemo_categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('kpdemo_category_translations');
    }

    public function down(): void
    {
        $this->forge->dropTable('kpdemo_category_translations', true);
    }
}
