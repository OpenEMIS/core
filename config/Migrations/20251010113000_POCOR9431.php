<?php

use Migrations\AbstractMigration;

class POCOR9431 extends AbstractMigration
{
    public function up()
    {
        // Temporarily disable foreign key checks
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Backup original table (just the affected rows for clarity and safety)
        $this->execute('DROP TABLE IF EXISTS `z_9431_institution_classes_secondary_staff`');
        $this->execute('
            CREATE TABLE `z_9431_institution_classes_secondary_staff` LIKE `institution_classes_secondary_staff`
        ');
        $this->execute('
            INSERT INTO `z_9431_institution_classes_secondary_staff`
            SELECT *
            FROM institution_classes_secondary_staff');

        // Perform the actual cleanup: remove secondary staff also listed as main staff
        $this->execute('
            DELETE icss
            FROM institution_classes_secondary_staff AS icss
            INNER JOIN institution_classes AS ic
                ON ic.id = icss.institution_class_id
            WHERE icss.secondary_staff_id = ic.staff_id
        ');

        // Re-enable foreign key checks
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        // Restore from backup if available
        if ($this->hasTable('z_9431_institution_classes_secondary_staff')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');

            // Restore deleted
            $this->execute('DROP TABLE IF EXISTS `institution_classes_secondary_staff`');
            $this->execute('RENAME TABLE `z_9431_institution_classes_secondary_staff` TO `institution_classes_secondary_staff`');

            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
