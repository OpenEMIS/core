<?php
use Migrations\AbstractMigration;

class POCOR9771 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('counselling_guidance_types', [
            'comment' => 'Links a counselling record to one or more guidance types'
        ]);

        $table
            ->addColumn('counselling_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => null,
                'comment' => 'Links to counsellings.id'
            ])
            ->addColumn('guidance_type_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => null,
                'comment' => 'Links to guidance_types.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'limit' => 11,
                'null' => true,
                'default' => null
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('created_user_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => null
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => null
            ])
            ->addIndex(['counselling_id'])
            ->addIndex(['guidance_type_id'])
            ->addIndex(['counselling_id', 'guidance_type_id'])
            ->create();

        // Backfill existing guidance types from counsellings
        $this->execute("
            INSERT INTO counselling_guidance_types
                (counselling_id, guidance_type_id, created_user_id, created)
            SELECT
                id,
                guidance_type_id,
                created_user_id,
                created
            FROM counsellings
            WHERE guidance_type_id IS NOT NULL
        ");

        // counsellings.guidance_type_id is deliberately kept (not dropped): the Institution,
        // Directory, GuardianNav, and Counselling plugins' CounsellingsTable copies still read
        // and write that column directly and have not been migrated to the join table yet.
    }

    public function down()
    {
        $this->table('counselling_guidance_types')->drop()->save();
    }
}