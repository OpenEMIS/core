<?php
use Migrations\AbstractMigration;

class POCOR5067 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5067_user_attachments` LIKE `user_attachments`');
        $this->execute('INSERT INTO `zz_5067_user_attachments` SELECT * FROM `user_attachments`');

        // START INSTITUTION ATTACHMENT TYPE 
        $table = $this->table('institution_attachment_types', [
            'collation' => 'utf8_general_ci',
            'comment' => 'This table contains the list of Institution Attachment Types'
        ]);
        $table
        ->addColumn('name', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => false
        ])
        ->addColumn('order', 'integer', [
            'default' => null,
            'limit' => 3,
            'null' => false
        ])
        ->addColumn('visible', 'integer', [
            'default' => 1,
            'limit' => 1,
            'null' => false
        ])
        ->addColumn('editable', 'integer', [
            'default' => 1,
            'limit' => 1,
            'null' => false
        ])
        ->addColumn('default', 'integer', [
            'default' => 0,
            'limit' => 1,
            'null' => false
        ])
        ->addColumn('international_code', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => true
        ])
        ->addColumn('national_code', 'string', [
            'default' => null,
            'limit' => 50,
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
        ->addIndex('modified_user_id')
        ->addIndex('created_user_id')
        ->save();
        //END INSTITUTION ATTACHMENT TYPE 


        // START STUDENT ATTACHMENT TYPE 
        $table = $this->table('student_attachment_types', [
            'collation' => 'utf8_general_ci',
            'comment' => 'This table contains the list of Institution Attachment Types'
        ]);
        $table
        ->addColumn('name', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => false
        ])
        ->addColumn('order', 'integer', [
            'default' => null,
            'limit' => 3,
            'null' => false
        ])
        ->addColumn('visible', 'integer', [
            'default' => 1,
            'limit' => 1,
            'null' => false
        ])
        ->addColumn('editable', 'integer', [
            'default' => 1,
            'limit' => 1,
            'null' => false
        ])
        ->addColumn('default', 'integer', [
            'default' => 0,
            'limit' => 1,
            'null' => false
        ])
        ->addColumn('international_code', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => true
        ])
        ->addColumn('national_code', 'string', [
            'default' => null,
            'limit' => 50,
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
        ->addIndex('modified_user_id')
        ->addIndex('created_user_id')
        ->save();
        //END STUDENT ATTACHMENT TYPE 


        // START STAFF ATTACHMENT TYPE 
            $table = $this->table('staff_attachment_types', [
            'collation' => 'utf8_general_ci',
            'comment' => 'This table contains the list of Institution Attachment Types'
        ]);
        $table
        ->addColumn('name', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => false
        ])
        ->addColumn('order', 'integer', [
            'default' => null,
            'limit' => 3,
            'null' => false
        ])
        ->addColumn('visible', 'integer', [
            'default' => 1,
            'limit' => 1,
            'null' => false
        ])
        ->addColumn('editable', 'integer', [
            'default' => 1,
            'limit' => 1,
            'null' => false
        ])
        ->addColumn('default', 'integer', [
            'default' => 0,
            'limit' => 1,
            'null' => false
        ])
        ->addColumn('international_code', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => true
        ])
        ->addColumn('national_code', 'string', [
            'default' => null,
            'limit' => 50,
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
        ->addIndex('modified_user_id')
        ->addIndex('created_user_id')
        ->save();
        //END STUDENT ATTACHMENT TYPE 


        $this->execute('ALTER TABLE `user_attachments` ADD `student_attachment_type_id` INT(11) NULL AFTER `id`, ADD `staff_attachment_type_id` INT(11) NULL AFTER `student_attachment_type_id`');
        $this->execute('ALTER TABLE `institution_attachments` ADD `institution_attachment_type_id` INT(11) NULL AFTER `id`');
        //END USER ATTACHMENT..

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_attachments`');
        $this->execute('RENAME TABLE `zz_5067_institution_attachments` TO `institution_attachments`');

        $this->execute('DROP TABLE IF EXISTS `user_attachments`');
        $this->execute('RENAME TABLE `zz_5067_user_attachments` TO `user_attachments`');

        $this->execute('DROP TABLE IF EXISTS `staff_attachment_types`');
        $this->execute('RENAME TABLE `zz_5067_staff_attachment_types` TO `staff_attachment_types`');

        $this->execute('DROP TABLE IF EXISTS `student_attachment_types`');
        $this->execute('RENAME TABLE `zz_5067_student_attachment_types` TO `student_attachment_types`');

        $this->execute('DROP TABLE IF EXISTS `institution_attachment_types`');
        $this->execute('RENAME TABLE `zz_5067_institution_attachment_types` TO `institution_attachment_types`');


        //$this->execute('RENAME TABLE `zz_6286_security_functions` TO `security_functions`');
    }
}
