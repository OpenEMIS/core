<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query;
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

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

    public function findDefaultIdentityType(Query $query, array $options) 
    {
        $defaultId = $this->getDefaultValue();
        return $query->where([$this->aliasField('id') => $defaultId]);
    }

	public function addEditBeforePatch(Event $event, Entity $entity) 
	{
		$entity->prevDefaultIdentityType = $this->getDefaultValue(); //keep the current default value before it is being updated.
	}

	public function afterSave(Event $event, Entity $entity) 
	{	
		if ($entity->default) { //if the current set as default
			if ($entity->prevDefaultIdentityType != $entity->id) { //if new default
				//run shell process to update identity_number on security_table
				$this->triggerUpdateUserDefaultIdentityNoShell($entity->id);
			}
		} else { //to cater if user edit default to become no-default.
			if ($entity->prevDefaultIdentityType == $entity->id) {
				$this->triggerUpdateUserDefaultIdentityNoShell($this->getDefaultValue());
			}
		}
	}

	public function afterDelete(Event $event, Entity $entity)
	{	
		//during delete, if the deleted one is the default identity value then need to update "identity_number" field value.
		if ($entity->default) {
			$this->triggerUpdateUserDefaultIdentityNoShell($this->getDefaultValue());
		}
	}

	public function triggerUpdateUserDefaultIdentityNoShell($params) 
	{
    	$cmd = ROOT . DS . 'bin' . DS . 'cake UpdateUserDefaultIdentityNo ' . $params;
		$logs = ROOT . DS . 'logs' . DS . 'UpdateUserDefaultIdentityNo.log & echo $!';
		$shellCmd = $cmd . ' >> ' . $logs;
		$pid = exec($shellCmd);
		Log::write('debug', $shellCmd);
    }
}
