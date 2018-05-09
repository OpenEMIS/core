<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class POCOR4569 extends AbstractMigration
{
    public function up()
    {
        // backup the table
        // institution_staff_appraisals
        $this->execute('RENAME TABLE `institution_staff_appraisals` TO `z_4569_institution_staff_appraisals`');
        $this->execute('DROP TABLE IF EXISTS institution_staff_appraisals');

        $StaffAppraisals = $this->table('institution_staff_appraisals', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of appraisals for a specific staff'
        ]);

        $StaffAppraisals
            ->addColumn('title', 'string', [
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('appraisal_period_from', 'date', [
                'null' => false
            ])
            ->addColumn('appraisal_period_to', 'date', [
                'null' => false
            ])
            ->addColumn('date_appraised', 'date', [
                'null' => false
            ])
            ->addColumn('file_name', 'string', [
                'limit' => 250,
                'null' => true,
                'default' => null
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => MysqlAdapter::BLOB_LONG,
                'null' => true,
                'default' => null
            ])
            ->addColumn('comment', 'text', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('staff_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('appraisal_type_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to appraisal_types.id'
            ])
            ->addColumn('appraisal_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to appraisal_periods.id'
            ])
            ->addColumn('appraisal_form_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to appraisal_forms.id'
            ])
            ->addColumn('assignee_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => 0,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('status_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to workflow_steps.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'limit' => 11,
                'null' => true,
                'default' => null
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('created_user_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => null
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => null
            ])
            ->addIndex('institution_id')
            ->addIndex('staff_id')
            ->addIndex('appraisal_form_id')
            ->addIndex('appraisal_type_id')
            ->addIndex('appraisal_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->execute('
            INSERT INTO `institution_staff_appraisals` 
                (`id`, `title`, `appraisal_period_from`, `appraisal_period_to`, `date_appraised`, `file_name`, `file_content`, `comment`, `institution_id`, `staff_id`, `appraisal_type_id`, `appraisal_period_id`, `appraisal_form_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            SELECT `id`, `title`, `from`, `to`, `created`, `file_name`, `file_content`, `comment`, `institution_id`, `staff_id`, `appraisal_type_id`, `appraisal_period_id`, `appraisal_form_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
            FROM `z_4569_institution_staff_appraisals`
        ');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS institution_staff_appraisals');
        $this->execute('RENAME TABLE `z_4569_institution_staff_appraisals` TO `institution_staff_appraisals`');
    }
}
