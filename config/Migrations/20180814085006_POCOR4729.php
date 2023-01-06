<?php

use Phinx\Migration\AbstractMigration;

class POCOR4729 extends AbstractMigration
{
   public function up()
    {
        $this->execute("UPDATE import_mapping SET `order` = `order` + 1 WHERE `model` = 'Institution.Institutions' AND `order` >= 4");

        $data = [
            'model'          => 'Institution.Institutions',
            'column_name'    => 'classification',
            'order'          => 4,
            'foreign_key'    => 3,
            'lookup_model'   => 'Classification',
            'lookup_column'  => 'id',
        ];

        $table = $this->table('import_mapping');
        $table->insert($data);
        $table->saveData();
    }

    public function down()
    { 
        $this->execute("UPDATE import_mapping SET `order` = `order` - 1 WHERE `order` > 4 AND `model` = 'Institution.Institutions'");
        $this->execute("DELETE FROM import_mapping WHERE `model` = 'Institution.Institutions' AND `column_name` = 'classification'");
    }
}
