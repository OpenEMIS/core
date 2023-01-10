<?php
use Migrations\AbstractMigration;

class POCOR4293 extends AbstractMigration
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
        $this->execute("DELETE FROM phinxlog WHERE migration_name = 'V3120'");
    }
}
