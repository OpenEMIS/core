<?php
use Migrations\AbstractSeed;

/**
 * SecurityRoles seed.
 */
class SecurityRolesSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => '1',
                'name' => 'Group Administrator',
                'code' => 'GROUP_ADMINISTRATOR',
                'order' => '3',
                'visible' => '1',
                'security_group_id' => '-1',
                'modified_user_id' => '1',
                'modified' => '2014-04-09 23:35:13',
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '2',
                'name' => 'Administrator',
                'code' => 'ADMINISTRATOR',
                'order' => '2',
                'visible' => '1',
                'security_group_id' => '-1',
                'modified_user_id' => '1',
                'modified' => '2014-04-09 23:35:13',
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '4',
                'name' => 'Principal',
                'code' => 'PRINCIPAL',
                'order' => '5',
                'visible' => '1',
                'security_group_id' => '-1',
                'modified_user_id' => '1',
                'modified' => '2014-04-10 00:11:49',
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '5',
                'name' => 'Homeroom Teacher',
                'code' => 'HOMEROOM_TEACHER',
                'order' => '6',
                'visible' => '1',
                'security_group_id' => '-1',
                'modified_user_id' => '2',
                'modified' => '2017-10-12 17:06:55',
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '6',
                'name' => 'Teacher',
                'code' => 'TEACHER',
                'order' => '7',
                'visible' => '1',
                'security_group_id' => '-1',
                'modified_user_id' => '2',
                'modified' => '2017-10-20 18:03:36',
                'created_user_id' => '1',
                'created' => '2014-04-03 10:25:25',
            ],
            [
                'id' => '7',
                'name' => 'Staff',
                'code' => 'STAFF',
                'order' => '8',
                'visible' => '1',
                'security_group_id' => '-1',
                'modified_user_id' => '1',
                'modified' => '2014-04-10 00:11:49',
                'created_user_id' => '1',
                'created' => '2014-04-03 10:25:25',
            ],
            [
                'id' => '8',
                'name' => 'Student',
                'code' => 'STUDENT',
                'order' => '10',
                'visible' => '1',
                'security_group_id' => '-1',
                'modified_user_id' => '2',
                'modified' => '2015-09-22 13:32:03',
                'created_user_id' => '2',
                'created' => '2014-04-04 16:42:28',
            ],
            [
                'id' => '9',
                'name' => 'Guardian',
                'code' => '',
                'order' => '9',
                'visible' => '1',
                'security_group_id' => '-1',
                'modified_user_id' => '1',
                'modified' => '2015-05-13 23:57:19',
                'created_user_id' => '1',
                'created' => '2014-04-09 08:01:18',
            ],
        ];

        $table = $this->table('security_roles');
        $table->insert($data)->save();
    }
}
