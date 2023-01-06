<?php

use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class POCOR4815 extends AbstractMigration
{
    public function up()
    {
        // backup existing user_identities and create new user_identities
        $this->execute('RENAME TABLE `user_identities` TO `z_4815_user_identities`');
        $table = $this->table('user_identities', [
            'collation' => 'utf8_general_ci',
            'comment' => 'This table contains identity information of every user'
        ]);
        $table
            ->addColumn('identity_type_id', 'integer', [
                    'comment' => 'links to identity_types.id',
                    'limit' => 11,
                    'null' => false
            ])
            ->addColumn('number', 'string', [
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('issue_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('expiry_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('issue_location', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('nationality_id', 'integer', [
                'comment' => 'links to nationalities.id',
                'limit' => 11,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('security_user_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
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
            ->addIndex('identity_type_id')
            ->addIndex('security_user_id')
            ->addIndex('nationality_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $countData = $this->fetchAll('SELECT count(*) AS `COUNT` FROM `z_4815_user_identities` LEFT JOIN `nationalities` on `z_4815_user_identities`.`identity_type_id` = `nationalities`.`identity_type_id`');
        $count = $countData[0]['COUNT'];
        $MAX_PER_LOOP = 10000;
        $iteration = ceil($count / $MAX_PER_LOOP);

        for ($i = 0; $i < $iteration; $i++) {
            $UserIdentities = $this->fetchAll('SELECT `nationalities`.`id` AS `nationalities_id`, `z_4815_user_identities`.`id` AS `user_identities_id`, `z_4815_user_identities`.`identity_type_id`, `z_4815_user_identities`.`number`, `z_4815_user_identities`.`issue_date`, `z_4815_user_identities`.`expiry_date`, `z_4815_user_identities`.`issue_location`, `z_4815_user_identities`.`comments`, `z_4815_user_identities`.`security_user_id`, `z_4815_user_identities`.`modified_user_id`, `z_4815_user_identities`.`modified`, `z_4815_user_identities`.`created_user_id`, `z_4815_user_identities`.`created` FROM `z_4815_user_identities` LEFT JOIN `nationalities` on `z_4815_user_identities`.`identity_type_id` = `nationalities`.`identity_type_id` LIMIT '.$MAX_PER_LOOP.' OFFSET '. ($i * $MAX_PER_LOOP));

            $data = [];
            foreach ($UserIdentities as $key => $value) {
                $data[] = [
                    'identity_type_id' => $value['identity_type_id'],
                    'number' => $value['number'],
                    'issue_date' => $value['issue_date'],
                    'expiry_date' => $value['expiry_date'],
                    'issue_location' => $value['issue_location'],
                    'nationality_id' => $value['nationalities_id'],
                    'comments' => $value['comments'],
                    'security_user_id' => $value['security_user_id'],
                    'modified_user_id' => $value['modified_user_id'],
                    'modified' => $value['modified'],
                    'created_user_id' => $value['created_user_id'],
                    'created' => $value['created']
                ];
            }
            if (!empty($data)) {
                $this->insert('user_identities', $data);
            }
        }
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `user_identities`');
        $this->execute('RENAME TABLE `z_4815_user_identities` TO `user_identities`');
    }
}
