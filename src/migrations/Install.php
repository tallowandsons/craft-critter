<?php

namespace tallowandsons\critter\migrations;

use Craft;
use craft\db\Migration;
use craft\records\Site;
use tallowandsons\critter\records\RequestRecord;
use tallowandsons\critter\records\SectionConfigRecord;

/**
 * Install migration for Critter plugin
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();

            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(SectionConfigRecord::tableName());
        $this->dropTableIfExists(RequestRecord::tableName());

        return true;
    }

    /**
     * Creates the tables needed for the Records used by the plugin.
     */
    protected function createTables(): bool
    {
        // Create criticalcssgenerator_requests table
        if (!$this->db->tableExists(RequestRecord::tableName())) {
            $this->createTable(RequestRecord::tableName(), [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->notNull(),
                'uri' => $this->string(500)->notNull(),
                'queryString' => $this->string(500),
                'tag' => $this->string(100),
                'status' => $this->string(50)->defaultValue('todo'),
                'data' => $this->text(),
                'dateQueued' => $this->dateTime(),
                'dateGenerated' => $this->dateTime(),
                'expiryDate' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        // Create criticalcssgenerator_sectionconfig table
        if (!$this->db->tableExists(SectionConfigRecord::tableName())) {
            $this->createTable(SectionConfigRecord::tableName(), [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer(),
                'sectionId' => $this->integer(),
                'data' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        return true;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin.
     */
    protected function createIndexes(): void
    {
        // Create indexes for requests table
        $this->createIndex(null, RequestRecord::tableName(), ['siteId', 'uri']);
        $this->createIndex(null, RequestRecord::tableName(), 'queryString');
        $this->createIndex(null, RequestRecord::tableName(), 'tag');
        $this->createIndex(null, RequestRecord::tableName(), 'status');
        $this->createIndex(null, RequestRecord::tableName(), 'expiryDate');
        $this->createIndex(null, RequestRecord::tableName(), 'dateQueued');
        $this->createIndex(null, RequestRecord::tableName(), 'dateGenerated');

        // Create indexes for section config table
        $this->createIndex(null, SectionConfigRecord::tableName(), 'siteId');
        $this->createIndex(null, SectionConfigRecord::tableName(), 'sectionId');
        $this->createIndex(null, SectionConfigRecord::tableName(), ['siteId', 'sectionId'], true);
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin.
     */
    protected function addForeignKeys(): void
    {
        // Add foreign keys for requests table
        $this->addForeignKey(
            null,
            RequestRecord::tableName(),
            'siteId',
            Site::tableName(),
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Add foreign keys for section config table
        $this->addForeignKey(
            null,
            SectionConfigRecord::tableName(),
            'siteId',
            Site::tableName(),
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Note: Uncomment these if you want to enforce section/entry type relationships
        // $this->addForeignKey(
        //     null,
        //     SectionConfigRecord::tableName(),
        //     'sectionId',
        //     '{{%sections}}',
        //     'id',
        //     'CASCADE',
        //     'CASCADE'
        // );

        // $this->addForeignKey(
        //     null,
        //     SectionConfigRecord::tableName(),
        //     'entryTypeId',
        //     '{{%entrytypes}}',
        //     'id',
        //     'CASCADE',
        //     'CASCADE'
        // );
    }
}
