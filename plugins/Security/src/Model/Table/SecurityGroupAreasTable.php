<?php
namespace Security\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Entity;

class SecurityGroupAreasTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.Institutions.afterSave' => 'institutionAfterSave',
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function institutionAfterSave(Event $event, Entity $entity)
    {
        // check if security group id is dirty instead of new entity as the security group id is save
        // on the institution entity marking the isNew flag false
        if ($entity->dirty('security_group_id')) {
            $classification = $entity->classification;
            if ($classification == $event->subject()->getNonAcademicConstant()) {
                $newSecurityGroupAreaRecord = [
                    'security_group_id' => $entity->security_group_id,
                    'area_id' => $entity->area_id
                ];
                $newEntity = $this->newEntity($newSecurityGroupAreaRecord);
                $this->save($newEntity);
            }
        } else {
            if ($entity->dirty('area_id')) {
                $newAreaId = $entity->area_id;
                $oldAreaId = $entity->getOriginal('area_id');

                $this->updateAll(
                    ['area_id' => $newAreaId],
                    ['security_group_id' => $entity->security_group_id, 'area_id' => $oldAreaId]
                );
            }
        }
    }

    public function getAreasByUser($userId)
    {
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupIds = $SecurityGroupUsers
        ->find('list', ['keyField' => 'id', 'valueField' => 'security_group_id'])
        ->where([$SecurityGroupUsers->aliasField('security_user_id') => $userId])
        ->toArray();

        if (!empty($groupIds)) {
            $areas = $this
            ->find('all')
            ->distinct(['area_id'])
            ->innerJoin(['AreaAll' => 'areas'], ['AreaAll.id = '.$this->aliasField('area_id')])
            ->innerJoin(['Areas' => 'areas'], [
                'Areas.lft >= AreaAll.lft',
                'Areas.rght <= AreaAll.rght'
            ])
            ->select(['area_id', 'lft' => 'Areas.lft', 'rght'=>'Areas.rght'])
            ->where([$this->aliasField('security_group_id') . ' IN ' => $groupIds])
            ->toArray();
            return $areas;
        } else {
            return [];
        }
    }
}
