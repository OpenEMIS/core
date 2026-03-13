<?php
use Migrations\AbstractMigration;

class POCOR9475 extends AbstractMigration
{
    public function up()
    {

        //Backup existing tables
         
        $this->execute('DROP TABLE IF EXISTS `z_9475_infrastructure_utility_electricities`');
        $this->execute('CREATE TABLE `z_9475_infrastructure_utility_electricities`
                        LIKE `infrastructure_utility_electricities`');
        $this->execute('INSERT INTO `z_9475_infrastructure_utility_electricities`
                        SELECT * FROM `infrastructure_utility_electricities`');

        $this->execute('DROP TABLE IF EXISTS `z_9475_infrastructure_utility_internets`');
        $this->execute('CREATE TABLE `z_9475_infrastructure_utility_internets`
                        LIKE `infrastructure_utility_internets`');
        $this->execute('INSERT INTO `z_9475_infrastructure_utility_internets`
                        SELECT * FROM `infrastructure_utility_internets`');

        $this->execute('DROP TABLE IF EXISTS `z_9475_infrastructure_utility_telephones`');
        $this->execute('CREATE TABLE `z_9475_infrastructure_utility_telephones`
                        LIKE `infrastructure_utility_telephones`');
        $this->execute('INSERT INTO `z_9475_infrastructure_utility_telephones`
                        SELECT * FROM `infrastructure_utility_telephones`');

        
        // Add new columns (nullable first)

        $tables = [
            'infrastructure_utility_electricities',
            'infrastructure_utility_internets',
            'infrastructure_utility_telephones'
        ];

        foreach ($tables as $table) {
            $this->execute("
                ALTER TABLE `$table`
                ADD COLUMN `start_date` DATE NULL AFTER `academic_period_id`,
                ADD COLUMN `end_date` DATE NULL AFTER `start_date`,
                ADD COLUMN `is_current` TINYINT(1) NOT NULL DEFAULT 0 AFTER `end_date`
            ");
        }
        
         //fill start_date from academic_periods

        $this->execute("
            UPDATE infrastructure_utility_electricities e
            JOIN academic_periods ap ON ap.id = e.academic_period_id
            SET 
                e.start_date = ap.start_date,
                e.end_date   = ap.end_date
        ");

        $this->execute("
            UPDATE infrastructure_utility_internets i
            JOIN academic_periods ap ON ap.id = i.academic_period_id
            SET 
                i.start_date = ap.start_date,
                i.end_date   = ap.end_date
        ");

        $this->execute("
            UPDATE infrastructure_utility_telephones t
            JOIN academic_periods ap ON ap.id = t.academic_period_id
            SET 
                t.start_date = ap.start_date,
                t.end_date   = ap.end_date
        ");

        $this->execute("UPDATE infrastructure_utility_electricities SET is_current = 0");
        $this->execute("UPDATE infrastructure_utility_internets SET is_current = 0");
        $this->execute("UPDATE infrastructure_utility_telephones SET is_current = 0");

        
        //Set is_current using date-range form academic_periods table

        $this->execute("
            UPDATE infrastructure_utility_electricities e
            JOIN (
                SELECT MAX(id) AS id
                FROM infrastructure_utility_electricities
                GROUP BY institution_id, academic_period_id, utility_electricity_type_id
            ) latest ON latest.id = e.id
            SET e.is_current = 1;");

        $this->execute("
            UPDATE infrastructure_utility_internets i
            JOIN (
                SELECT MAX(id) AS id
                FROM infrastructure_utility_internets
                GROUP BY institution_id, academic_period_id
            ) latest ON latest.id = i.id
            SET i.is_current = 1;");

        $this->execute("
            UPDATE infrastructure_utility_telephones t
            JOIN (
                SELECT MAX(id) AS id
                FROM infrastructure_utility_telephones
                GROUP BY institution_id, academic_period_id
            ) latest ON latest.id = t.id
            SET t.is_current = 1;");
        
        //Enforce NOT NULL on start_date
        
        foreach ($tables as $table) {
            $this->execute("
                ALTER TABLE `$table`
                MODIFY `start_date` DATE NOT NULL
            ");
        }
    }

    public function down()
    {
        //Restore original tables from backup
         
        $this->execute('DROP TABLE IF EXISTS `infrastructure_utility_electricities`');
        $this->execute('RENAME TABLE `z_9475_infrastructure_utility_electricities`
                        TO `infrastructure_utility_electricities`');

        $this->execute('DROP TABLE IF EXISTS `infrastructure_utility_internets`');
        $this->execute('RENAME TABLE `z_9475_infrastructure_utility_internets`
                        TO `infrastructure_utility_internets`');

        $this->execute('DROP TABLE IF EXISTS `infrastructure_utility_telephones`');
        $this->execute('RENAME TABLE `z_9475_infrastructure_utility_telephones`
                        TO `infrastructure_utility_telephones`');
    }
}
