<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR5070 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5070_institution_staff` LIKE `institution_staff`');
        $this->execute('INSERT INTO `z_5070_institution_staff` SELECT * FROM `institution_staff`');

        $this->execute('CREATE TABLE `z_5070_institution_positions` LIKE `institution_positions`');
        $this->execute('INSERT INTO `z_5070_institution_positions` SELECT * FROM `institution_positions`');
        // End
        
        $this->execute("ALTER TABLE `institution_staff` ADD `is_homeroom` TINYINT(1) NOT NULL DEFAULT '0' AFTER `institution_id`");
        //insrt data for is_homeroom from institution_positions to institution_staff table
        $staff = TableRegistry::get('institution_staff');
        $position = TableRegistry::get('institution_positions');
        $institutionStaff = $staff->find('all')->toArray();
        foreach($institutionStaff as $key=> $insStaff){
            $staffPosition = $position->find('all',['conditions'=>['id'=> $insStaff->institution_position_id]])->first();
            $staffPositionHomeroom = $staffPosition->is_homeroom;
            $insStaffId = $insStaff->id;
            $this->execute("UPDATE `institution_staff` SET `is_homeroom` = $staffPositionHomeroom WHERE `id`= $insStaffId");
        }
        //end query

        $this->execute("ALTER TABLE `institution_positions` DROP `is_homeroom`");
        
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_staff`');
        $this->execute('RENAME TABLE `z_5070_institution_staff` TO `institution_staff`');
        $this->execute('DROP TABLE IF EXISTS `institution_positions`');
        $this->execute('RENAME TABLE `z_5070_institution_positions` TO `institution_positions`');
    }
}
