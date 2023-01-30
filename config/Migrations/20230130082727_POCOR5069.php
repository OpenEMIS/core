<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR5069 extends AbstractMigration
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
        // Backup tables
        $this->execute('CREATE TABLE `zz_5069_institution_positions` LIKE `institution_positions`');
        $this->execute('INSERT INTO `zz_5069_institution_positions` SELECT * FROM `institution_positions`');

        $this->execute('CREATE TABLE `zz_5069_institution_staff` LIKE `institution_staff`');
        $this->execute('INSERT INTO `zz_5069_institution_staff` SELECT * FROM `institution_staff`');

        $this->execute('ALTER TABLE `institution_staff` ADD `staff_position_grade_id` INT NOT NULL AFTER `security_group_user_id`;');

        $staff = TableRegistry::get('institution_staff');
        $position = TableRegistry::get('institution_positions');
        $institutionStaff = $staff->find('all')->toArray();
        foreach($institutionStaff as $key=> $insStaff){
            $staffPosition = $position->find('all',['conditions'=>['id'=> $insStaff->institution_position_id]])->first();
            
            $staffPositionHomeroom = $staffPosition->staff_position_grade_id;
            $insStaffId = $insStaff->id;
            $this->execute("UPDATE `institution_staff` SET `staff_position_grade_id` = $staffPositionHomeroom WHERE `id`= $insStaffId");
        }

        $this->execute('ALTER TABLE institution_positions DROP FOREIGN KEY insti_posit_fk_staff_posit_grade_id');
        $this->execute('ALTER TABLE `institution_positions` DROP `staff_position_grade_id`');
        
    }

    public function down()
    {

        $this->execute('DROP TABLE IF EXISTS `institution_positions`');
        $this->execute('RENAME TABLE `zz_5069_institution_positions` TO `institution_positions`');

        $this->execute('DROP TABLE IF EXISTS `institution_staff`');
        $this->execute('RENAME TABLE `zz_5069_institution_staff` TO `institution_staff`');

    }
}
