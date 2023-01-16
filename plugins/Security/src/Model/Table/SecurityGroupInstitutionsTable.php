<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Datasource\Exception\RecordNotFoundException;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use Cake\I18n\Time;


class SecurityGroupInstitutionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_group_institutions');
		parent::initialize($config);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);
	}

	public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.SecurityGroupInstitutions.afterSave' => 'institutionAfterSave'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

	public function institutionAfterSave(Event $event, Entity $entity)
    {
        if ($entity->isNew()) {
        	$SecurityInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
        	$SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
        	if ($entity->institution_id['_ids']) {
	            foreach ($entity->institution_id['_ids'] as $key => $value) {
	                    $securityInstitution = $SecurityInstitutions->newEntity([
	                        'security_group_id' => $entity->id,
	                        'institution_id' => $value
	                    ]);
	                    $SecurityInstitutions->save($securityInstitution);
	                }
        	}
        	if ($entity->area_id['_ids']) {
	            foreach ($entity->area_id['_ids'] as $key => $value) {
	                    $securityArea = $SecurityGroupAreas->newEntity([
	                        'security_group_id' => $entity->id,
	                        'area_id' => $value
	                    ]);
	                    $SecurityGroupAreas->save($securityArea);
	                }
        	}
        } 
    }
}
