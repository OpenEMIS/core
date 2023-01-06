<?php
use Migrations\AbstractMigration;

class POCOR5172 extends AbstractMigration
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
        $this->execute('UPDATE security_functions SET `_execute` = "Classes.excel" WHERE `id` = 1007');
        $this->execute('UPDATE security_functions SET `_execute` = "Subjects.excel" WHERE `id` = 1009');
        $this->execute('UPDATE security_functions SET `_execute` = "Subjects.excel" WHERE `id` = 1010');
    }

    // rollback
    public function down()
    {
        $this->execute('UPDATE security_functions SET `_execute` = NULL WHERE `id` = 1007');
        $this->execute('UPDATE security_functions SET `_execute` = NULL WHERE `id` = 1009');
        $this->execute('UPDATE security_functions SET `_execute` = NULL WHERE `id` = 1010');
    }
}
