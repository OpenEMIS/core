<?php
use Migrations\AbstractMigration;

class POCOR4340 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('appraisal_criterias', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of staff appraisal criterias'
        ]);
        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 45
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('field_type_id', 'integer', [
                'null' => false,
                'comment' => 'links to field_types.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('field_type_id')
            ->save();

        $table = $this->table('appraisal_forms', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of staff appraisal forms'
        ]);
        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 45
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->save();

        $table = $this->table('appraisal_forms_criterias', [
            'id' => false,
            'primary_key' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of criterias linked to a specific staff appraisal form'
        ]);
        $table
            ->addColumn('id', 'string', [
                'limit' => 64,
                'null' => false
            ])
            ->addColumn('appraisal_form_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_forms.id'
            ])
            ->addColumn('appraisal_criteria_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_criterias.id'
            ])
            ->addColumn('section', 'string', [
                'null' => true,
                'limit' => 250
            ])
            ->addColumn('order', 'integer', [
                'null' => false
            ])
            ->addIndex('appraisal_form_id')
            ->addIndex('appraisal_criteria_id')
            ->save();

        $table = $this->table('appraisal_periods', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of periods for a specific staff appraisal form'
        ]);
        $table
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('appraisal_form_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_forms.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('date_enabled', 'date', [
                'null' => false
            ])
            ->addColumn('date_disabled', 'date', [
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('appraisal_form_id')
            ->addIndex('academic_period_id')
            ->save();

        $table = $this->table('appraisal_periods_types', [
            'id' => false,
            'primary_key' => ['appraisal_period_id', 'appraisal_type_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of appraisal types linked to a specific staff appraisal period'
        ]);
        $table
            ->addColumn('appraisal_period_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_periods.id'
            ])
            ->addColumn('appraisal_type_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_types.id'
            ])
            ->addIndex('appraisal_period_id')
            ->addIndex('appraisal_type_id')
            ->save();

        $table = $this->table('appraisal_slider_answers', [
            'id' => false,
            'primary_key' => ['appraisal_form_id', 'appraisal_criteria_id', 'institution_staff_appraisal_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the slider answers recorded for a specific institution staff appraisal'
        ]);
        $table
            ->addColumn('appraisal_form_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_forms.id'
            ])
            ->addColumn('appraisal_criteria_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_criterias.id'
            ])
            ->addColumn('institution_staff_appraisal_id', 'integer', [
                'null' => false,
                'comment' => 'links to institution_staff_appraisals.id'
            ])
            ->addColumn('answer', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('appraisal_form_id')
            ->addIndex('appraisal_criteria_id')
            ->addIndex('institution_staff_appraisal_id')
            ->save();

        $table = $this->table('appraisal_sliders', [
            'id' => false,
            'primary_key' => ['appraisal_criteria_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the slider configurations set for a specific staff appraisal criteria'
        ]);
        $table
            ->addColumn('appraisal_criteria_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_criterias.id'
            ])
            ->addColumn('min', 'decimal', [
                'null' => false,
                'precision' => 5,
                'scale' => 2
            ])
            ->addColumn('max', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false
            ])
            ->addColumn('step', 'decimal', [
                'precision' => 3,
                'scale' => 2,
                'null' => false
            ])
            ->addIndex('appraisal_criteria_id')
            ->save();

        $table = $this->table('appraisal_text_answers', [
            'id' => false,
            'primary_key' => ['appraisal_form_id', 'appraisal_criteria_id', 'institution_staff_appraisal_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the text answers recorded for a specific institution staff appraisal'
        ]);
        $table
            ->addColumn('appraisal_form_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_forms.id'
            ])
            ->addColumn('appraisal_criteria_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_criterias.id'
            ])
            ->addColumn('institution_staff_appraisal_id', 'integer', [
                'null' => false,
                'comment' => 'links to institution_staff_appraisals.id'
            ])
            ->addColumn('answer', 'text', [
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('appraisal_form_id')
            ->addIndex('appraisal_criteria_id')
            ->addIndex('institution_staff_appraisal_id')
            ->save();

        $table = $this->table('appraisal_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of staff appraisal types'
        ]);
        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 100
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->save();

        $table = $this->table('institution_staff_appraisals', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of appraisals for a specific staff'
        ]);
        $table
            ->addColumn('title', 'string', [
                'null' => false,
                'limit' => 100
            ])
            ->addColumn('from', 'date', [
                'null' => false
            ])
            ->addColumn('to', 'date', [
                'null' => false
            ])
            ->addColumn('file_name', 'string', [
                'null' => true,
                'limit' => 250
            ])
            ->addColumn('file_content', 'blob', [
                'null' => true,
                'limit' => 4294967295
            ])
            ->addColumn('comment', 'text', [
                'null' => true
            ])
            ->addColumn('institution_id', 'integer', [
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('staff_id', 'integer', [
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('appraisal_type_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_types.id'
            ])
            ->addColumn('appraisal_period_id', 'integer', [
                'null' => false,
                'comment' => 'links to appraisal_periods.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('institution_id')
            ->addIndex('staff_id')
            ->addIndex('appraisal_type_id')
            ->addIndex('appraisal_period_id')
            ->save();

        $table = $this->table('field_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of available custom field types that are used in the system'
        ]);
        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 45
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 45
            ])
            ->save();

        $table
            ->insert([
                [
                    'code' => 'TEXTAREA',
                    'name' => 'Textarea'
                ],
                [
                    'code' => 'SLIDER',
                    'name' => 'Slider'
                ]
            ])
            ->save();

        $this->table('competency_sets')->rename('z_4340_competency_sets');
        $this->table('competencies')->rename('z_4340_competencies');
        $this->table('competency_sets_competencies')->rename('z_4340_competency_sets_competencies');
        $this->table('staff_appraisals')->rename('z_4340_staff_appraisals');
        $this->table('staff_appraisals_competencies')->rename('z_4340_staff_appraisals_competencies');
        $this->table('staff_appraisal_types')->rename('z_4340_staff_appraisal_types');

        // appraisal_forms
        $this->execute("INSERT INTO `appraisal_forms`
                            (`id`, `code`, `name`, `modified_user_id`, `modified`,  `created_user_id`, `created`)
                        SELECT
                            `id`, `id`, `name`, `modified_user_id`, `modified`,  `created_user_id`, `created`
                        FROM `z_4340_competency_sets`"
                    );

        // appraisal_criterias
        $this->execute("INSERT INTO `appraisal_criterias`
                            (`id`, `code`, `name`, `field_type_id`,
                            `modified_user_id`, `modified`,  `created_user_id`, `created`)
                        SELECT
                            `id`, `id`, `name`, (SELECT `id` FROM field_types WHERE `code` = 'SLIDER'),
                            `modified_user_id`, `modified`,  `created_user_id`, `created`
                        FROM `z_4340_competencies`"
                    );

        // appraisal_forms_criterias
        $this->execute("INSERT INTO `appraisal_forms_criterias`
                            (`id`, `appraisal_form_id`, `appraisal_criteria_id`, `order`)
                        SELECT
                            sha2(CONCAT(z_comp_sets_comp.`competency_set_id`, ',', z_comp_sets_comp.`competency_id`), '256'),
                            z_comp_sets_comp.`competency_set_id`,
                            z_comp_sets_comp.`competency_id`,
                            z_comp.`order`
                        FROM `z_4340_competency_sets_competencies` z_comp_sets_comp
                        INNER JOIN `z_4340_competencies` z_comp ON z_comp_sets_comp.`competency_id` = z_comp.`id`");

        // appraisal_sliders
        $this->execute("INSERT INTO `appraisal_sliders`
                            (`appraisal_criteria_id`, `min`, `max`, `step`)
                        SELECT
                            `id`, `min`, `max`, '0.5'
                        FROM `z_4340_competencies`");

        // appraisal_types
        $this->execute("INSERT INTO `appraisal_types` SELECT * FROM `z_4340_staff_appraisal_types`");

        // appraisal_periods
        $this->execute("INSERT INTO `appraisal_periods` (
                            `name`,
                            `appraisal_form_id`,
                            `academic_period_id`,
                            `date_enabled`,
                            `date_disabled`,
                            `modified_user_id`,
                            `modified`,
                            `created_user_id`,
                            `created`
                        )
                        SELECT
                            CONCAT(acad_periods.`name`, ' - ', z_comp_sets.`name`),
                            z_staff_appr.`competency_set_id`,
                            z_staff_appr.`academic_period_id`,
                            acad_periods.`start_date`,
                            acad_periods.`end_date`,
                            z_staff_appr.`modified_user_id`,
                            z_staff_appr.`modified`,
                            z_staff_appr.`created_user_id`,
                            z_staff_appr.`created`
                        FROM `z_4340_staff_appraisals` z_staff_appr
                        INNER JOIN `academic_periods` acad_periods ON z_staff_appr.`academic_period_id` = acad_periods.`id`
                        INNER JOIN `z_4340_competency_sets` z_comp_sets ON z_staff_appr.`competency_set_id` = z_comp_sets.`id`
                        GROUP BY z_staff_appr.`competency_set_id`, z_staff_appr.`academic_period_id`
                    ");

        // appraisal_periods_types
        $this->execute("INSERT INTO `appraisal_periods_types`
                            (`appraisal_period_id`, `appraisal_type_id`)
                        SELECT
                            appr_periods.`id`, z_staff_appr.`staff_appraisal_type_id`
                        FROM `z_4340_staff_appraisals` z_staff_appr
                        INNER JOIN `appraisal_periods` appr_periods
                            ON z_staff_appr.`competency_set_id` = appr_periods.`appraisal_form_id`
                            AND z_staff_appr.`academic_period_id` = appr_periods.`academic_period_id`
                        GROUP BY appr_periods.`id`, z_staff_appr.`staff_appraisal_type_id`
                    ");

        // institution_staff_appraisals
        $this->execute("INSERT INTO `institution_staff_appraisals` (
                            `id`,
                            `title`,
                            `from`,
                            `to`,
                            `file_name`,
                            `file_content`,
                            `comment`,
                            `institution_id`,
                            `staff_id`,
                            `appraisal_type_id`,
                            `appraisal_period_id`,
                            `modified_user_id`,
                            `modified`,
                            `created_user_id`,
                            `created`
                        )
                        SELECT
                            z_staff_appr.`id`,
                            z_staff_appr.`title`,
                            z_staff_appr.`from`,
                            z_staff_appr.`to`,
                            z_staff_appr.`file_name`,
                            z_staff_appr.`file_content`,
                            z_staff_appr.`comment`,
                            staff.`institution_id`,
                            z_staff_appr.`staff_id`,
                            z_staff_appr.`staff_appraisal_type_id`,
                            appr_periods.`id`,
                            z_staff_appr.`modified_user_id`,
                            z_staff_appr.`modified`,
                            z_staff_appr.`created_user_id`,
                            z_staff_appr.`created`
                        FROM `z_4340_staff_appraisals` z_staff_appr
                        INNER JOIN `appraisal_periods` appr_periods
                            ON z_staff_appr.`competency_set_id` = appr_periods.`appraisal_form_id`
                            AND z_staff_appr.`academic_period_id`=  appr_periods.`academic_period_id`
                        INNER JOIN `institution_staff` staff ON z_staff_appr.`staff_id` = staff.`staff_id`
                        INNER JOIN `staff_statuses` statuses ON staff.`staff_status_id` = statuses.`id`
                        WHERE statuses.`code` = 'ASSIGNED'
                        GROUP BY z_staff_appr.`id`
                    ");

        // appraisal_slider_answers
        $this->execute("INSERT INTO `appraisal_slider_answers`
                        (`appraisal_form_id`, `appraisal_criteria_id`, `institution_staff_appraisal_id`, `answer`, `created_user_id`, `created`)
                        SELECT
                            form_criterias.`appraisal_form_id`,
                            form_criterias.`appraisal_criteria_id`,
                            z_results.`staff_appraisal_id`,
                            z_results.`rating`,
                            1,
                            NOW()
                        FROM `z_4340_staff_appraisals_competencies` z_results
                        INNER JOIN `z_4340_staff_appraisals` z_staff_appr ON z_results.`staff_appraisal_id` = z_staff_appr.`id`
                        INNER JOIN `appraisal_forms_criterias` form_criterias
                            ON z_staff_appr.`competency_set_id` = form_criterias.`appraisal_form_id`
                            AND z_results.`competency_id` = form_criterias.`appraisal_criteria_id`
                    ");

        // security_functions
        $securityFunctionsData = [
            [
                'id' => '5085',
                'name' => 'Criterias',
                'controller' => 'StaffAppraisals',
                'module' => 'Administration',
                'category' => 'Staff Appraisals',
                'parent_id' => 5000,
                '_view' => 'Criterias.index|Criterias.view',
                '_edit' => 'Criterias.edit',
                '_add' => 'Criterias.add',
                '_delete' => 'Criterias.remove',
                'order' => '309',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '5086',
                'name' => 'Forms',
                'controller' => 'StaffAppraisals',
                'module' => 'Administration',
                'category' => 'Staff Appraisals',
                'parent_id' => 5000,
                '_view' => 'Forms.index|Forms.view',
                '_edit' => 'Forms.edit',
                '_add' => 'Forms.add',
                '_delete' => 'Forms.remove',
                'order' => '310',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '5087',
                'name' => 'Types',
                'controller' => 'StaffAppraisals',
                'module' => 'Administration',
                'category' => 'Staff Appraisals',
                'parent_id' => 5000,
                '_view' => 'Types.index|Types.view',
                '_edit' => 'Types.edit',
                '_add' => 'Types.add',
                '_delete' => 'Types.remove',
                'order' => '311',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '5088',
                'name' => 'Periods',
                'controller' => 'StaffAppraisals',
                'module' => 'Administration',
                'category' => 'Staff Appraisals',
                'parent_id' => 5000,
                '_view' => 'Periods.index|Periods.view',
                '_edit' => 'Periods.edit',
                '_add' => 'Periods.add',
                '_delete' => 'Periods.remove',
                'order' => '312',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
        ];
        $this->insert('security_functions', $securityFunctionsData);

        $institutionAppraisalsSql = "UPDATE security_functions
                                SET `_view` = 'InstitutionStaffAppraisals.index|InstitutionStaffAppraisals.view|InstitutionStaffAppraisals.download',
                                `_edit` = 'InstitutionStaffAppraisals.edit',
                                `_add` = 'InstitutionStaffAppraisals.add',
                                `_delete` = 'InstitutionStaffAppraisals.remove',
                                `_execute` = null
                                WHERE `id` = 3037";
        $directoryAppraisalsSql = "UPDATE security_functions
                                SET `_view` = 'StaffAppraisals.index|StaffAppraisals.view|StaffAppraisals.download'
                                WHERE `id` = 7049";
        $this->execute($institutionAppraisalsSql);
        $this->execute($directoryAppraisalsSql);

         // locale_contents
        $localeContent = [
            [
                'en' => 'Code must be unique',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Value cannot be more than two decimal places',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Value must be within 0 to 100',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Value must be within 0 to 9.99',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Max value must be greater than min value',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Max value must be greater than step value',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Please enter a unique name in the selected academic period',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Date Disabled should not be earlier than Date Enabled',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'To Date should not be earlier than From Date',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Text',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Decimal',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Textarea',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Dropdown',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Checkbox',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Table',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Student List',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Coordinates',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Repeater',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Slider',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];
        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->dropTable('appraisal_criterias');
        $this->dropTable('appraisal_forms');
        $this->dropTable('appraisal_forms_criterias');
        $this->dropTable('appraisal_periods');
        $this->dropTable('appraisal_periods_types');
        $this->dropTable('appraisal_slider_answers');
        $this->dropTable('appraisal_text_answers');
        $this->dropTable('appraisal_types');
        $this->dropTable('institution_staff_appraisals');
        $this->dropTable('field_types');
        $this->dropTable('appraisal_sliders');

        $this->table('z_4340_competency_sets')->rename('competency_sets');
        $this->table('z_4340_competencies')->rename('competencies');
        $this->table('z_4340_competency_sets_competencies')->rename('competency_sets_competencies');
        $this->table('z_4340_staff_appraisals')->rename('staff_appraisals');
        $this->table('z_4340_staff_appraisals_competencies')->rename('staff_appraisals_competencies');
        $this->table('z_4340_staff_appraisal_types')->rename('staff_appraisal_types');

        // security_functions
        $this->execute("DELETE FROM `security_functions` WHERE `id` IN (5085,5086,5087,5088)");
        $institutionAppraisalsSql = "UPDATE security_functions
                                SET `_view` = 'StaffAppraisals.index|StaffAppraisals.view',
                                `_edit` = 'StaffAppraisals.edit',
                                `_add` = 'StaffAppraisals.add',
                                `_delete` = 'StaffAppraisals.remove',
                                `_execute` = 'StaffAppraisals.download'
                                WHERE `id` = 3037";
        $directoryAppraisalsSql = "UPDATE security_functions
                                SET `_view` = 'StaffAppraisals.index|StaffAppraisals.view'
                                WHERE `id` = 7049";
        $this->execute($institutionAppraisalsSql);
        $this->execute($directoryAppraisalsSql);
    }
}
