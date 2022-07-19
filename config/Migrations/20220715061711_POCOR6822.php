<?php
use Migrations\AbstractMigration;

class POCOR6822 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_6822_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6822_security_functions` SELECT * FROM `security_functions`');

        // security_functions
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `name` = "Staff" AND `controller` = "ProfileTemplates" AND `module` = "Administration"');
        $order = $row['order'];

        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= ' . $order);
        $this->insert('security_functions', [
                'name' => 'Classes',
                'controller' => 'ProfileTemplates',
                'module' => 'Administration',
                'category' => 'Profiles',
                'parent_id' => 5000,
                '_view' => 'Classes.index|Classes.view|ClassProfiles.view|ClassProfiles.view',
                '_edit' => 'Classes.edit',
                '_add' => 'Classes.add',
                '_delete' => 'Classes.remove',
                '_execute' => 'ClassProfiles.generate|ClassProfiles.downloadExcel|ClassProfiles.publish|ClassProfiles.unpublish|ClassProfiles.email|ClassProfiles.downloadAll|ClassProfiles.generateAll|ClassProfiles.publishAll|ClassProfiles.unpublishAll',
                'order' => $order,
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]); 

        //class_report_card_processes
        $this->table('class_report_card_processes', [
            'id' => false,
            'collation' => 'utf8mb4_unicode_ci',
            'primary_key' => ['report_card_id', 'institution_id'],
        ])
        ->addColumn('report_card_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to report_cards.id'
        ])
        ->addColumn('status', 'integer', [
            'limit' => 2,
            'null' => false,
            'comment' => '1 => New 2 => Running 3 => Completed -1 => Error'
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
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false
        ])
        ->addIndex('academic_period_id')
        ->addIndex('institution_id')
        ->addIndex('report_card_id')
        ->save();

        //class_report_cards
        $this->table('class_report_cards', [
            'id' => false,
            'primary_key' => ['report_card_id', 'institution_id', 'academic_period_id'],
            'collation' => 'utf8mb4_unicode_ci'
        ])
        ->addColumn('id', 'char', [
            'limit' => 64,
            'null' => false,
        ])
        ->addColumn('status', 'integer', [
            'limit' => 1,
            'null' => false,
            'comment' => '1 -> New, 2 -> In Progress, 3 -> Generated, 4 -> Published'
        ])
        ->addColumn('file_name', 'string', [
            'limit' => 250,
            'default' => null,
            'null' => true
        ])
        ->addColumn('file_content', 'blob', [
            'limit' => '4294967295',
            'default' => null,
            'null' => true
        ])
        ->addColumn('file_content_pdf', 'blob', [
            'limit' => '4294967295',
            'default' => null,
            'null' => true
        ])
        ->addColumn('started_on', 'datetime', [
            'default' => null,
            'null' => true
        ])
        ->addColumn('completed_on', 'datetime', [
            'default' => null,
            'null' => true
        ])
        ->addColumn('report_card_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to academic_periods.id'
        ])
        ->addColumn('institution_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to academic_periods.id'
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
            'limit' => 11,
            'null' => false
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false
        ])
        ->addIndex('report_card_id')
        ->addIndex('institution_id')
        ->addIndex('academic_period_id')
        ->addIndex('modified_user_id')
        ->addIndex('created_user_id')
        ->save();

        $this->execute('ALTER TABLE `class_report_cards` ADD `institution_class_id` INT(11) NOT NULL COMMENT "links to institution_classes.id" AFTER `academic_period_id`');
        $this->execute('ALTER TABLE `class_report_cards` ADD INDEX(`institution_class_id`)');
        $this->execute('ALTER TABLE `class_report_cards` DROP PRIMARY KEY, ADD PRIMARY KEY( `report_card_id`, `institution_id`, `academic_period_id`, `institution_class_id`)');

        $this->execute('ALTER TABLE `class_report_card_processes` ADD `institution_class_id` INT(11) NOT NULL COMMENT "links to institution_classes.id" AFTER `academic_period_id`');
        $this->execute('ALTER TABLE `class_report_card_processes` ADD INDEX(`institution_class_id`)');

        $this->execute('ALTER TABLE `class_report_cards` CHANGE `file_name` `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL');

        $this->execute('ALTER TABLE `class_report_cards` CHANGE `file_content` `file_content` LONGBLOB NULL DEFAULT NULL');

        $this->execute('ALTER TABLE `class_report_cards` CHANGE `file_content_pdf` `file_content_pdf` LONGBLOB NULL DEFAULT NULL');
                
        $this->execute('ALTER TABLE `class_report_cards` CHANGE `started_on` `started_on` DATETIME NULL DEFAULT NULL');

        $this->execute('ALTER TABLE `class_report_cards` CHANGE `completed_on` `completed_on` DATETIME NULL DEFAULT NULL');
        
        //class_profile_templates
        $this->table('class_profile_templates', [
            'collation' => 'utf8mb4_unicode_ci',
            'primary_key' => 'id',
            'id' => true //Auto increment id and primary key
        ])
        ->addColumn('code', 'string', [
            'limit' => 50,
            'null' => false
        ])
        ->addColumn('name', 'string', [
            'limit' => 150,
            'null' => false
        ])
        ->addColumn('description', 'text', [
            'default' => null,
            'null' => false
        ])
        ->addColumn('generate_start_date', 'datetime', [
            'default' => null,
            'null' => false
        ])
        ->addColumn('generate_end_date', 'datetime', [
            'default' => null,
            'null' => false
        ])
        ->addColumn('excel_template_name', 'string', [
            'limit' => 250,
            'default' => null,
            'null' => false
        ])
        ->addColumn('excel_template', 'blob', [
            'limit' => '4294967295',
            'default' => null,
            'null' => false
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
            'limit' => 11,
            'null' => false
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false
        ])
        ->addIndex('academic_period_id')
        ->addIndex('modified_user_id')
        ->addIndex('created_user_id')
        ->save();     
    }

    // rollback
    public function down()
    {
        // rollback of security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6822_security_functions` TO `security_functions`');
        
        //rollback of class_profile_templates,class_report_card_processes,class_report_cards
        $this->execute('DROP TABLE IF EXISTS `class_profile_templates`');
        $this->execute('DROP TABLE IF EXISTS `class_report_card_processes`');
        $this->execute('DROP TABLE IF EXISTS `class_report_cards`');
    }
}
