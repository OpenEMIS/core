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

        $this->execute("INSERT INTO `staff_position_titles_grades` (`staff_position_title_id`, `staff_position_grade_id`) SELECT `id`, -1
            FROM `staff_position_titles`");

        $localeContent = [
            [
                'en' => 'You are not allow to remove the following in-use grades: %s',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
       $this->dropTable('staff_position_titles_grades');
       $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'You are not allow to remove the following in-use grades: %s'");
    }
}
