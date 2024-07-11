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
        $this->execute('DROP TRIGGER trigger_institution_student_absence_details_insert');
        $this->execute('DROP TRIGGER trigger_institution_student_absence_details_update');
        $this->execute('DROP TRIGGER trigger_institution_student_absence_details_delete');
   
    }
    public function down()
    {
    }
}