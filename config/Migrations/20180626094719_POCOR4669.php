<?php

use Phinx\Migration\AbstractMigration;

class POCOR4669 extends AbstractMigration
{
    public function up()
    {
        // locale_contents
        $this->execute('CREATE TABLE `z_4669_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_4669_locale_contents` SELECT * FROM `locale_contents`');

        $table = $this->table('locale_contents');
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'en' => 'To recover your username, enter the email address you use in this system.',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Recover Username',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Return to login',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Recover Your Username',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Recover Your Password',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'To reset your password, enter the email address or username you used in this system. A link will be emailed to you which will let you reset your password.',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Username or Email',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Reset Password',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Please check your email for further instructions.',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Please check your email for more information.',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Please enter a valid email address.',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Your password has been reset successfully.',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Sorry, there was an error. Please retry your request.',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Forgot username?',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Forgot password?',
                'created_user_id' => 1,
                'created' => $now
            ]
        ];

        $table
            ->insert($data)
            ->save();
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_4669_locale_contents` TO `locale_contents`');
    }
}
