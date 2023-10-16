<?php
use Phinx\Migration\AbstractMigration;

class POCOR4641 extends AbstractMigration
{
    public function up()
    {
        $this->execute('RENAME TABLE `institution_classes` TO `z_4641_institution_classes`');

        // institution_classes table
        $table = $this->table('institution_classes', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of classes by grade and academic period in every institution'
        ]);

        $table
            ->addColumn('name', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null
            ])
            ->addColumn('class_number', 'integer', [
                'limit' => 11,
                'null' => true,
                'default' => null,
                'comment' => 'This column is being used to determine whether this class is a multi-grade or single-grade.'
            ])
            ->addColumn('capacity', 'integer', [
                'limit' => 5,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('total_male_students', 'integer', [
                'limit' => 5,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('total_female_students', 'integer', [
                'limit' => 5,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('staff_id', 'integer', [
                'limit' => 11,
                'null' => true,
                'default' => 0,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('secondary_staff_id', 'integer', [
                'limit' => 11,
                'null' => true,
                'default' => null
                
            ])
            ->addColumn('institution_shift_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_shifts.id'
            ])
            ->addColumn('institution_id', 'integer', [ 
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
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
            ->addIndex('total_male_students')
            ->addIndex('total_female_students')
            ->addIndex('staff_id')
            ->addIndex('secondary_staff_id')
            ->addIndex('institution_shift_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $row = $this->fetchRow("SELECT IFNULL(NULLIF(`value`, ''), `default_value`) AS `max_capacity` FROM `config_items` WHERE `code` = 'max_students_per_class'");
        $maxCapacity = $row['max_capacity'];

        $this->execute('INSERT INTO `institution_classes` (`id`, `name`, `class_number`, `capacity`, `total_male_students`, `total_female_students`, `staff_id`, `secondary_staff_id`, `institution_shift_id`, `institution_id`, `academic_period_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `id`, `name`, `class_number`, '.$maxCapacity.', `total_male_students`, `total_female_students`, `staff_id`, `secondary_staff_id`, `institution_shift_id`, `institution_id`, `academic_period_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4641_institution_classes`');
    }
    
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_classes`');
        $this->execute('RENAME TABLE `z_4641_institution_classes` TO `institution_classes`');
    }
}
