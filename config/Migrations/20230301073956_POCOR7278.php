<?php
use Migrations\AbstractMigration;

class POCOR7278 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7278_institution_staff_position_profiles` LIKE `institution_staff_position_profiles`');
        $this->execute('INSERT INTO `zz_7278_institution_staff_position_profiles` SELECT * FROM `institution_staff_position_profiles`');


        // DROP foreign key relationship     
        $this->execute("ALTER TABLE institution_staff_position_profiles DROP FOREIGN KEY `insti_staff_posit_profi_fk_ass_id`;");
        
    }
         
    // rollback
    public function down()
    {
        
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `institution_staff_position_profiles`');
        $this->execute('RENAME TABLE `zz_7278_institution_staff_position_profiles` TO `institution_staff_position_profiles`');
    }
}
?>