<?php

declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Utility\Text;
use Cake\I18n\FrozenTime;

class POCOR9033 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE IF NOT EXISTS `z_9033_labels` LIKE `labels`');
        $this->execute('INSERT IGNORE INTO `z_9033_labels` SELECT * FROM `labels`');

        $this->execute("ALTER TABLE `outcome_criterias` CHANGE `code` `code` TEXT NULL");
        $labels = $this->table('labels');
        $now    = FrozenTime::now();

        $labels->insert([
            [
                'id'              => Text::uuid(),
                'module'          => 'InstitutionsSurvey',
                'field'           => 'institution_surveys',
                'module_name'     => 'Institutions>Survey',
                'field_name'      => 'Institution Surveys',
                'created_user_id' => 1,
                'created'         => $now,
            ],
            [
                'id'              => Text::uuid(),
                'module'          => 'InstitutionsSurvey',
                'field'           => 'survey',
                'module_name'     => 'Institutions>Survey',
                'field_name'      => 'Survey',
                'created_user_id' => 1,
                'created'         => $now,
            ],
        ])->save();
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `z_9033_labels` TO `labels`');
    }
}
