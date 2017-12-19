<?php
use Migrations\AbstractMigration;
use Cake\Filesystem\File;
use Cake\Core\Configure;

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
        $table = $this->table('config_items');
        $data = [
            [
                'id' => '1005',
                'name' => 'Themes',
                'code' => 'themes',
                'type' => 'Themes',
                'label' => 'Themes',
                'value' => '0',
                'default_value' => '0',
                'editable' => '1',
                'visible' => '1',
                'field_type' => '',
                'option_type' => '',
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-11-30 01:01:17'
            ],
        ];

        $table->insert($data)->save();

        $bgFile = Configure::read('schoolMode') ? 'school-login-bg.jpg' : 'core-login-bg.jpg';
        $loginBackground = new File(WWW_ROOT . 'img' . DS. 'default_images' .DS. $bgFile);
        $favicon = new File(WWW_ROOT . 'img' . DS .'default_images' .DS. 'favicon.ico');
        $logo = new File(WWW_ROOT . 'img' . DS .'default_images' .DS. 'oe-logo.png');
        $productName = Configure::read('schoolMode') ? 'OpenSMIS '.Configure::read('schoolMode') : 'OpenEMIS Core';
        $color = Configure::read('schoolMode') ? '3366CC' : '6699CC';
        $copyright = Configure::read('schoolMode') ? '2017 - {{currentYear}} OpenSMIS' : '2015 - {{currentYear}} OpenEMIS';
        $data = [
            [
                'id' => '1',
                'name' => 'Application Name',
                'value' => null,
                'content' => null,
                'default_value' => $productName,
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
                'default_value' => $bgFile,
                'default_content' => $loginBackground->read(),
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-11-30 01:01:17',
            ],
            [
                'id' => '3',
                'name' => 'Logo',
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
                'id' => '5',
                'name' => 'Colour',
                'value' => null,
                'content' => null,
                'default_value' => $color,
                'default_content' => null,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-11-30 01:01:17',
            ],
            [
                'id' => '6',
                'name' => 'Copyright Notice In Footer',
                'value' => null,
                'content' => null,
                'default_value' => 'Copyright &copy; '. $copyright. '. All rights reserved.',
                'default_content' => null,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => '2017-11-30 01:01:17',
            ],
        ];

        $table = $this->table('themes')
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
            ->addColumn('content', 'blob', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('default_value', 'string', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('default_content', 'blob', [
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

        $this->execute("UPDATE `security_functions` SET `_view`='index|view|Themes.index|Themes.view', `_edit`='edit|Themes.edit' WHERE `id`='5020'");
    }

    public function down()
    {
        $this->execute("DELETE FROM `config_items` WHERE `id`=1005");
        $this->dropTable('themes');
        $this->execute("UPDATE `security_functions` SET `_view`='index|view', `_edit`='edit' WHERE `id`=5020");
    }
}
