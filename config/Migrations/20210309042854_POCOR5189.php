<?php
use Migrations\AbstractMigration;

class POCOR5189 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // institution_associations
        $table = $this->table('institution_associations', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of guidance'
            ]);
           
            $table ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('total_male_students', 'integer', [
                'default' => 0,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('total_female_students', 'integer', [
                'default' => 0,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('institution_id', 'integer', [
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 50,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => NULL,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => NULL,
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
            ->addIndex('total_male_students')
            ->addIndex('total_female_students')
            ->save();
        // end institution_associations

        // institution_association_staff
        $table = $this->table('institution_association_staff', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains staff in the institution association'
            ]);
             $table->addColumn('security_user_id', 'integer', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('institution_association_id', 'integer', [
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
            ->addIndex('institution_association_id')
            ->save();
        // end institution_association_staff
        // institution_association_students
        $table = $this->table('institution_association_student', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains student in the institution association'
            ]);
           $table ->addColumn('security_user_id', 'integer', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('institution_association_id', 'integer', [
                'default' => null,
                'null' => false
            ])
             ->addColumn('education_grade_id', 'integer', [
                'default' => null,
                'null' => false
            ])
             ->addColumn('student_status_id', 'integer', [
                'default' => null,
                'null' => false
            ])
             ->addColumn('academic_period_id', 'integer', [
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
            ->addIndex('institution_association_id')
            ->addIndex('education_grade_id')
            ->addIndex('student_status_id')
            ->addIndex('academic_period_id')
            ->save();
        // end institution_association_students
    
        // Backup table for security_functions
        $this->execute('CREATE TABLE `zz_5189_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5189_security_functions` SELECT * FROM `security_functions`');

        // Insert Association in security_function table
        $this->execute("INSERT INTO `security_functions` ( `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES ('Associations', 'Institutions', 'Institutions', 'Students - Academic', '2000', 'StudentAssociations.index|StudentAssociations.view', NULL, NULL, NULL, NULL, '409', '1', NULL, '2', '2020-10-26 13:28:18', '1', '2019-10-31 11:05:55')");
        $this->execute("INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
            VALUES ('Associations', 'Staff', 'Institutions', 'Staff - Career', '3000', 'StaffAssociations.index|StaffAssociations.view', NULL, NULL, NULL, NULL, '212', '1', NULL, '2', '2020-10-27 13:28:45', '1', '2016-11-27 03:17:43')");
        $this->execute("INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
            VALUES ('Associations', 'Institutions', 'Institutions', 'Academic', '8', 'Associations.index|Associations.view', 'Associations.edit', 'Associations.add', 'Associations.remove',  'Associations.excel', '119', '1', NULL, '2', '2020-10-27 13:28:45', '1', '2016-11-27 03:17:43')");

         // Backup table for locale_contents
        $this->execute('CREATE TABLE `zz_5189_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `zz_5189_locale_contents` SELECT * FROM `locale_contents`');

        $this->execute("INSERT INTO `locale_contents` ( `en`,`modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('StudentAssociations', '2', '2020-10-26 13:28:18', '1', '2019-10-31 11:05:55')");
        $this->execute("INSERT INTO `locale_contents` (`en`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('StaffAssociations', '2', '2020-10-27 13:28:45', '1', '2016-11-27 03:17:43')");
        $this->execute("INSERT INTO `locale_contents` (`en`,`modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('Associations','2','2020-10-26 13:28:18', '1', '2019-10-31 11:05:55')");
        // end locale_contents
   
    }

      // rollback
    public function down()
    {
        $this->execute('DROP TABLE institution_associations');
        $this->execute('DROP TABLE institution_association_student');
        $this->execute('DROP TABLE institution_association_staff');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5189_security_functions` TO `security_functions`');
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `zz_5189_locale_contents` TO `locale_contents`');
    }
}
