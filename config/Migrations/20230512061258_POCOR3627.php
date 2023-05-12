<?php
use Migrations\AbstractMigration;

class POCOR3627 extends AbstractMigration
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
        $this->execute("UPDATE security_functions SET `_execute` = 'Textbooks.excel' WHERE `name`='Textbooks' and `controller`='Institutions' and `module`='Institutions' and `category`='Academic'");
    }

    // rollback
    public function down()
    {
        $this->execute("UPDATE security_functions SET `_execute` = '' WHERE `name`='Textbooks' and `controller`='Institutions' and `module`='Institutions' and `category`='Academic'");
    }
}
