<?php

use Phinx\Migration\AbstractMigration;

class POCOR4124 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {   
        //import_mapping
        $table = $this->table('import_mapping');
        $table->addColumn('is_optional', 'integer', [
                    'after' => 'order', 
                    'limit' => 1,
                    'default' => 0, 
                    'null' => false
                ])
              ->save();

        $sql = "UPDATE `import_mapping`
                SET is_optional = 1
                WHERE description LIKE '%optional%'";

        $this->execute($sql);

        $sql = "UPDATE `import_mapping` 
                SET `description` = 'Code' 
                WHERE `model` = 'Training.TrainingSessionsTrainees'
                AND `column_name` = 'identity_type_id'";

        $this->execute($sql);

        $sql = "UPDATE `import_mapping` 
                SET `description` = NULL 
                WHERE `model` = 'Training.TrainingSessionsTrainees'
                AND `column_name` = 'openemis_no'";

        $this->execute($sql);
        //import_mapping
    }

    // rollback
    public function down()
    {
        //import_mapping
        $table = $this->table('import_mapping');
        $table->removeColumn('is_optional')
              ->save();

        $sql = "UPDATE `import_mapping` 
                SET `description` = 'Code (Optional)' 
                WHERE `model` = 'Training.TrainingSessionsTrainees'
                AND `column_name` = 'identity_type_id'";

        $this->execute($sql);

        $sql = "UPDATE `import_mapping` 
                SET `description` = '(Optional)'
                WHERE `model` = 'Training.TrainingSessionsTrainees'
                AND `column_name` = 'openemis_no'";

        $this->execute($sql);
        //import_mapping
    }
}
