<?php

use Phinx\Migration\AbstractMigration;

class POCOR4624 extends AbstractMigration
{
    public function up()
    {
        // create backup for security_roles
        $this->execute('CREATE TABLE `z_4624_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `z_4624_security_roles` SELECT * FROM `security_roles`');
        // Gets the current order for PRINCIPAL
        $row = $this->fetchRow('SELECT `order` FROM `security_roles` WHERE `code` = "PRINCIPAL"');
        $order = $row['order'];
        $this->execute('UPDATE security_roles SET `order` = `order` + 1 WHERE `security_group_id`= -1 AND `order` > '. $order);

        $this->insert('security_roles', [
            'name' => 'Deputy Principal',
            'code' => 'DEPUTY_PRINCIPAL',
            'order' => $order + 1,
            'visible' => 1,
            'security_group_id' => -1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_roles`');
        $this->execute('RENAME TABLE `z_4624_security_roles` TO `security_roles`');
    }
}
