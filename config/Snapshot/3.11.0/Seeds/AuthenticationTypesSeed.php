<?php
use Migrations\AbstractSeed;

/**
 * AuthenticationTypes seed.
 */
class AuthenticationTypesSeed extends AbstractSeed
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
                'name' => 'Google',
            ],
            [
                'id' => '2',
                'name' => 'Saml',
            ],
            [
                'id' => '3',
                'name' => 'OAuth',
            ],
        ];

        $table = $this->table('authentication_types');
        $table->insert($data)->save();
    }
}
