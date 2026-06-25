<?php

use Migrations\AbstractMigration;

class POCOR8773 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8773_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_8773_security_functions` SELECT * FROM `security_functions`');
        //  add permission in security function

        $createdAt = (new DateTime())->format('Y-m-d H:i:s');
        $order = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parent_id = $parent_id[0] + 1;
        $order = $order[0] + 1;

        $record = [
            [
                'name' => 'Report Card Progress', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Report Cards', 'parent_id' => $parent_id,'_view' => 'ReportCardStatusProgress.index|ReportCardStatusProgress.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => $createdAt,
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

    }

    public function down()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8773_security_functions` TO `security_functions`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
