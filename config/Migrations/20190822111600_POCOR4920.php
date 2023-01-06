<?php

use Phinx\Migration\AbstractMigration;

class POCOR4920 extends AbstractMigration {

    public function up() {
        $this->execute('INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
            VALUES ("Institution.StaffLeave", "academic_period_id", "Code", 6, 0, 2, "AcademicPeriod", "AcademicPeriods", "code")');
    }

    public function down() {
        $this->execute('DELETE FROM `import_mapping` where model="Institution.StaffLeave" and column_name="academic_period_id"');
    }

}
