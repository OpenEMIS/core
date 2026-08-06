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
                'null' => true,
                'default' => null
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP'
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

        if ($table->hasForeignKey('guidance_type_id', 'insti_couns_fk_gui_typ_id')) {
            $table->dropForeignKey('guidance_type_id', 'insti_couns_fk_gui_typ_id');
        }

        if ($table->hasColumn('guidance_type_id')) {
            $table->removeColumn('guidance_type_id');
        }

        $table->update();
    }

    public function down()
    {
        // Remove junction table
        $this->table('counselling_guidance_types')->drop()->save();
        $table = $this->table('counsellings');
        $table->addColumn('guidance_type_id', 'integer', [
            'limit' => 11,
            'null' => true,
            'comment' => 'links to guidance_types.id',
        ])->update();
        $this->execute('ALTER TABLE `counsellings` ADD CONSTRAINT `insti_couns_fk_gui_typ_id` FOREIGN KEY (`guidance_type_id`) REFERENCES `guidance_types`(`id`)');

        // Restore original counsellings table data .
        // re-added guidance_type_id column in the table, not in its
        $this->execute('DELETE FROM `counsellings`');
        $this->execute('
            INSERT INTO `counsellings`
                (id, date, guidance_utilized, description, intervention, comment, file_name,
                 file_content, counselor_id, student_id, requester_id, guidance_type_id,
                 modified_user_id, modified, created_user_id, created)
            SELECT
                id, date, guidance_utilized, description, intervention, comment, file_name,
                file_content, counselor_id, student_id, requester_id, guidance_type_id,
                modified_user_id, modified, created_user_id, created
            FROM `zz_9771_counsellings`
        ');

        // Remove backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9771_counsellings`');
    }
}