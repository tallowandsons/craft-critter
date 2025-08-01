<?php

namespace tallowandsons\critter\migrations;

use craft\db\Migration;

/**
 * m250801_000000_create_config_table migration for Critter plugin
 */
class m250801_000000_create_config_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Create the critter_config table
        if (!$this->db->tableExists('{{%critter_config}}')) {
            $this->createTable('{{%critter_config}}', [
                'id' => $this->primaryKey(),
                'key' => $this->string(255)->notNull(),
                'value' => $this->text(),
                'siteId' => $this->integer(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            // Create unique index on key and siteId
            $this->createIndex(
                $this->db->getIndexName('{{%critter_config}}', ['key', 'siteId']),
                '{{%critter_config}}',
                ['key', 'siteId'],
                true
            );

            // Add foreign key for siteId
            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%critter_config}}', 'siteId'),
                '{{%critter_config}}',
                'siteId',
                '{{%sites}}',
                'id',
                'CASCADE'
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Drop the table
        if ($this->db->tableExists('{{%critter_config}}')) {
            $this->dropTableIfExists('{{%critter_config}}');
        }

        return true;
    }
}
