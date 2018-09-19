<?php

use Phinx\Migration\AbstractMigration;

class POCOR4816 extends AbstractMigration
{
    public function up()
    {
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
                'en' => 'Is this course for Special Education Needs?',
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

        // institution_lands
        $this->execute('CREATE TABLE `z_4816_institution_lands` LIKE `institution_lands`');
        $this->execute('INSERT INTO `z_4816_institution_lands` SELECT * FROM `institution_lands`');
        $this->execute('DROP TABLE IF EXISTS `institution_lands`');

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
        $this->execute('CREATE TABLE `z_4816_institution_buildings` LIKE `institution_buildings`');
        $this->execute('INSERT INTO `z_4816_institution_buildings` SELECT * FROM `institution_buildings`');
        $this->execute('DROP TABLE IF EXISTS `institution_buildings`');


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
        $this->execute('CREATE TABLE `z_4816_institution_floors` LIKE `institution_floors`');
        $this->execute('INSERT INTO `z_4816_institution_floors` SELECT * FROM `institution_floors`');
        $this->execute('DROP TABLE IF EXISTS `institution_floors`');

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
        $this->execute('CREATE TABLE `z_4816_institution_rooms` LIKE `institution_rooms`');
        $this->execute('INSERT INTO `z_4816_institution_rooms` SELECT * FROM `institution_rooms`');
        $this->execute('DROP TABLE IF EXISTS `institution_rooms`');


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
        $this->execute('CREATE TABLE `z_4816_training_courses` LIKE `training_courses`');
        $this->execute('INSERT INTO `z_4816_training_courses` SELECT * FROM `training_courses`');
        $this->execute('DROP TABLE IF EXISTS `training_courses`');

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
    }

    public function down()
    {
        // locale_content
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_4816_locale_contents` TO `locale_contents`');

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
    }
}
