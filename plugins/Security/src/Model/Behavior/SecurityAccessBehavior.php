<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class SecurityAccessBehavior extends Behavior
{
	public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
	{
        // $options['user'] = ['id' => 4, 'super_admin' => 0];
        if (array_key_exists('user', $options) && is_array($options['user'])) { // the user object is set by RestfulComponent
            $user = $options['user'];
            if ($user['super_admin'] == 0) { // if he is not super admin
                $userId = $user['id'];

                $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
                $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');

                $institutionQuery = $SecurityGroupInstitutions->find()
                    ->select(1)
                    ->innerJoin(['SecurityGroupUser' => 'security_group_users'], [
                        'SecurityGroupUser.security_group_id = SecurityGroupInstitutions.security_group_id',
                        'SecurityGroupUser.security_user_id = ' . $userId
                    ])
                    ->where([
                        $SecurityGroupInstitutions->aliasField('institution_id') . ' = Institutions.id'
                    ]);

                $areaQuery = $SecurityGroupAreas->find()
                    ->select(1)
                    ->innerJoin(['SecurityGroupUser' => 'security_group_users'], [
                        'SecurityGroupUser.security_group_id = SecurityGroupAreas.security_group_id',
                        'SecurityGroupUser.security_user_id = ' . $userId
                    ])
                    ->innerJoin(['AreaAll' => 'areas'], [
                        'AreaAll.id = SecurityGroupAreas.area_id'
                    ])
                    ->where([
                        'AreaAll.lft <= Areas.lft',
                        'AreaAll.rght >= Areas.rght'
                    ]);

                $query->innerJoin(
                    ['Institutions' => 'institutions'],
                    ['Institutions.id = ' . $this->_table->aliasField('institution_id')]
                );
                $query->innerJoin(
                    ['Areas' => 'areas'],
                    ['Areas.id = Institutions.area_id']
                );
                $query->where(['OR' => [
                    'EXISTS (' . $institutionQuery->sql() . ')',
                    'EXISTS (' . $areaQuery->sql() . ')'
                ]]);
            }
        }
	}
}
