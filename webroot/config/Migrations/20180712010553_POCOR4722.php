<?php

use Phinx\Migration\AbstractMigration;

class POCOR4722 extends AbstractMigration
{
    public function up()
    {
        $this->table('institutions')->rename('z_4722_institutions');           
        $this->execute('CREATE TABLE `institutions` LIKE `z_4722_institutions`');
        $this->table('institutions')->changeColumn('longitude', 'string', [
                'limit' => 25,
                'null' =>true,
            ])
            ->save();
        $this->table('institutions')->changeColumn('latitude', 'string', [
                'limit' => 25,
                'null' =>true,
            ])
            ->save();
        $this->execute('INSERT INTO `institutions` SELECT * FROM `z_4722_institutions`');
    }

    public function down()
    {
        $this->dropTable('institutions');
        $this->table('z_4722_institutions')->rename('institutions');
    }
}
