<?php
use Migrations\AbstractMigration;

class POCOR6928 extends AbstractMigration
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
        /** backup */
        $this->execute('CREATE TABLE `zz_6928_staff_change_types` LIKE `staff_change_types`');
        $this->execute('INSERT INTO `zz_6928_staff_change_types` SELECT * FROM `staff_change_types`');

        /** inserting record */
        $data = [
            [   
                'code' => 'CHANGE_OF_SHIFT',
                'name' => 'Change of Shift'
            ]
        ];
        $this->insert('staff_change_types', $data);
    }

    /** rollback */ 
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `staff_change_types`');
        $this->execute('RENAME TABLE `zz_6928_staff_change_types` TO `staff_change_types`');
    }
}
