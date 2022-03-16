<?php
use Migrations\AbstractMigration;

class POCOR6616 extends AbstractMigration
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
        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_6616_import_mapping`');
        $this->execute('CREATE TABLE `zz_6616_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_6616_import_mapping` SELECT * FROM `import_mapping`');

        //deleting academic period in import compatancy section
        $this->execute('DELETE FROM `import_mapping` WHERE `model` = "Competency.CompetencyTemplates" And `column_name` = "academic_period_id"');
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_6616_import_mapping` TO `import_mapping`');
    }
}
