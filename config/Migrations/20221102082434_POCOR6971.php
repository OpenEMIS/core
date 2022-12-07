<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR6971 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6971_institution_positions` LIKE `institution_positions`');
        $this->execute('INSERT INTO `zz_6971_institution_positions` SELECT * FROM `institution_positions`');
        // End

        /*$this->execute("ALTER TABLE `institution_positions` ADD `shift_id` INT NOT NULL DEFAULT 1 AFTER `assignee_id`");

        $this->execute("ALTER TABLE institution_positions DROP PRIMARY KEY");

        $this->execute("ALTER TABLE institution_positions ADD PRIMARY KEY(status_id,staff_position_title_id,staff_position_grade_id,institution_id,assignee_id,shift_id,)");*/

        //insert new column
        $this->execute('ALTER TABLE `institution_positions` ADD `shift_id` INT NULL AFTER `assignee_id`');
       
        $this->execute("ALTER TABLE `institution_positions` CHANGE `shift_id` `shift_id` INT(11) NULL COMMENT 'links to shift_options.id'");

        //updating shift_id column value
        $shift = TableRegistry::get('institution_staff_shifts');
        $staff = TableRegistry::get('institution_staff');
        $position = TableRegistry::get('institution_positions');
        $shiftOption = TableRegistry::get('institution_shifts');
        $staffIds = $shift->find()->select(['staff_id'])->group([$shift->aliasField('staff_id')])->toArray();  
        foreach($staffIds as $staffId){
           $staffidss =  $staffId['staff_id'];
           $staffData = $shift->find('all')->select(['staff_id'=>$shift->aliasField('staff_id'),'shift_id'=>$shift->aliasField('shift_id')])->where([$shift->aliasField('staff_id')=>$staffidss])->order([$shift->aliasField('id DESC')])->limit(1); 
           foreach($staffData->toArray() as $val) {
            $staffGet  = $val['staff_id'];
            $shiftGet  = $val['shift_id'];
            }
           $positionVal = $staff->find('all')->select(['institution_position_id'=>$staff->aliasField('institution_position_id')])->where([$staff->aliasField('staff_id')=>$staffGet])->first();
           $shiftVal = $shiftOption->find('all')->select(['shift_option_id'=>$shiftOption->aliasField('shift_option_id')])->where([$shiftOption->aliasField('id')=>$shiftGet])->first();
           $shiftOptionId =$shiftVal->shift_option_id; 
           $positionId =$positionVal->institution_position_id; 
            $this->execute("UPDATE `institution_positions` SET `shift_id` = $shiftOptionId WHERE `id`= $positionId");
        }
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_positions`');
        $this->execute('RENAME TABLE `zz_6971_institution_positions` TO `institution_positions`');
    }
}
