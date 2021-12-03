<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR6223 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `z_6223_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `z_6223_security_roles` SELECT * FROM `security_roles`');


        $securityRolesTable = TableRegistry::get('security_roles');
        $reorderItems = $securityRolesTable
                ->find('list')
                ->order([$securityRolesTable->aliasField('order')])
                ->toArray();
        if(!empty($reorderItems)){
            $counter = 1;
            foreach ($reorderItems as $key => $item) {
                $securityRolesTable->updateAll(['order' => $counter++], [$securityRolesTable->primaryKey() => $key]);
            }
        }    
    }

    //rollback
    public function down()
    {
       $this->execute('DROP TABLE IF EXISTS `security_roles`');
       $this->execute('RENAME TABLE `z_6223_security_roles` TO `security_roles`');
    }
}
