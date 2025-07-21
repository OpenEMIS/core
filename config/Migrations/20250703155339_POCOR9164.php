<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9164 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE TABLE `zz_9164_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_9164_config_items` SELECT * FROM `config_items`');


        $this->execute("
            INSERT INTO `config_items` (
                `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, 
                `editable`, `visible`, `field_type`, `option_type`, 
                `modified_user_id`, `modified`, `created_user_id`, `created`
            )
            VALUES (
                'Auto Generated Candidate Number',
                'auto_generated_candidate_number',
                'Auto Generated Candidate Number',
                'Candidate Number Prefix',
                '1',
                '\${area_code}/\${institution_code}/\${academic_period_code}/\${4}',
                '0',
                0,
                1,
                'Dropdown',
                'yes_no',
                2,
                NOW(),
                1,
                NOW()
            )
        ");


        $this->execute("ALTER TABLE institution_student_programmes MODIFY registration_number VARCHAR(200);");
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_9164_config_items` TO `config_items`');
    }
}
