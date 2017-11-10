<?php

use Phinx\Migration\AbstractMigration;

class POCOR3125 extends AbstractMigration
{
    // commit
    public function up()
    {
        // security_functions
        $this->execute('
            UPDATE `security_functions`
            SET `controller` = "InstitutionHistories",
                `_view` = "index"
            WHERE `id` = 1001
        ');

        $this->execute('
            UPDATE `security_functions`
            SET `controller` = "StudentHistories",
                `_view` = "index"
            WHERE `id` = 2009
        ');

        $this->execute('
            UPDATE `security_functions`
            SET `controller` = "StaffHistories",
                `_view` = "index"
            WHERE `id` = 3009
        ');

        $this->execute('
            UPDATE `security_functions`
            SET `controller` = "DirectoryHistories",
                `_view` = "index",
                `_edit` = NULL,
                `_add` = NULL,
                `_delete` = NULL
            WHERE `id` = 7008
        ');
        // end security_functions
    }

    // rollback
    public function down()
    {
        // security_functions
        $this->execute('
            UPDATE `security_functions`
            SET `controller` = "Institutions",
                `_view` = "History.index"
            WHERE `id` = 1001
        ');

        $this->execute('
            UPDATE `security_functions`
            SET `controller` = "Students",
                `_view` = "History.index"
            WHERE `id` = 2009
        ');

        $this->execute('
            UPDATE `security_functions`
            SET `controller` = "Staff",
                `_view` = "History.index"
            WHERE `id` = 3009
        ');

        $this->execute('
            UPDATE `security_functions`
            SET `controller` = "Students",
                `_view` = "History.index|History.view",
                `_edit` = "History.edit",
                `_add` = "History.add",
                `_delete` = "History.remove"
            WHERE `id` = 7008
        ');
        // end security_functions
    }
}
