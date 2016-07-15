<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;

class UpdateUserDefaultIdentityNoShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

 	public function main() {
 		$this->out("started");
 		
		$identityType = $this->args[0];

		$this->out('default identity type -> ' . $identityType);

		$userTable = TableRegistry::get('User.Users');
		$Identities = TableRegistry::get('User.Identities');

		//query will get the latest identy number per user based on the default identity type
		$query = $Identities->find()
					->select([
						$Identities->aliasField('security_user_id'),
						$Identities->aliasField('number')
					])
					->leftJoin(['IdentitiesClone' => 'user_identities'], [
							'IdentitiesClone.security_user_id = '. $Identities->aliasField('security_user_id'),
							'IdentitiesClone.created > '. $Identities->aliasField('created'),
							'IdentitiesClone.identity_type_id = ' . $identityType
						])
					->where([
							'IdentitiesClone.security_user_id IS NULL',
							$Identities->aliasField('identity_type_id') => $identityType,
							$Identities->aliasField('number') . ' <> \'\''
						]);					
		//$this->out($query->sql());
		$query = $query->toArray();

		$userTable->updateAll(['identity_number' => NULL], []); //will reset all identity number to NULL

		//loop to update all identity number for each user.
		foreach ($query as $key => $value) {
			$userTable->updateAll(['identity_number' => $value['number']], ['id' => $value['security_user_id']]);
		}

		$this->out("ended");
	}
}
