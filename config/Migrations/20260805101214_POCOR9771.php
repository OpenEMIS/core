<?php
use Migrations\AbstractMigration;

class POCOR9771 extends AbstractMigration
{
    public function up()
    {
        // Backup counsellings table
        $this->execute('CREATE TABLE `zz_9771_counsellings` LIKE `counsellings`');
        $this->execute('INSERT INTO `zz_9771_counsellings` SELECT * FROM `counsellings`');

        $table = $this->table('counselling_guidance_types', [
            'comment' => 'Links a counselling record to one or more guidance types'
        ]);

        $table
            ->addColumn('counselling_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'Links to counsellings.id'
            ])
            ->addColumn('guidance_type_id', 'integer', [
                'limit' => 11,
                'null' => false,
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
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex(['counselling_id'])
            ->addIndex(['guidance_type_id'])
            ->addIndex(['counselling_id', 'guidance_type_id'])
            ->create();

        // Migrate existing data
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

        // Remove index and column from counsellings
        $table = $this->table('counsellings');

        $table
            ->dropForeignKey('guidance_type_id', 'insti_couns_fk_gui_typ_id')
            ->removeColumn('guidance_type_id')
            ->update();
    }

    public function down()
    {
        // Remove junction table
        $this->table('counselling_guidance_types')->drop()->save();

        // Restore original counsellings table data
        $this->execute('DELETE FROM `counsellings`');
        $this->execute('INSERT INTO `counsellings` SELECT * FROM `zz_9771_counsellings`');

        // Remove backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9771_counsellings`');
    }
}