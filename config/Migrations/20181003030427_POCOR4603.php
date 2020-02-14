<?php

use Cake\Utility\Security;
use Phinx\Migration\AbstractMigration;

class POCOR4603 extends AbstractMigration
{
    public function up()
    {
        // institution_classes_secondary_staff
        $InstitutionClassesSecondaryStaff = $this->table('institution_classes_secondary_staff', [
            'id' => false,
            'primary_key' => ['institution_class_id', 'secondary_staff_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all staff for all institution classes'
        ]);

        $InstitutionClassesSecondaryStaff
            ->addColumn('id', 'char', [
                'limit' => 64,
                'default' => null,
                'null' => false
            ])
            ->addColumn('institution_class_id', 'integer', [
                'comment' => 'links to institution_classes.id',
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('secondary_staff_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
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
            ->addIndex('id')
            ->addIndex('institution_class_id')
            ->addIndex('secondary_staff_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $institutionClassRecords = $this->fetchAll('
            SELECT * FROM `institution_classes`
        ');

        $classStaffRecords = [];
        foreach ($institutionClassRecords as $obj) {
            if ($obj['secondary_staff_id'] != 0) {
                $primaryKeys = [$obj['id'], $obj['secondary_staff_id']];
                $classStaffRecords[] = [
                    'id' => Security::hash(implode(',', $primaryKeys), 'sha256'),
                    'institution_class_id' => $obj['id'],
                    'secondary_staff_id' => $obj['secondary_staff_id'],
                    'modified_user_id' => $obj['modified_user_id'],
                    'modified' => $obj['modified'],
                    'created_user_id' => $obj['created_user_id'],
                    'created' => $obj['created'],
                ];
            }
        }

        if (!empty($classStaffRecords)) {
            $this->insert('institution_classes_secondary_staff', $classStaffRecords);
        }

        // institution_classes
        $this->execute('RENAME TABLE `institution_classes` TO `z_4603_institution_classes`');
        $InstitutionClasses = $this->table('institution_classes', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of classes by grade and academic period in every institution'
        ]);

        $InstitutionClasses
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
                'comment' => 'links to security_users.id',
                'default' => 0,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('institution_shift_id', 'integer', [
                'comment' => 'links to institution_shifts.id',
                'default' => null,
                'limit' => 11,
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
            ->addIndex('institution_shift_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->execute('INSERT INTO `institution_classes` (`id`, `name`, `class_number`, `capacity`, `total_male_students`, `total_female_students`, `staff_id`, `institution_shift_id`, `institution_id`, `academic_period_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT `id`, `name`, `class_number`, `capacity`, `total_male_students`, `total_female_students`, `staff_id`, `institution_shift_id`, `institution_id`, `academic_period_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_4603_institution_classes`');
    }

    public function down()
    {
        // institution_classes_secondary_staff
        $this->execute('DROP TABLE IF EXISTS `institution_classes_secondary_staff`');

        // institution_classes
        $this->execute('DROP TABLE IF EXISTS `institution_classes`');
        $this->execute('RENAME TABLE `z_4603_institution_classes` TO `institution_classes`');
    }
}
