<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR6677 extends AbstractMigration
{
    const FIXED_SYSTEM_GROUP_ID = -1;  // fixed system defined roles
    const CUSTOM_SYSTEM_GROUP_ID = 0;  // custom system defined roles
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        /**backup of security_roles table*/
        $this->execute('CREATE TABLE `zz_6677_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `zz_6677_security_roles` SELECT * FROM `security_roles`');

        $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
        $records = $SecurityRoles->find()
                    ->where(['security_group_id IN ' => [self::FIXED_SYSTEM_GROUP_ID, self::CUSTOM_SYSTEM_GROUP_ID]])
                    ->toArray();

        $order = 1;
        foreach ($records as $key => $value) {
            /**updating existed value of order*/
            $this->execute('UPDATE `security_roles` SET `order` = "'.$order.'" WHERE `id`="'.$value->id.'"');
            $order++;
        }
    }

    /**rollback migration script*/
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_roles`');
        $this->execute('RENAME TABLE `zz_6677_security_roles` TO `security_roles`');
    }
}
