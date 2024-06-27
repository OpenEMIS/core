<?php
use Migrations\AbstractMigration;
class POCOR8311 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        $this->execute('DROP TRIGGER IF EXISTS trigger_institution_student_absence_details_insert ON institution_student_absence_details');
        $this->execute('DROP TRIGGER IF EXISTS trigger_institution_student_absence_details_update ON institution_student_absence_details');
        $this->execute('DROP TRIGGER IF EXISTS trigger_institution_student_absence_details_delete ON institution_student_absence_details');
    }
    public function down()
    {
    }
}