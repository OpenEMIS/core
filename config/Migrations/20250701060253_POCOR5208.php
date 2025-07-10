<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR5208 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `z_5208_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_5208_security_functions` SELECT * FROM `security_functions`');

        // infrastructure_attachment_types Start
        $table = $this->table('infrastructure_attachment_types', [
            'id' => false, // We'll define ID manually
            'primary_key' => ['id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of infrastructure attachment types'
        ]);

        $table
            ->addColumn('id', 'integer', [
                'limit' => 11,
                'null' => false,
                'signed' => false,
                'identity' => true, // auto_increment
            ])
            ->addColumn('name', 'string', [
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('visible', 'integer', [
                'null' => true,
                'default' => 1,
            ])
            ->addColumn('editable', 'integer', [
                'null' => true,
                'default' => 1,
            ])
            ->addColumn('default', 'integer', [
                'null' => true,
                'default' => 0,
            ])
            ->addColumn('international_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('national_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['modified_user_id'])
            ->addIndex(['created_user_id'])
            ->addForeignKey('modified_user_id', 'security_users', 'id', [
                'delete'=> 'NO_ACTION',
                'update'=> 'NO_ACTION',
                'constraint' => 'fk_infrastructure_attachment_types_modified_user'
            ])
            ->addForeignKey('created_user_id', 'security_users', 'id', [
                'delete'=> 'NO_ACTION',
                'update'=> 'NO_ACTION',
                'constraint' => 'fk_infrastructure_attachment_types_created_user'
            ])
        ->create();
        // infrastructure_attachment_types End

        // institution_infrastructure_attachments
        $table = $this->table('institution_infrastructure_attachments', [
            'id' => false, // We'll define ID manually
            'primary_key' => ['id'],
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('id', 'integer', [
                'limit' => 11,
                'null' => false,
                'signed' => false,
                'identity' => true, // auto_increment
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => false
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('infrastructure_attachment_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
                'comment' => 'links to infrastructure_attachment_types.id'
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
            ->addIndex('institution_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->addForeignKey('institution_id', 'institutions', 'id', [
                'delete'=> 'NO_ACTION',
                'update'=> 'NO_ACTION',
                'constraint' => 'fk_institution_infrastructure_attachments_institution'
            ])
            ->addForeignKey('infrastructure_attachment_type_id', 'infrastructure_attachment_types', 'id', [
                'delete'=> 'NO_ACTION',
                'update'=> 'NO_ACTION',
                'constraint' => 'fk_infrastructure_attachments_infrastructure_attachment_type'
            ])
            ->addForeignKey('modified_user_id', 'security_users', 'id', [
                'delete'=> 'NO_ACTION',
                'update'=> 'NO_ACTION',
                'constraint' => 'fk_institution_infrastructure_attachments_modified_user'
            ])
            ->addForeignKey('created_user_id', 'security_users', 'id', [
                'delete'=> 'NO_ACTION',
                'update'=> 'NO_ACTION',
                'constraint' => 'fk_institution_infrastructure_attachments_created_user'
            ])
        ->create();
        //end institution_infrastructure_attachments

        // Adding new entry into field options
        $this->execute('CREATE TABLE `z_5208_field_options` LIKE `field_options`');
        $this->execute('INSERT INTO `z_5208_field_options` SELECT * FROM `field_options`');

        $orderRow = $this->fetchRow("SELECT `order` FROM `field_options` ORDER BY `id` DESC LIMIT 1");
        $nextOrder = isset($orderRow['order']) ? $orderRow['order'] + 1 : 1;

        $this->execute("
            INSERT INTO `field_options` (
                `name`, `category`, `table_name`, `order`, 
                `modified_by`, `modified`, `created_by`, `created`
            ) VALUES (
                'Infrastructure Attachment Types', 
                'Infrastructure', 
                'infrastructure_attachment_types', 
                {$nextOrder},
                NULL,
                NULL,
                1,
                '" . date('Y-m-d H:i:s') . "'
            )
        ");

        // Adding new entry into security_functions table
        // CHECK THAT THERE IS NO SUCH SECURITY FUNCTION FIRST
        $query = $this->fetchRow("SELECT * FROM `security_functions`
                                  WHERE `name` = 'Infrastructure Attachments' AND `controller` = 'Institutions'
                                  AND `module` = 'Institutions' AND `category` = 'Details'");
        if (!$query) {
            $this->execute("INSERT INTO `security_functions` (
                                  `id`,
                                  `name`,
                                  `controller`,
                                  `module`,
                                  `category`,
                                  `parent_id`,
                                  `_view`,
                                  `_edit`,
                                  `_add`,
                                  `_delete`,
                                  `_execute`,
                                  `order`,
                                  `visible`,
                                  `description`,
                                  `modified_user_id`,
                                  `modified`,
                                  `created_user_id`,
                                  `created`) VALUES (NULL,
                                                     'Infrastructure Attachments', 'Institutions', 'Institutions', 'Details', '8',
                                                     'InfrastructureAttachments.view', 'InfrastructureAttachments.edit',
                                                     'InfrastructureAttachments.add', 'InfrastructureAttachments.delete', NULL,
                                                     '1236', '1', NULL, NULL, NULL,
                                                     '2',  '" . date('Y-m-d H:i:s') . "');"  
            );
        }
    }

    // rollback
    public function down()
    {
        // Drop the table if it exists
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('DROP TABLE IF EXISTS `institution_infrastructure_attachments`');
        $this->execute('DROP TABLE IF EXISTS ` infrastructure_attachment_types`');
        $this->execute('DROP TABLE IF EXISTS `field_options`');
        $this->execute('RENAME TABLE `z_5208_field_options` TO `field_options`');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_5208_security_functions` TO `security_functions`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
