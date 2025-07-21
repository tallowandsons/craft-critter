<?php

namespace mijewe\critter\migrations;

use Craft;
use craft\db\Migration;
use mijewe\critter\records\RequestRecord;

/**
 * m250721_000000_add_tag_column migration
 */
class m250721_000000_add_related_tag_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Add tag column to the requests table
        if (!$this->db->columnExists(RequestRecord::tableName(), 'tag')) {
            $this->addColumn(
                RequestRecord::tableName(),
                'tag',
                $this->string(100)->after('uri')
            );

            // Add index for efficient querying by tag
            $this->createIndex(
                null,
                RequestRecord::tableName(),
                'tag'
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Remove the tag column
        if ($this->db->columnExists(RequestRecord::tableName(), 'tag')) {
            $this->dropIndex(
                $this->db->getIndexes(RequestRecord::tableName())['tag'] ?? null,
                RequestRecord::tableName()
            );

            $this->dropColumn(RequestRecord::tableName(), 'tag');
        }

        return true;
    }
}
