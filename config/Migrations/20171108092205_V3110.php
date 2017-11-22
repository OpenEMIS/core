<?php
use Migrations\AbstractMigration;

class V3110 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('examination_centres');
        $table->changeColumn('telephone', 'string', ['limit' => 30]);
        $table->changeColumn('fax', 'string', ['limit' => 30]);
        $table->save();
    }

    public function down()
    {
        $table = $this->table('examination_centres');
        $table->changeColumn('telephone', 'string', ['limit' => 20]);
        $table->changeColumn('fax', 'string', ['limit' => 20]);
        $table->save();
    }
}
