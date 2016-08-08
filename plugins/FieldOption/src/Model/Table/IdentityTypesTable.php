<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Log\Log;

use Cake\Datasource\ConnectionManager;

class IdentityTypesTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('identity_types');
		parent::initialize($config);
		
		$this->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'identity_type_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
    }

	public function afterSave(Event $event, Entity $entity) 
	{
		if ($entity->dirty('default')) { //check whether default value has been changed
			if ($entity->default) { 
				$this->updateIdentityNumber(null, $entity->id);
			} else {
				$this->updateIdentityNumber(null, $this->getDefaultValue());
			}
		}
	}

	public function afterDelete(Event $event, Entity $entity)
	{
		//logic for delete transfer
		if ($entity->default) { //if the deleted one is the default
			$this->updateAll(['default' => 1], ['id' => $entity->convert_to]); //then the destination also need to be set as default.
			$this->updateIdentityNumber($entity->id, $entity->convert_to); //also update identity_number, need the old identity type because the actual transfer is done after this event.
		} else { //if not default
			if ($entity->convert_to == $this->getDefaultValue()) { //then check on the destination whether it is default.
				$this->updateIdentityNumber($entity->id, $entity->convert_to);
			}
		}
	}

    private function updateIdentityNumber($oldIdentityType, $newIdentityType)
    {
    	$conn = $this->connection();

    	$conditions = "AND (U2.`identity_type_id` = $newIdentityType";
    	if ($oldIdentityType) { //check whether request come from delete
    		$conditions .= " OR U2.`identity_type_id` = $oldIdentityType";
    	}
    	$conditions .= ")";

    	$conn->execute("UPDATE `security_users` SET `identity_number` = NULL"); //set all back to NULL

    	//update based on the default indentity type and get the latest record to be put on identity_number field.
    	$query = "UPDATE `security_users` S 
					INNER JOIN (
					    SELECT `security_user_id`, `number`
					    FROM `user_identities` U1
					    WHERE `created` = (
			        		SELECT MAX(U2.`created`)
			        		FROM `user_identities` U2
			        		WHERE U1.`security_user_id` = U2.`security_user_id`
			        		$conditions
			        		GROUP BY U2.`security_user_id`)
						AND `number` <> '') U
					ON S.`id` = U.`security_user_id`
					SET S.`identity_number` = U.`number`;";
		//pr($query);die;
    	$conn->execute($query);
    }
}