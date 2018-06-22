<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Log\Log;

class SecurityAccessBehavior extends Behavior
{
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.beforeFind'] = ['callable' => 'beforeFind', 'priority' => 1];
        return $events;
    }

    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        // $options['user'] = ['id' => 4, 'super_admin' => 0]; // for testing purposes
        // This logic will only be triggered when the table is accessed by RestfulController
        if (array_key_exists('user', $options) && is_array($options['user']) && !array_key_exists('iss', $options['user'])) { // the user object is set by RestfulComponent
            $user = $options['user'];
            if ($user['super_admin'] == 0) { // if he is not super admin
                $userId = $user['id'];

                $query->find('BySecurityAccess', ['userId' => $userId]);
            }
        }
    }

    public function findBySecurityAccess(Query $query, array $options)
    {
        if (array_key_exists('userId', $options)) {
            $userId = $options['userId'];
            $Institutions = TableRegistry::get('Institution.Institutions');

            $institutionQuery = $Institutions->find()
                ->select([
                    'institution_id' => $Institutions->aliasField('id'),
                    'security_group_id' => 'SecurityGroupUsers.security_group_id',
                    'security_role_id' => 'SecurityGroupUsers.security_role_id'
                ])
                ->innerJoin(['SecurityGroupInstitutions' => 'security_group_institutions'], [
                    ['SecurityGroupInstitutions.institution_id = ' . $Institutions->aliasField('id')]
                ])
                ->innerJoin(['SecurityGroupUsers' => 'security_group_users'], [
                    [
                        'SecurityGroupUsers.security_group_id = SecurityGroupInstitutions.security_group_id',
                        'SecurityGroupUsers.security_user_id = ' . $userId
                    ]
                ])
                ->group([$Institutions->aliasField('id'), 'SecurityGroupUsers.security_group_id', 'SecurityGroupUsers.security_role_id']);

            /* Generated SQL: */

            // SELECT institutions.id AS institution_id, security_group_users.security_group_id, security_group_users.security_role_id
            // FROM institutions
            // INNER JOIN security_group_institutions ON security_group_institutions.institution_id = institutions.id
            // INNER JOIN security_group_users
            //     ON security_group_users.security_group_id = security_group_institutions.security_group_id
            //     AND security_group_users.security_user_id = 4
            // GROUP BY institutions.id, security_group_users.security_group_id, security_group_users.security_role_id


            $areaQuery = $Institutions->find()
                ->select([
                    'institution_id' => $Institutions->aliasField('id'),
                    'security_group_id' => 'SecurityGroupUsers.security_group_id',
                    'security_role_id' => 'SecurityGroupUsers.security_role_id'
                ])
                ->innerJoin(['Areas' => 'areas'], ['Areas.id = ' . $Institutions->aliasField('area_id')])
                ->innerJoin(['AreasAll' => 'areas'], [
                    'AreasAll.lft <= Areas.lft',
                    'AreasAll.rght >= Areas.rght'
                ])
                ->innerJoin(['SecurityGroupAreas' => 'security_group_areas'], [
                    'SecurityGroupAreas.area_id = AreasAll.id'
                ])
                ->innerJoin(['SecurityGroupUsers' => 'security_group_users'], [
                    [
                        'SecurityGroupUsers.security_group_id = SecurityGroupAreas.security_group_id',
                        'SecurityGroupUsers.security_user_id = ' . $userId
                    ]
                ])
                ->group([$Institutions->aliasField('id'), 'SecurityGroupUsers.security_group_id', 'SecurityGroupUsers.security_role_id']);

            /* Generated SQL: */

            // SELECT institutions.id AS institution_id, security_group_users.security_group_id, security_group_users.security_role_id
            // FROM institutions
            // INNER JOIN areas ON areas.id = institutions.area_id
            // INNER JOIN areas AS AreaAll
            //     ON AreaAll.lft <= areas.lft
            //     AND AreaAll.rght >= areas.rght
            // INNER JOIN security_group_areas ON security_group_areas.area_id = AreaAll.id
            // INNER JOIN security_group_users
            //     ON security_group_users.security_group_id = security_group_areas.security_group_id
            //     AND security_group_users.security_user_id = 4
            // GROUP BY institutions.id, security_group_users.security_group_id, security_group_users.security_role_id

            $query->join([
                'table' => '((' . $institutionQuery->sql() . ' ) UNION ( ' . $areaQuery->sql() . '))', // inner join subquery
                'alias' => 'SecurityAccess',
                'type' => 'inner',
                'conditions' => ['SecurityAccess.institution_id = ' . $this->_table->aliasField('institution_id')]
            ]);
        }

        return $query;
    }
}
