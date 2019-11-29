<?php

use Phinx\Migration\AbstractMigration;

class POCOR4356 extends AbstractMigration
{
    public function up()
    {
        // institution_schedule_terms
        $InstitutionScheduleTerms = $this->table('institution_schedule_terms', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the schedule terms for all institutions'
        ]);

        $InstitutionScheduleTerms
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 50
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('start_date', 'date', [
                'null' => false
            ])
            ->addColumn('end_date', 'date', [
                'null' => false
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
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
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // institution_schedule_terms - END

        // institution_schedule_intervals
        $InstitutionScheduleIntervals = $this->table('institution_schedule_intervals', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the schedule intervals for all institutions'
        ]);

        $InstitutionScheduleIntervals
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('institution_shift_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_shifts.id'
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
            ->addIndex('institution_id')
            ->addIndex('institution_shift_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // institution_schedule_intervals - END

        // institution_schedule_timeslots
        $InstitutionScheduleTimeslots = $this->table('institution_schedule_timeslots', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the timeslots for all the schedule intervals'
        ]);

        $InstitutionScheduleTimeslots
            ->addColumn('interval', 'integer', [
                'null' => false,
                'limit' => 3
            ])
            ->addColumn('order', 'integer', [
                'null' => false,
                'limit' => 3
            ])
            ->addColumn('institution_schedule_interval_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_schedule_intervals.id'
            ])
            ->addIndex('institution_schedule_interval_id')
            ->save();
        // institution_schedule_timeslots - END

        // institution_schedule_timetables
        $InstitutionScheduleTimetables = $this->table('institution_schedule_timetables', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the timetable for all institutions'
        ]);

        $InstitutionScheduleTimetables
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('status', 'integer', [
                'limit' => 1,
                'null' => true,
                'default' => 1,
                'comment' => '1 -> Draft, 2 -> Publish'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('institution_class_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_classes.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('institution_schedule_interval_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_schedule_intervals.id'
            ])
            ->addColumn('institution_schedule_term_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_schedule_terms.id'
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
            ->addIndex('institution_class_id')
            ->addIndex('institution_id')
            ->addIndex('institution_schedule_interval_id')
            ->addIndex('institution_schedule_term_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // institution_schedule_timetables - END

        // institution_schedule_lessons
        $InstitutionScheduleLessons = $this->table('institution_schedule_lessons', [
            'collation' => 'utf8mb4_unicode_ci',
            'id' => false,
            'primary_key' => ['day_of_week', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id'],
            'comment' => 'This table contains all the lessons for all the timetables for all institutions'
        ]);

        $InstitutionScheduleLessons
            ->addColumn('id', 'string', [
                'limit' => 64,
                'null' => false
            ])
            ->addColumn('day_of_week', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('institution_schedule_timeslot_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_schedule_timeslots.id'
            ])
            ->addColumn('institution_schedule_timetable_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_schedule_timetables.id'
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
            ->addIndex('day_of_week')
            ->addIndex('institution_schedule_timetable_id')
            ->addIndex('institution_schedule_timeslot_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // institution_schedule_lessons - END
        
        // institution_schedule_lesson_details
        $InstitutionScheduleLessonDetails = $this->table('institution_schedule_lesson_details', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the lesson details for all the lessons for all the timetable'
        ]);

        $InstitutionScheduleLessonDetails
            ->addColumn('lesson_type', 'integer', [
                'limit' => 1,
                'null' => false,
                'comment' => '1 -> Curriculum, 2 -> Non-Curriculum'
            ])
            ->addColumn('day_of_week', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('institution_schedule_timeslot_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_schedule_timeslots.id'
            ])
            ->addColumn('institution_schedule_timetable_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_schedule_timetables.id'
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
            ->addIndex('day_of_week')
            ->addIndex('institution_schedule_timeslot_id')
            ->addIndex('institution_schedule_timetable_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // institution_schedule_lesson_details - END

        // institution_schedule_curriculum_lessons
        $InstitutionScheduleCurriculumLessons = $this->table('institution_schedule_curriculum_lessons', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the curriculum lessons for all the lessons'
        ]);

        $InstitutionScheduleCurriculumLessons
            ->addColumn('code_only', 'integer', [
                'limit' => 1,
                'default' => 0,
                'null' => true
            ])
            ->addColumn('institution_schedule_lesson_detail_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_schedule_lessons.id'
            ])
            ->addColumn('institution_subject_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_subjects.id'
            ])

            ->addIndex('institution_schedule_lesson_detail_id')
            ->addIndex('institution_subject_id')
            ->save();
        // institution_schedule_curriculum_lessons - END

        // institution_schedule_non_curriculum_lessons
        $InstitutionScheduleNonCurriculumLessons = $this->table('institution_schedule_non_curriculum_lessons', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the non curriculum lessons for all the lessons'
        ]);

        $InstitutionScheduleNonCurriculumLessons
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('institution_schedule_lesson_detail_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_schedule_lessons.id'
            ])
            ->addIndex('institution_schedule_lesson_detail_id')
            ->save();
        // institution_schedule_non_curriculum_lessons - END
        
        // institution_schedule_lesson_rooms
        $InstitutionScheduleLessonRooms = $this->table('institution_schedule_lesson_rooms', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all rooms for all the lessons'
        ]);

        $InstitutionScheduleLessonRooms
            ->addColumn('institution_schedule_lesson_detail_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_schedule_lesson_details.id'
            ])
            ->addColumn('institution_room_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_rooms.id'
            ])
            ->addIndex('institution_schedule_lesson_detail_id')
            ->addIndex('institution_room_id')
            ->save();
        // institution_schedule_lesson_rooms - END 
		
		
		// institution_schedule_timetable_customizes
        $InstitutionScheduleTimetableCustomizes = $this->table('institution_schedule_timetable_customizes', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the customize color'
        ]);

        $InstitutionScheduleTimetableCustomizes
            ->addColumn('institution_schedule_timetable_id', 'integer', [
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('customize_key', 'string', [
                'null' => false,
                'limit' => 100
            ])
            ->addColumn('customize_value', 'string', [
                'null' => false,
                'limit' => 15
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
			->addIndex('institution_schedule_timetable_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->save();
        // institution_schedule_timetable_customizes - END
		
		
        
        // institution_schedule_timetable_styles (?)
        
        // security_functions
        // $this->execute('CREATE TABLE `z_4365_security_functions` LIKE `security_functions`');
        // $this->execute('INSERT INTO `z_4365_security_functions` SELECT * FROM `security_functions`');

        // $securityData = [];

        // $this->insert('security_functions', $securityData);
        // security_functions - END
    
        // locale_contents
        // $this->execute('CREATE TABLE `z_4365_locale_contents` LIKE `locale_contents`');
        // $this->execute('INSERT INTO `z_4365_locale_contents` SELECT * FROM `locale_contents`');
        // 
        /* 
            Terms
            Term
            Schedules
            Interval
            Intervals
            All Shifts
            Add Interval
            Draft
            Published
            -- Select Status --
            Value entered exceed the end time of the shift selected.
            Start Scheduling
            Non Curriculum Lesson
            Curriculum Lesson
        */
        // 
        // locale_contents - END
    }

    public function down()
    {
        // institution_schedule_terms
        $this->execute('DROP TABLE IF EXISTS `institution_schedule_terms`');

        // institution_schedule_intervals
        $this->execute('DROP TABLE IF EXISTS `institution_schedule_intervals`');

        // institution_schedule_timeslots
        $this->execute('DROP TABLE IF EXISTS `institution_schedule_timeslots`');

        // institution_schedule_timetables
        $this->execute('DROP TABLE IF EXISTS `institution_schedule_timetables`');

        // institution_schedule_lessons
        $this->execute('DROP TABLE IF EXISTS `institution_schedule_lessons`');

        // institution_schedule_lesson_details
        $this->execute('DROP TABLE IF EXISTS `institution_schedule_lesson_details`');

        // institution_schedule_curriculum_lessons
        $this->execute('DROP TABLE IF EXISTS `institution_schedule_curriculum_lessons`');

        // institution_schedule_non_curriculum_lessons
        $this->execute('DROP TABLE IF EXISTS `institution_schedule_non_curriculum_lessons`');
     
        // institution_schedule_lesson_rooms
        $this->execute('DROP TABLE IF EXISTS `institution_schedule_lesson_rooms`');
		
		// institution_schedule_timetable_customizes
        $this->execute('DROP TABLE IF EXISTS `institution_schedule_timetable_customizes`');

        // security_functions
        // $this->execute('DROP TABLE IF EXISTS `security_functions`');
        // $this->execute('RENAME TABLE `z_4365_security_functions` TO `security_functions`');
        
    }
}
