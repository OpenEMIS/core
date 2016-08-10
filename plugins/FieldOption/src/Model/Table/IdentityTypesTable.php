<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Log\Log;

class IdentityTypesTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('identity_types');
		parent::initialize($config);
		
		$this->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'identity_type_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'restrict');
    }

	public function afterSave(Event $event, Entity $entity) 
	{
		if ($entity->dirty('default')) { //check whether default value has been changed
			if ($entity->default) { 
				$this->triggerUpdateUserDefaultIdentityNoShell($entity->id);
			} else {
				$this->triggerUpdateUserDefaultIdentityNoShell($this->getDefaultValue());
			}
		}
	}

	public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra) 
	{
		if ($entity->default) { //if the one that is going to be deleted is default identity type
			$event->stopPropagation(); 
			$extra['Alert']['message'] = $this->aliasField('deleteDefault');
			return false;
		}
	}

	public function triggerUpdateUserDefaultIdentityNoShell($params) {
    	$cmd = ROOT . DS . 'bin' . DS . 'cake UpdateUserDefaultIdentityNo ' . $params;
		$logs = ROOT . DS . 'logs' . DS . 'UpdateUserDefaultIdentityNo.log & echo $!';
		$shellCmd = $cmd . ' >> ' . $logs;
		$pid = exec($shellCmd);
		Log::write('debug', $shellCmd);
    }
}