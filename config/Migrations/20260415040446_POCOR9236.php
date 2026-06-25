<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9236 extends AbstractMigration
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
        // Step 1: Backup original table
        $this->execute('CREATE TABLE `zz_9236_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_9236_import_mapping` SELECT * FROM `import_mapping`');

        // Step 2: uPDATE existing 'examination_item_id' to 'examination_subject_id' and update lookup_model to 'ExaminationSubjects'
        $this->execute("
            UPDATE import_mapping
            SET `column_name` = 'examination_subject_id',
                `lookup_model` = 'ExaminationSubjects'
            WHERE model = 'Examination.ExaminationItemResults'
            AND column_name = 'examination_item_id'
            AND lookup_model = 'ExaminationItems';
        ");
        $result = $this->fetchAll("
            SELECT MAX(`order`) AS max_order
            FROM `import_mapping`
            WHERE `model` = 'Examination.ExaminationStudentSubjectResults'
        ");
        $maxOrder = $result[0]['max_order'] ?? 0;
        // Step 3: Insert new mapping for 'examination_item_id' with lookup_model 'ExaminationItems'
        $this->execute("
            INSERT INTO import_mapping (
                model,
                column_name,
                description,
                `order`,
                is_optional,
                foreign_key,
                lookup_plugin,
                lookup_model,
                lookup_column
            )
            VALUES (
                'Examination.ExaminationStudentSubjectResults',
                'examination_subject_id',
                Id,
                " . ($maxOrder + 1) . ",
                0,
                2,
                'Examination',
                'ExaminationSubjects',
                'id'
            )
        ");

        $this->execute("
            INSERT INTO import_mapping (
                model,
                column_name,
                description,
                `order`,
                is_optional,
                foreign_key,
                lookup_plugin,
                lookup_model,
                lookup_column
            )
            VALUES (
                'Examination.ExaminationStudentSubjectResults',
                'examination_grading_option_id',
                Id,
                " . ($maxOrder + 2) . ",
                0,
                1,
                'Examination',
                'ExaminationGradingOptions',
                'id'
            )
        ");
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_9236_import_mapping` TO `import_mapping`');
    }
}
