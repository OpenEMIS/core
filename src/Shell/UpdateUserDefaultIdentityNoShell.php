<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

class UpdateUserDefaultIdentityNoShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

 	public function main() {
 		$this->out("started");
 		
		$identityType = $this->args[0];

		$this->out('default identity type -> ' . $identityType);

		$conn = ConnectionManager::get('default');

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
			        		AND U2.`identity_type_id` = $identityType
			        		GROUP BY U2.`security_user_id`)
						AND `number` <> '') U
					ON S.`id` = U.`security_user_id`
					SET S.`identity_number` = U.`number`;";
		//pr($query);die;
    	$conn->execute($query);

		$this->out("ended");
	}
}
