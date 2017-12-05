<?php
use Migrations\AbstractMigration;

class POCOR4178 extends AbstractMigration
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
        $loginBackground = new File(WWW_ROOT . 'img' . DS. 'default_images' .DS. 'core-login-bg.jpg');
        $favicon = new File(WWW_ROOT . 'img' . DS .'default_images' .DS. 'favicon.ico');
        $logo = new File(WWW_ROOT . 'img' . DS .'default_images' .DS. 'oe-logo.png');
        $data = [
            [
                'id' => '1',
                'name' => 'Application Name',
                'value' => null,
                'content' => null,
                'default_value' => 'OpenEMIS Core',
                'default_content' => null,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-11-30 01:01:17',
            ],
            [
                'id' => '2',
                'name' => 'Login Page Image',
                'value' => null,
                'content' => null,
                'default_value' => 'core-login-bg.jpg',
                'default_content' => $loginBackground->read(),
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-11-30 01:01:17',
            ],
            [
                'id' => '3',
                'name' => 'Icon/Logo',
                'value' => null,
                'content' => null,
                'default_value' => 'oe-logo.png',
                'default_content' => $logo->read(),
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-11-30 01:01:17',
            ],
            [
                'id' => '4',
                'name' => 'Favicon',
                'value' => null,
                'content' => null,
                'default_value' => 'oe-favicon.ico',
                'default_content' => $favicon->read(),
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-11-30 01:01:17',
            ],
            [
                'id' => '5',
                'name' => 'Colour',
                'value' => null,
                'content' => null,
                'default_value' => '6699CC',
                'default_content' => null,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-11-30 01:01:17',
            ],
            [
                'id' => '6',
                'name' => 'Copyright Notice in Footer',
                'value' => null,
                'content' => null,
                'default_value' => 'Copyright &copy; 2017 OpenEMIS. All rights reserved.',
                'default_content' => null,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-11-30 01:01:17',
            ],
        ];

        $table = $this->table('adaptations')
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => false,
            ])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('content', 'binary', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('default_value', 'string', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('default_content', 'binary', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ]);
        $table->insert($data)->save();
        $logo->close();
        $favicon->close();
        $loginBackground->close();
    }

    public function down()
    {
        $this->dropTable('adaptations');
    }
}
