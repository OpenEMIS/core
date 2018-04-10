<?php

use Phinx\Migration\AbstractMigration;

class POCOR4502 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('staff_position_titles_grades', [
            'id' => false,
            'primary_key' => ['staff_position_title_id', 'staff_position_grade_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of grade linked to a specific staff position title'
        ]);
        $table
            ->addColumn('id', 'char', [
                'limit' => 64,
                'null' => false
            ])
            ->addColumn('staff_position_title_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to staff_position_titles.id'
            ])
            ->addColumn('staff_position_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to staff_position_grades.id'
            ])
            ->addIndex('staff_position_title_id')
            ->addIndex('staff_position_grade_id')
            ->save();

        $this->execute("INSERT INTO `staff_position_titles_grades` (`id`, `staff_position_title_id`, `staff_position_grade_id`) SELECT sha2(CONCAT(`id`, ',', -1), '256'),`id`, -1
            FROM `staff_position_titles`");
    }

    public function down()
    {
       $this->dropTable('staff_position_titles_grades');
    }
}
