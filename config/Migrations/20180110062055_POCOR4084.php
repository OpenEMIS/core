<?php
use Migrations\AbstractMigration;

class POCOR4084 extends AbstractMigration
{
    public function up()
    {
        $this->table('institution_student_absence_days');


        $table = $this->table('institution_student_absences');
        $table->addColumn('institution_student_absence_day_id', 'integer', [
                'limit' => 11,
                'null' => true,
                'default' => null
            ])
            ->update();
    }

    public function down()
    {
        $table->removeColumn('institution_student_absence_day_id')
            ->save();
    }
}
