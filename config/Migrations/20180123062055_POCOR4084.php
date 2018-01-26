<?php
use Migrations\AbstractMigration;

class POCOR4084 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('institution_student_absence_days');

        $table
            ->addColumn('student_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to security_users.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to institutions.id'
            ])
            ->addColumn('absence_type_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'link to absence_types.id'
            ])
            ->addColumn('absent_days', 'integer', [
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('start_date', 'date', [
                'null' => false
            ])
            ->addColumn('end_date', 'date', [
                'null' => false
            ])
            ->addIndex('student_id')
            ->addIndex('institution_id')
            ->addIndex('absence_type_id')
            ->addIndex('start_date')
            ->addIndex('end_date')
            ->save();

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
        $this->dropTable('institution_student_absence_days');
        $table = $this->table('institution_student_absences');
        $table->removeColumn('institution_student_absence_day_id')
            ->save();
    }
}
