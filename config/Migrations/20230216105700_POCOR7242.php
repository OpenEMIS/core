<?php
use Migrations\AbstractMigration;

class POCOR7242 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_7242_institution_staff_leave` LIKE `institution_staff_leave`');
        $this->execute('INSERT INTO `zz_7242_institution_staff_leave` SELECT * FROM `institution_staff_leave`');


        // DROP foreign key relationship     
        $this->execute("ALTER TABLE institution_staff_leave DROP FOREIGN KEY `insti_staff_leave_fk_ass_id`;");
        
    }
         
    // rollback
    public function down()
    {
        
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `institution_staff_leave`');
        $this->execute('RENAME TABLE `zz_7242_institution_staff_leave` TO `institution_staff_leave`');
    }
}
?>