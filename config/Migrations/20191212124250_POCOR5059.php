<?php

use Phinx\Migration\AbstractMigration;

class POCOR5059 extends AbstractMigration
{
    // commit
    public function up()
    {
        // user_identities
		$this->execute('CREATE TABLE `zz_5059_user_identities` LIKE `user_identities`');
        $this->execute('INSERT INTO `zz_5059_user_identities` SELECT * FROM `user_identities`');
		
		// security_users
        $this->execute('CREATE TABLE `zz_5059_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `zz_5059_security_users` SELECT * FROM `security_users`');

        // update nationality id in user_identities
        $this->execute('UPDATE user_identities 
			JOIN security_users
			ON user_identities.security_user_id = security_users.id AND 
			user_identities.identity_type_id = security_users.identity_type_id
			SET user_identities.nationality_id = security_users.nationality_id 
            WHERE user_identities.nationality_id=""');
		
        // update indentity number in security_users table
		$userIdentityRows = $this->fetchAll('SELECT * FROM `user_identities` WHERE number <> "" GROUP BY security_user_id ORDER BY id ASC');
		foreach($userIdentityRows as $userIdentityRow){
			$this->execute('UPDATE security_users
			SET security_users.identity_number = "'.$userIdentityRow['number'].'"
			WHERE security_users.identity_number=""');
		}
    }

    // rollback
    public function down()
    {
        //user_identities
        $this->execute('DROP TABLE IF EXISTS `user_identities`');
        $this->execute('RENAME TABLE `zz_5059_user_identities` TO `user_identities`');
		
		//security_users
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `zz_5059_security_users` TO `security_users`');
    }
}
