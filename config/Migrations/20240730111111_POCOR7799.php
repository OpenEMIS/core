<?php

use Phinx\Migration\AbstractMigration;

class POCOR7799 extends AbstractMigration
{
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_7799_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_7799_import_mapping` SELECT * FROM `import_mapping`');

        $this->execute("DELETE FROM import_mapping WHERE
                               `model` = 'Institution.InstitutionPositions' AND
                               `column_name` = 'staff_position_grade_id'");
        $this->execute("DELETE FROM import_mapping WHERE
                               `model` = 'Institution.InstitutionPositions' AND
                               `column_name` = 'is_homeroom'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_7799_import_mapping` TO `import_mapping`');
    }
}
