<?php
use Migrations\AbstractMigration;

class POCOR7507 extends AbstractMigration
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
        $this->execute('UPDATE `config_items` SET `type`="External Data Source - Identity" WHERE `code`="external_data_source_type" AND `type`="External Data Source"');
    }
    public function down()
    {
        $this->execute('UPDATE `config_items` SET `type`="External Data Source - Identity" WHERE `code`="external_data_source_type" AND `type`="External Data Source Identity"');
    }
}
