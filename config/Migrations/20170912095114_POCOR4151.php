<?php

use Phinx\Migration\AbstractMigration;

class POCOR4151 extends AbstractMigration
{
    // commit
    public function up()
    {
        // security_functions
        $studentsSql = "UPDATE security_functions
                        SET `controller` = 'StudentComments',
                        `_view` = 'index|view',
                        `_edit` = 'edit',
                        `_add` = 'add',
                        `_delete` = 'delete'
                        WHERE `id` = 2005";

        $staffSql = "UPDATE security_functions
                        SET `controller` = 'StaffComments',
                        `_view` = 'index|view',
                        `_edit` = 'edit',
                        `_add` = 'add',
                        `_delete` = 'delete'
                        WHERE `id` = 3005";

        $directorySql = "UPDATE security_functions
                        SET `controller` = 'DirectoryComments',
                        `_view` = 'index|view',
                        `_edit` = 'edit',
                        `_add` = 'add',
                        `_delete` = 'delete'
                        WHERE `id` = 7005";

        $this->execute($studentsSql);
        $this->execute($staffSql);
        $this->execute($directorySql);
    }

    // rollback
    public function down()
    {
        // security_functions
        $studentsSql = "UPDATE security_functions
                        SET `controller` = 'Students',
                        `_view` = 'Comments.index|Comments.view',
                        `_edit` = 'Comments.edit',
                        `_add` = 'Comments.add',
                        `_delete` = 'Comments.remove'
                        WHERE `id` = 2005";

        $staffSql = "UPDATE security_functions
                        SET `controller` = 'Staff',
                        `_view` = 'Comments.index|Comments.view',
                        `_edit` = 'Comments.edit',
                        `_add` = 'Comments.add',
                        `_delete` = 'Comments.remove'
                        WHERE `id` = 3005";

        $directorySql = "UPDATE security_functions
                        SET `controller` = 'Directories',
                        `_view` = 'Comments.index|Comments.view',
                        `_edit` = 'Comments.edit',
                        `_add` = 'Comments.add',
                        `_delete` = 'Comments.remove'
                        WHERE `id` = 7005";

        $this->execute($studentsSql);
        $this->execute($staffSql);
        $this->execute($directorySql);
    }
}
