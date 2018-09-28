<?php

use Phinx\Migration\AbstractMigration;

class POCOR4816 extends AbstractMigration
{
    public function up()
    {
        // config_items
        $this->execute('CREATE TABLE `z_4816_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_4816_config_items` SELECT * FROM `config_items`');

        $this->execute('DELETE FROM `config_items` WHERE `id` IN (75, 99)');
        // config_items - END
        
        // locale_content
        $this->execute('CREATE TABLE `z_4816_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_4816_locale_contents` SELECT * FROM `locale_contents`');

        $today = date('Y-m-d H:i:s');
        $localeData = [
            [
                'en' => 'Designed for use by anyone including those with special needs/disabilities.',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'This course is Special Educational Needs(SENs) compliant.',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Referrals',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Services',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Devices',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Plans',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Accessible',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Not Accessible',
                'created_user_id' => 1,
                'created' => $today
            ]
        ];
        $this->insert('locale_contents', $localeData);
        // locale_content - END
        
        // security_functions
        /*
            TODO: add permissions for newly added features
         */
        $this->execute('CREATE TABLE `z_4816_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4816_security_functions` SELECT * FROM `security_functions`');

        /*
            Current Special Needs permission
            Institution > Students (id: 2006, order: 120)
            Institution > Staff    (id: 3006, order: 156)
            Directory              (id: 7007, order: 289)
         */
        
        $this->execute('DELETE FROM `security_functions` WHERE id = 2006');
        $this->execute('UPDATE `security_functions` set `order` = `order` - 1 WHERE `order` >= 120');

        $this->execute('DELETE FROM `security_functions` WHERE id = 3006');
        $this->execute('UPDATE `security_functions` set `order` = `order` - 1 WHERE `order` >= 156');

        $this->execute('DELETE FROM `security_functions` WHERE id = 7007');
        $this->execute('UPDATE `security_functions` set `order` = `order` - 1 WHERE `order` >= 289');

        /*
            Updated Special Needs permissions
            Institution > Students
                - Referrals   (id: 2041, order: 145)
                - Assessments (id: 2042, order: 146)
                - Services    (id: 2043, order: 147)
                - Devices     (id: 2044, order: 148)
                - Plans       (id: 2045, order: 149)

            Institution > Staff 
                - Referrals   (id: 3050, order: 188) + 5
                - Assessments (id: 3051, order: 189) + 5
                - Services    (id: 3052, order: 190) + 5
                - Devices     (id: 3053, order: 191) + 5
                - Plans       (id: 3054, order: 192) + 5

            Directories
                - Referrals   (id: 7063, order: 332) + 10
                - Assessments (id: 7064, order: 333) + 10
                - Services    (id: 7065, order: 334) + 10
                - Devices     (id: 7066, order: 335) + 10
                - Plans       (id: 7067, order: 336) + 10
         */
        
        $this->execute('UPDATE `security_functions` set `order` = `order` + 5 WHERE `order` >= 145');
        $this->execute('UPDATE `security_functions` set `order` = `order` + 5 WHERE `order` >= 193');
        $this->execute('UPDATE `security_functions` set `order` = `order` + 5 WHERE `order` >= 342');

        $securityFunctionData = [
            [
                'id' => 2041,
                'name' => 'Referrals',
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Special Needs',
                'parent_id' => 2000,
                '_view' => 'SpecialNeedsReferrals.index|SpecialNeedsReferrals.view',
                '_edit' => 'SpecialNeedsReferrals.edit',
                '_add' => 'SpecialNeedsReferrals.add',
                '_delete' => 'SpecialNeedsReferrals.remove',
                '_execute' => null,
                'order' => 145,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 2042,
                'name' => 'Assessments',
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Special Needs',
                'parent_id' => 2000,
                '_view' => 'SpecialNeedsAssessments.index|SpecialNeedsAssessments.view',
                '_edit' => 'SpecialNeedsAssessments.edit',
                '_add' => 'SpecialNeedsAssessments.add',
                '_delete' => 'SpecialNeedsAssessments.remove',
                '_execute' => null,
                'order' => 146,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 2043,
                'name' => 'Services',
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Special Needs',
                'parent_id' => 2000,
                '_view' => 'SpecialNeedsServices.index|SpecialNeedsServices.view',
                '_edit' => 'SpecialNeedsServices.edit',
                '_add' => 'SpecialNeedsServices.add',
                '_delete' => 'SpecialNeedsServices.remove',
                '_execute' => null,
                'order' => 147,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 2044,
                'name' => 'Devices',
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Special Needs',
                'parent_id' => 2000,
                '_view' => 'SpecialNeedsDevices.index|SpecialNeedsDevices.view',
                '_edit' => 'SpecialNeedsDevices.edit',
                '_add' => 'SpecialNeedsDevices.add',
                '_delete' => 'SpecialNeedsDevices.remove',
                '_execute' => null,
                'order' => 148,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 2045,
                'name' => 'Plans',
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Special Needs',
                'parent_id' => 2000,
                '_view' => 'SpecialNeedsPlans.index|SpecialNeedsPlans.view',
                '_edit' => 'SpecialNeedsPlans.edit',
                '_add' => 'SpecialNeedsPlans.add',
                '_delete' => 'SpecialNeedsPlans.remove',
                '_execute' => null,
                'order' => 149,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 3050,
                'name' => 'Referrals',
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - Special Needs',
                'parent_id' => 3000,
                '_view' => 'SpecialNeedsReferrals.index|SpecialNeedsReferrals.view',
                '_edit' => 'SpecialNeedsReferrals.edit',
                '_add' => 'SpecialNeedsReferrals.add',
                '_delete' => 'SpecialNeedsReferrals.remove',
                '_execute' => null,
                'order' => 193,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 3051,
                'name' => 'Assessments',
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - Special Needs',
                'parent_id' => 3000,
                '_view' => 'SpecialNeedsAssessments.index|SpecialNeedsAssessments.view',
                '_edit' => 'SpecialNeedsAssessments.edit',
                '_add' => 'SpecialNeedsAssessments.add',
                '_delete' => 'SpecialNeedsAssessments.remove',
                '_execute' => null,
                'order' => 194,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 3052,
                'name' => 'Services',
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - Special Needs',
                'parent_id' => 3000,
                '_view' => 'SpecialNeedsServices.index|SpecialNeedsServices.view',
                '_edit' => 'SpecialNeedsServices.edit',
                '_add' => 'SpecialNeedsServices.add',
                '_delete' => 'SpecialNeedsServices.remove',
                '_execute' => null,
                'order' => 195,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 3053,
                'name' => 'Devices',
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - Special Needs',
                'parent_id' => 3000,
                '_view' => 'SpecialNeedsDevices.index|SpecialNeedsDevices.view',
                '_edit' => 'SpecialNeedsDevices.edit',
                '_add' => 'SpecialNeedsDevices.add',
                '_delete' => 'SpecialNeedsDevices.remove',
                '_execute' => null,
                'order' => 196,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 3054,
                'name' => 'Plans',
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - Special Needs',
                'parent_id' => 3000,
                '_view' => 'SpecialNeedsPlans.index|SpecialNeedsPlans.view',
                '_edit' => 'SpecialNeedsPlans.edit',
                '_add' => 'SpecialNeedsPlans.add',
                '_delete' => 'SpecialNeedsPlans.remove',
                '_execute' => null,
                'order' => 197,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 7063,
                'name' => 'Referrals',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Special Needs',
                'parent_id' => 7000,
                '_view' => 'SpecialNeedsReferrals.index|SpecialNeedsReferrals.view',
                '_edit' => 'SpecialNeedsReferrals.edit',
                '_add' => 'SpecialNeedsReferrals.add',
                '_delete' => 'SpecialNeedsReferrals.remove',
                '_execute' => null,
                'order' => 342,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 7064,
                'name' => 'Assessments',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Special Needs',
                'parent_id' => 7000,
                '_view' => 'SpecialNeedsAssessments.index|SpecialNeedsAssessments.view',
                '_edit' => 'SpecialNeedsAssessments.edit',
                '_add' => 'SpecialNeedsAssessments.add',
                '_delete' => 'SpecialNeedsAssessments.remove',
                '_execute' => null,
                'order' => 343,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 7065,
                'name' => 'Services',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Special Needs',
                'parent_id' => 7000,
                '_view' => 'SpecialNeedsServices.index|SpecialNeedsServices.view',
                '_edit' => 'SpecialNeedsServices.edit',
                '_add' => 'SpecialNeedsServices.add',
                '_delete' => 'SpecialNeedsServices.remove',
                '_execute' => null,
                'order' => 344,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 7066,
                'name' => 'Devices',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Special Needs',
                'parent_id' => 7000,
                '_view' => 'SpecialNeedsDevices.index|SpecialNeedsDevices.view',
                '_edit' => 'SpecialNeedsDevices.edit',
                '_add' => 'SpecialNeedsDevices.add',
                '_delete' => 'SpecialNeedsDevices.remove',
                '_execute' => null,
                'order' => 345,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 7067,
                'name' => 'Plans',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Special Needs',
                'parent_id' => 7000,
                '_view' => 'SpecialNeedsPlans.index|SpecialNeedsPlans.view',
                '_edit' => 'SpecialNeedsPlans.edit',
                '_add' => 'SpecialNeedsPlans.add',
                '_delete' => 'SpecialNeedsPlans.remove',
                '_execute' => null,
                'order' => 346,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ]
        ];

        $this->insert('security_functions', $securityFunctionData);
        // security_functions - END

        // institution_lands
        $this->execute('RENAME TABLE `institution_lands` TO `z_4816_institution_lands`');

        $InstitutionLands = $this->table('institution_lands', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all land information of all institutions'
        ]);
        $InstitutionLands
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('start_date', 'date', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('end_date', 'date', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('year_acquired', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('year_disposed', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('area', 'float', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('accessibility', 'integer', [
                'limit' => 1,
                'default' => null,
                'null' => false
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('land_type_id', 'integer', [
                'comment' => 'links to land_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('land_status_id', 'integer', [
                'comment' => 'links to infrastructure_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_ownership_id', 'integer', [
                'comment' => 'links to infrastructure_ownerships.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_condition_id', 'integer', [
                'comment' => 'links to infrastructure_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('previous_institution_land_id', 'integer', [
                'comment' => 'links to institution_lands.id',
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('code')
            ->addIndex('accessibility')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('land_type_id')
            ->addIndex('land_status_id')
            ->addIndex('infrastructure_ownership_id')
            ->addIndex('infrastructure_condition_id')
            ->addIndex('previous_institution_land_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->execute('INSERT INTO `institution_lands` (`id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `year_acquired`, `year_disposed`, `area`, `accessibility`, `comment`, `institution_id`, `academic_period_id`, `land_type_id`, `land_status_id`, `infrastructure_ownership_id`, `infrastructure_condition_id`, `previous_institution_land_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `year_acquired`, `year_disposed`, `area`, 0, `comment`, `institution_id`, `academic_period_id`, `land_type_id`, `land_status_id`, `infrastructure_ownership_id`, `infrastructure_condition_id`, `previous_institution_land_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4816_institution_lands`');
        // institution_lands - END
        
        // institution_buildings
        $this->execute('RENAME TABLE `institution_buildings` TO `z_4816_institution_buildings`');

        $InstitutionBuildings = $this->table('institution_buildings', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all building information of all institutions'
        ]);
        $InstitutionBuildings
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('start_date', 'date', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('end_date', 'date', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('year_acquired', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('year_disposed', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('area', 'float', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('accessibility', 'integer', [
                'limit' => 1,
                'default' => null,
                'null' => false
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('institution_land_id', 'integer', [
                'comment' => 'links to institution_lands.id',
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('building_type_id', 'integer', [
                'comment' => 'links to building_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('building_status_id', 'integer', [
                'comment' => 'links to infrastructure_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_ownership_id', 'integer', [
                'comment' => 'links to infrastructure_ownerships.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_condition_id', 'integer', [
                'comment' => 'links to infrastructure_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('previous_institution_building_id', 'integer', [
                'comment' => 'links to institution_buildings.id',
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('code')
            ->addIndex('accessibility')
            ->addIndex('institution_land_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('building_type_id')
            ->addIndex('building_status_id')
            ->addIndex('infrastructure_ownership_id')
            ->addIndex('infrastructure_condition_id')
            ->addIndex('previous_institution_building_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->execute('INSERT INTO `institution_buildings` (`id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `year_acquired`, `year_disposed`, `area`, `accessibility`, `comment`, `institution_land_id`, `institution_id`, `academic_period_id`, `building_type_id`, `building_status_id`, `infrastructure_ownership_id`, `infrastructure_condition_id`, `previous_institution_building_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `year_acquired`, `year_disposed`, `area`, 0, `comment`, `institution_land_id`, `institution_id`, `academic_period_id`, `building_type_id`, `building_status_id`, `infrastructure_ownership_id`, `infrastructure_condition_id`, `previous_institution_building_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4816_institution_buildings`');
        // institution_buildings - END

        // institution_floors
        $this->execute('RENAME TABLE `institution_floors` TO `z_4816_institution_floors`');
        
        $InstitutionFloors = $this->table('institution_floors', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all floor information of all institutions'
        ]);
        $InstitutionFloors
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('start_date', 'date', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('end_date', 'date', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true
            ])
            ->addColumn('area', 'float', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('accessibility', 'integer', [
                'limit' => 1,
                'default' => null,
                'null' => false
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('institution_building_id', 'integer', [
                'comment' => 'links to institution_buildings.id',
                'default' => null,
                'limit' => 4,
                'null' => false
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('floor_type_id', 'integer', [
                'comment' => 'links to floor_types.id',
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('floor_status_id', 'integer', [
                'comment' => 'links to infrastructure_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_condition_id', 'integer', [
                'comment' => 'links to infrastructure_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('previous_institution_floor_id', 'integer', [
                'comment' => 'links to institution_floors.id',
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('code')
            ->addIndex('accessibility')
            ->addIndex('institution_building_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('floor_type_id')
            ->addIndex('floor_status_id')
            ->addIndex('infrastructure_condition_id')
            ->addIndex('previous_institution_floor_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->execute('INSERT INTO `institution_floors` (`id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `area`, `accessibility`, `comment`, `institution_building_id`, `institution_id`, `academic_period_id`, `floor_type_id`, `floor_status_id`, `infrastructure_condition_id`, `previous_institution_floor_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `area`, 0, `comment`, `institution_building_id`, `institution_id`, `academic_period_id`, `floor_type_id`, `floor_status_id`, `infrastructure_condition_id`, `previous_institution_floor_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4816_institution_floors`');
        // institution_floors - END

        // institution_rooms
        $this->execute('RENAME TABLE `institution_rooms` TO `z_4816_institution_rooms`');

        $InstitutionRooms = $this->table('institution_rooms', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all room information of all institutions'
        ]);
        $InstitutionRooms
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('start_date', 'date', [
                'null' => false,
                'default' => null
            ])
            ->addColumn('start_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false
            ])
            ->addColumn('end_date', 'date', [
                'null' => false,
                'default' => null
            ])
            ->addColumn('end_year', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => false
            ])
            ->addColumn('accessibility', 'integer', [
                'limit' => 1,
                'default' => null,
                'null' => false
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('room_type_id', 'integer', [
                'comment' => 'links to room_types.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('room_status_id', 'integer', [
                'comment' => 'links to infrastructure_statuses.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('institution_floor_id', 'integer', [
                'comment' => 'links to institution_floors.id',
                'default' => null,
                'limit' => 4,
                'null' => false
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('infrastructure_condition_id', 'integer', [
                'comment' => 'links to infrastructure_conditions.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('previous_institution_room_id', 'integer', [
                'comment' => 'links to institution_rooms.id',
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('code')
            ->addIndex('accessibility')
            ->addIndex('room_type_id')
            ->addIndex('room_status_id')
            ->addIndex('institution_floor_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('infrastructure_condition_id')
            ->addIndex('previous_institution_room_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->execute('INSERT INTO `institution_rooms` (`id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `accessibility`, `room_type_id`, `room_status_id`, `institution_floor_id`, `institution_id`, `academic_period_id`, `infrastructure_condition_id`, `previous_institution_room_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, 0, `room_type_id`, `room_status_id`, `institution_floor_id`, `institution_id`, `academic_period_id`, `infrastructure_condition_id`, `previous_institution_room_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4816_institution_rooms`');

        // institution_rooms - END

        // training_courses
        $this->execute('RENAME TABLE `training_courses` TO `z_4816_training_courses`');

        $TrainingCourses = $this->table('training_courses', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all training courses'
        ]);
        $TrainingCourses
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 60,
                'default' => null
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250,
                'default' => null
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('objective', 'text', [
                'null' => true,
                'default' => null
            ])
            ->addColumn('credit_hours', 'integer', [
                'null' => false,
                'default' => null,
                'limit' => 3
            ])
            ->addColumn('duration', 'integer', [
                'null' => false,
                'default' => null,
                'limit' => 3
            ])
            ->addColumn('number_of_months', 'integer', [
                'null' => false, 
                'default' => null,
                'limit' => 3
            ])
            ->addColumn('special_education_needs', 'integer', [
                'null' => false,
                'default' => null,
                'limit' => 1
            ])
            ->addColumn('file_name', 'string', [
                'null' => true,
                'limit' => 250,
                'default' => null
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => true
            ])
            ->addColumn('training_field_of_study_id', 'integer', [
                'comment' => 'links to training_field_of_studies.id',
                'limit' => 11,
                'null' => false,
                'default' => null
            ])
            ->addColumn('training_course_type_id', 'integer', [
                'comment' => 'links to training_course_types.id',
                'limit' => 11,
                'null' => false,
                'default' => null
            ])
            ->addColumn('training_mode_of_delivery_id', 'integer', [
                'comment' => 'links to training_mode_of_deliveries.id',
                'limit' => 11,
                'null' => false,
                'default' => null
            ])
            ->addColumn('training_requirement_id', 'integer', [
                'comment' => 'links to training_requirements.id',
                'limit' => 11,
                'null' => false,
                'default' => null
            ])
            ->addColumn('training_level_id', 'integer', [
                'comment' => 'links to training_levels.id',
                'limit' => 11,
                'null' => false,
                'default' => null
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'limit' => 11,
                'null' => false,
                'default' => null
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('special_education_needs')
            ->addIndex('training_field_of_study_id')
            ->addIndex('training_course_type_id')
            ->addIndex('training_mode_of_delivery_id')
            ->addIndex('training_requirement_id')
            ->addIndex('training_level_id')
            ->addIndex('assignee_id')
            ->addIndex('status_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->execute('INSERT INTO `training_courses` (`id`, `code`, `name`, `description`, `objective`, `credit_hours`, `duration`, `number_of_months`, `special_education_needs`, `file_name`, `file_content`, `training_field_of_study_id`, `training_course_type_id`, `training_mode_of_delivery_id`, `training_requirement_id`, `training_level_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `id`, `code`, `name`, `description`, `objective`, `credit_hours`, `duration`, `number_of_months`, 0, `file_name`, `file_content`, `training_field_of_study_id`, `training_course_type_id`, `training_mode_of_delivery_id`, `training_requirement_id`, `training_level_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4816_training_courses`');
        // training_courses - END
        
        // institution_counsellings
        $this->execute('RENAME TABLE `institution_counsellings` TO `z_4816_institution_counsellings`');

        $InstitutionCounsellings = $this->table('institution_counsellings', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains counsellings for the students'
        ]);
        $InstitutionCounsellings
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('guidance_utilized', 'text', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('intervention', 'text', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('file_name', 'string', [
                'null' => true,
                'limit' => 250,
                'default' => null
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => true
            ])
            ->addColumn('counselor_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('guidance_type_id', 'integer', [
                'comment' => 'links to guidance_types.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('counselor_id')
            ->addIndex('student_id')
            ->addIndex('guidance_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // guidance_utilized - NULLABLE field?
        $this->execute('INSERT INTO `institution_counsellings` (`id`, `date`, `guidance_utilized`, `description`, `intervention`, `comment`, `file_name`, `file_content`, `counselor_id`, `student_id`, `guidance_type_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `id`, `date`, " ", `description`, `intervention`, null, `file_name`, `file_content`, `counselor_id`, `student_id`, `guidance_type_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4816_institution_counsellings`');

        // institution_counsellings - END
        
        // NEW TABLE
        // special_needs_referrer_types (field_options)
        $SpecialNeedsReferrerTypes = $this->table('special_needs_referrer_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of special needs referrer types used in user_special_needs_referrals'
        ]);

        $SpecialNeedsReferrerTypes
            ->addColumn('name', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => null
            ])
            ->addColumn('order', 'integer', [
                'limit' => 3,
                'null' => false,
                'default' => null
            ])
            ->addColumn('visible', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 1
            ])
            ->addColumn('editable', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 1
            ])
            ->addColumn('default', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('international_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('national_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // special_needs_referrer_types - END
    
        // special_needs_service_types (field_options)
        $SpecialNeedsServiceTypes = $this->table('special_needs_service_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of special needs service types used in user_special_needs_services'
        ]);
        $SpecialNeedsServiceTypes
            ->addColumn('name', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => null
            ])
            ->addColumn('order', 'integer', [
                'limit' => 3,
                'null' => false,
                'default' => null
            ])
            ->addColumn('visible', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 1
            ])
            ->addColumn('editable', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 1
            ])
            ->addColumn('default', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('international_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('national_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // special_needs_service_types - END

        // special_needs_device_types (field_options)
        $SpecialNeedsDeviceTypes = $this->table('special_needs_device_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of special needs devices types used in user_special_needs_devices'
        ]);
        $SpecialNeedsDeviceTypes
            ->addColumn('name', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => null
            ])
            ->addColumn('order', 'integer', [
                'limit' => 3,
                'null' => false,
                'default' => null
            ])
            ->addColumn('visible', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 1
            ])
            ->addColumn('editable', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 1
            ])
            ->addColumn('default', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('international_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('national_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // special_needs_device_types - END
        
        // special_needs_visit_types (system_defined)
        $SpecialNeedsVisitTypes = $this->table('special_needs_visit_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => ''
        ]);
        $SpecialNeedsVisitTypes
            ->addColumn('code', 'string', [
                'limit' => 100,
                'null' => false,
                'default' => null
            ])
            ->addColumn('name', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => null
            ])
            ->save();

        $specialNeedsVisitData = [
            [
                'id' => 1,
                'code' => 'INSTITUTION_VISIT',
                'name' => 'Institution Visit'
            ],
            [
                'id' => 2,
                'code' => 'HOME_VISIT',
                'name' => 'Home Visit'
            ]
        ];

        $this->insert('special_needs_visit_types', $specialNeedsVisitData);
        // special_needs_visit_types - END
        
        // special_needs_purpose_types (field options)
        $SpecialNeedsPurposeTypes = $this->table('special_needs_purpose_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => ''
        ]);
        $SpecialNeedsPurposeTypes
            ->addColumn('name', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => null
            ])
            ->addColumn('order', 'integer', [
                'limit' => 3,
                'null' => false,
                'default' => null
            ])
            ->addColumn('visible', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 1
            ])
            ->addColumn('editable', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 1
            ])
            ->addColumn('default', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('international_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('national_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // special_needs_purpose_types - END (field options)

        
        // user_special_needs_referrals
        $UserSpecialNeedsReferrals = $this->table('user_special_needs_referrals', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all special needs referral for all users'
        ]);
        $UserSpecialNeedsReferrals
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('file_name', 'string', [
                'null' => true,
                'limit' => 250,
                'default' => null
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('referrer_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('special_needs_referrer_type_id', 'integer', [
                'comment' => 'links to special_needs_referrer_types.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('reason_type_id', 'integer', [
                'comment' => 'links to special_need_types.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('academic_period_id')
            ->addIndex('security_user_id')
            ->addIndex('referrer_id')
            ->addIndex('special_needs_referrer_type_id')
            ->addIndex('reason_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // user_special_needs_referral - END
        
        // user_special_needs_assessments
        $UserSpecialNeedsAssessments = $this->table('user_special_needs_assessments', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all special needs assessments for all users'
        ]);
        $UserSpecialNeedsAssessments
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('file_name', 'string', [
                'null' => true,
                'limit' => 250,
                'default' => null
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('special_need_type_id', 'integer', [
                'comment' => 'links to special_need_types.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('special_need_difficulty_id', 'integer', [
                'comment' => 'links to special_need_difficulties.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('special_need_type_id')
            ->addIndex('special_need_difficulty_id')
            ->addIndex('security_user_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        
        $this->execute('RENAME TABLE `user_special_needs` TO `z_4816_user_special_needs`');
        $this->execute('INSERT INTO `user_special_needs_assessments` (`id`, `date`, `comment`, `security_user_id`, `special_need_type_id`, `special_need_difficulty_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `id`, `special_need_date`, `comment`, `security_user_id`, `special_need_type_id`, `special_need_difficulty_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4816_user_special_needs`');

        // user_special_needs_assessments - END
        
        // user_special_needs_services
        $UserSpecialNeedsServices = $this->table('user_special_needs_services', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all special needs services for all users'
        ]);
        $UserSpecialNeedsServices
            ->addColumn('organization', 'string', [
                'limit' => 100,
                'default' => null,
                'null' => true
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('file_name', 'string', [
                'null' => true,
                'limit' => 250,
                'default' => null
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => true
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('special_needs_service_type_id', 'integer', [
                'comment' => 'links to special_needs_service_types.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('academic_period_id')
            ->addIndex('security_user_id')
            ->addIndex('special_needs_service_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // user_special_needs_services - END
      
        // user_special_needs_devices
        $UserSpecialNeedsDevices = $this->table('user_special_needs_devices', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all special needs devices for all users'
        ]);

        $UserSpecialNeedsDevices
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('special_needs_device_type_id', 'integer', [
                'comment' => 'links to special_needs_device_types.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('security_user_id')
            ->addIndex('special_needs_device_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // user_special_needs_devices - END
        
        // user_special_needs_plans
        $UserSpecialNeedsPlans = $this->table('user_special_needs_plans', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all special needs plans for all users'
        ]);

        $UserSpecialNeedsPlans
            ->addColumn('plan_name', 'string', [
                'limit' => 250,
                'default' => null,
                'null' => false
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('file_name', 'string', [
                'null' => true,
                'limit' => 250,
                'default' => null
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => true
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('security_user_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // user_special_needs_plans - END
    }

    public function down()
    {
        // config_items
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `z_4816_config_items` TO `config_items`');

        // locale_content
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_4816_locale_contents` TO `locale_contents`');

        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_4816_security_functions` TO `security_functions`');

        // institution_lands
        $this->execute('DROP TABLE IF EXISTS `institution_lands`');
        $this->execute('RENAME TABLE `z_4816_institution_lands` TO `institution_lands`');

        // institution_buildings
        $this->execute('DROP TABLE IF EXISTS `institution_buildings`');
        $this->execute('RENAME TABLE `z_4816_institution_buildings` TO `institution_buildings`');

        // institution_floors
        $this->execute('DROP TABLE IF EXISTS `institution_floors`');
        $this->execute('RENAME TABLE `z_4816_institution_floors` TO `institution_floors`');

        // institution_rooms
        $this->execute('DROP TABLE IF EXISTS `institution_rooms`');
        $this->execute('RENAME TABLE `z_4816_institution_rooms` TO `institution_rooms`');

        // training_courses
        $this->execute('DROP TABLE IF EXISTS `training_courses`');
        $this->execute('RENAME TABLE `z_4816_training_courses` TO `training_courses`');

        // institution_counsellings
        $this->execute('DROP TABLE IF EXISTS `institution_counsellings`');
        $this->execute('RENAME TABLE `z_4816_institution_counsellings` TO `institution_counsellings`');

        // NEW TABLE
        // special_needs_referral_types
        $this->execute('DROP TABLE IF EXISTS `special_needs_referrer_types`');

        // special_needs_service_types
        $this->execute('DROP TABLE IF EXISTS `special_needs_service_types`');

        // special_needs_device_types
        $this->execute('DROP TABLE IF EXISTS `special_needs_device_types`');

        // special_needs_visit_types
        $this->execute('DROP TABLE IF EXISTS `special_needs_visit_types`');

        // special_needs_purpose_types
        $this->execute('DROP TABLE IF EXISTS `special_needs_purpose_types`');

        // user_special_needs_referrals
        $this->execute('DROP TABLE IF EXISTS `user_special_needs_referrals`');

        // user_special_needs_assessments 
        $this->execute('DROP TABLE IF EXISTS `user_special_needs_assessments`');
        $this->execute('RENAME TABLE `z_4816_user_special_needs` TO `user_special_needs`');

        // user_special_needs_services
        $this->execute('DROP TABLE IF EXISTS `user_special_needs_services`');

        // user_special_needs_devices
        $this->execute('DROP TABLE IF EXISTS `user_special_needs_devices`');

        // user_special_needs_plans
        $this->execute('DROP TABLE IF EXISTS `user_special_needs_plans`');
    }
}
