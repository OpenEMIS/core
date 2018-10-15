<?php

use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class POCOR4815 extends AbstractMigration
{
    public function up()
    {
        $NationalitiesLookUp = TableRegistry::get('FieldOption.Nationalities');
        $Identities = TableRegistry::get('User.Identities');
        $UserIdentities = $Identities
            ->find()
            ->select([
                $NationalitiesLookUp->aliasField('id'),
                $Identities->aliasField('id'),
                $Identities->aliasField('identity_type_id'),
                $Identities->aliasField('number'),
                $Identities->aliasField('issue_date'),
                $Identities->aliasField('expiry_date'),
                $Identities->aliasField('issue_location'),
                $Identities->aliasField('comments'),
                $Identities->aliasField('security_user_id'),
                $Identities->aliasField('modified_user_id'),
                $Identities->aliasField('modified'),
                $Identities->aliasField('created_user_id'),
                $Identities->aliasField('created'),
            ])
            ->leftJoin(
                [$NationalitiesLookUp->alias() => $NationalitiesLookUp->table()],
                [$NationalitiesLookUp->aliasField('identity_type_id = ') . $Identities->aliasField('identity_type_id')]
            )
            ->toArray();

        $data = [];
        foreach ($UserIdentities as $key => $value) {
            if ($value->modified) {
                $modified = date('Y-m-d H:i:s', strtotime($value->modified));
            } else {
                $modified = null;
            }

            if ($value->issue_date) {
                $issueDate = date('Y-m-d', strtotime($value->issue_date));
            } else {
                $issueDate = null;
            }

            if ($value->expiry_date) {
                $expiryDate = date('Y-m-d', strtotime($value->expiry_date));
            } else {
                $expiryDate = null;
            }

            $data[] = [
                'id' => $value->id,
                'identity_type_id' => $value->identity_type_id,
                'number' => $value->number,
                'issue_date' => $issueDate,
                'expiry_date' => $expiryDate,
                'issue_location' => $value->issue_location,
                'nationality_id' => $value->Nationalities['id'],
                'comments' => $value->comments,
                'security_user_id' => $value->security_user_id,
                'modified_user_id' => $value->modified_user_id,
                'modified' => $modified,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s', strtotime($value->created))
            ];

        }

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
        if (!empty($data)) {
            $this->insert('user_identities', $data);
        }
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `user_identities`');
        $this->execute('RENAME TABLE `z_4815_user_identities` TO `user_identities`');
    }
}
