<?php
use Migrations\AbstractMigration;

class POCOR9407 extends AbstractMigration
{
    public function up()
    {
       $this->execute('CREATE TABLE `zz_9407_institution_custom_field_values` LIKE `institution_custom_field_values`');
       $this->execute('INSERT INTO `zz_9407_institution_custom_field_values` SELECT * FROM `institution_custom_field_values`');
       
       // Add file_name column if not exists
        $table = $this->table('institution_custom_field_values');
        if (!$table->hasColumn('file_name')) {
            $table->addColumn('file_name', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'file'
            ])->update();
        }

    }

    public function down()
    {
        // Step 1: Drop modified table
        $this->execute('DROP TABLE IF EXISTS `institution_custom_field_values`');

        // Step 2: Restore original from backup
        $this->execute('RENAME TABLE `zz_9407_institution_custom_field_values` TO `institution_custom_field_values`');
    }
}
