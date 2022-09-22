<?php
use Migrations\AbstractMigration;

class POCOR6949 extends AbstractMigration
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
    
        // staff_position_categories
       $this->execute('CREATE TABLE `staff_position_categories` (
                      `id` int(11) NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `order` int(3) DEFAULT NULL,
                      `visible` int(1) DEFAULT NULL,
                      `editable` int(1) NOT NULL DEFAULT 1,
                      `default` int(1) NOT NULL DEFAULT 0,
                      `international_code` varchar(10) DEFAULT NULL,
                      `national_code` varchar(10) DEFAULT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) DEFAULT NULL,
                      `created` datetime DEFAULT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1');

        $this->execute('INSERT INTO `staff_position_categories` SELECT * FROM `staff_position_categories`');
        $StaffPositionCategories = [
            [
                'id' => 1,
                'name' => 'None',
                'order' => 1,
                'visible' => 1,
                'editable' => 1,
                'default' => 0,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'supervisor',
                'order' => 1,
                'visible' => 1,
                'editable' => 1,
                'default' => 0,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'name' => 'leadership',
                'order' => 1,
                'visible' => 1,
                'editable' => 1,
                'default' => 0,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('staff_position_categories', $StaffPositionCategories);
        $this->execute("ALTER TABLE `staff_position_categories` ADD PRIMARY KEY (`id`)");
        $this->execute("ALTER TABLE `staff_position_categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;");

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `staff_position_categories`');
    }
}
