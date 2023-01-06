<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR5784 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5784_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `z_5784_security_roles` SELECT * FROM `security_roles`');
        
        $table = TableRegistry::get('SecurityRoles');
        
        $securityRoles = $table->find()->where(['security_group_id IN ' => [self::FIXED_SYSTEM_GROUP_ID, self::CUSTOM_SYSTEM_GROUP_ID]])->order(['`order` ASC']);
        $order = 1;
        
        foreach($securityRoles as $securityRole){           
            $this->execute('UPDATE `security_roles` SET `order` = "'.$order.'" WHERE `id`="'.$securityRole->id.'"');
            $order++;
        }
        
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_roles`');
        $this->execute('RENAME TABLE `z_5784_security_roles` TO `security_roles`');
    }
}
