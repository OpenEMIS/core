<?php

use Phinx\Migration\AbstractMigration;

class POCOR4490 extends AbstractMigration
{
    public function up()
    {
        // system_authentications
        $this->execute('RENAME TABLE `system_authentications` TO `z_4490_system_authentications`');
        $this->execute('DROP TABLE IF EXISTS `system_authentications`');

        $SystemAuthentications = $this->table('system_authentications', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains user specified authentication'
        ]);

        $SystemAuthentications
            ->addColumn('code', 'char', [
                'limit' => 16,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null
            ])
            ->addColumn('status', 'integer', [
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('allow_create_user', 'integer', [
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('mapped_username', 'string', [
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('mapped_first_name', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('mapped_last_name', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('mapped_date_of_birth', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('mapped_gender', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('mapped_role', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('mapped_email', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('authentication_type_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to authentication_types.id'
            ])
            ->addIndex('authentication_type_id')
            ->save();

            $this->execute('
                INSERT INTO `system_authentications`
                    (`id`, `code`, `name`, `authentication_type_id`, `status`, `allow_create_user`, `mapped_username`, `mapped_first_name`, `mapped_last_name`, `mapped_date_of_birth`, `mapped_gender`, `mapped_role`)
                SELECT `id`, `code`, `name`, `authentication_type_id`, `status`, `allow_create_user`, `mapped_username`, `mapped_first_name`, `mapped_last_name`, `mapped_date_of_birth`, `mapped_gender`, `mapped_role`
                FROM `z_4490_system_authentications`
            ');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `system_authentications`');
        $this->execute('RENAME TABLE `z_4490_system_authentications` TO `system_authentications`');
    }
}
