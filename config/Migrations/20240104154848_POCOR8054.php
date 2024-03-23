<?php

use Phinx\Migration\AbstractMigration;

class POCOR8054 extends AbstractMigration
{
    public function up()
    {
        //backup
//        $this->execute('CREATE TABLE `z_8054_security_functions` LIKE `security_functions`');
//        $this->execute('INSERT INTO `z_8054_security_functions` SELECT * FROM `security_functions`');

        //change security functions
        $this->execute("UPDATE `security_functions` 
SET `category` = 'Data Management' 
WHERE `category` = 'Archive' ");
        $this->execute("UPDATE `security_functions` 
SET `_view` = 'TransferLogs.index|TransferLogs.view|Transfer.view|Transfer.index',
    `_edit` = NULL,
    `_add` = NULL,
    `_execute` = 'TransferLogs.add|TransferLogs.add|Transfer.add|Transfer.add',
    `_delete` = NULL
WHERE `category` = 'Data Management' AND `name` = 'Archive' ");
        $this->execute("UPDATE `security_functions` 
SET `_view` = 'DataManagementCopy.index|DataManagementCopy.view|DataManagementCopy.index|CopyData.view||CopyData.index',
    `_edit` = NULL,
    `_add` = NULL,
    `_execute` = 'DataManagementCopy.add|DataManagementCopy.add|DataManagementCopy.add|CopyData.add||CopyData.add',
    `_delete` = NULL 
WHERE `category` = 'Data Management' AND `name` = 'Copy' ");
        $this->execute("UPDATE `security_functions` 
SET `_view` = 'BackupLogs.index|BackupLogs.view|BackupLog.view|BackupLog.index',
    `_edit` = NULL,
    `_add` = NULL,
    `_execute` = 'BackupLogs.add|BackupLogs.add|BackupLog.add|BackupLog.add',
    `_delete` = NULL 
WHERE `category` = 'Data Management' AND `name` = 'Backup' ");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8054_security_functions` TO `security_functions`');
    }
}
