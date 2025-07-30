<?php

namespace tallowandsons\critter\migrations;

use craft\db\Migration;
use tallowandsons\critter\records\RequestRecord;

/**
 * m250727_000000_add_query_string_column migration for Critter plugin
 */
class m250727_000000_add_query_string_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Add queryString column to the critter_requests table
        if (!$this->db->columnExists(RequestRecord::tableName(), 'queryString')) {
            $this->addColumn(
                RequestRecord::tableName(),
                'queryString',
                $this->string(500)->after('uri')
            );

            // Create an index on the queryString column for performance
            $this->createIndex(
                $this->db->getIndexName(RequestRecord::tableName(), 'queryString'),
                RequestRecord::tableName(),
                'queryString'
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Drop the index first
        $indexName = $this->db->getIndexName(RequestRecord::tableName(), 'queryString');
        if ($this->db->indexExists(RequestRecord::tableName(), $indexName)) {
            $this->dropIndex($indexName, RequestRecord::tableName());
        }

        // Remove the queryString column
        if ($this->db->columnExists(RequestRecord::tableName(), 'queryString')) {
            $this->dropColumn(RequestRecord::tableName(), 'queryString');
        }

        return true;
    }
}
