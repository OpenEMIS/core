<?php

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Hash;

class POCOR5009 extends AbstractMigration
{
    public function up()
    {
    	// backup 
        $this->execute('CREATE TABLE `temp_security_users` LIKE `security_users`');
		
        // alter
		$this->execute("ALTER TABLE `temp_security_users` MODIFY openemis_no VARCHAR(100) NULL, ADD UNIQUE INDEX `openemis_no_UNIQUE_openemis_no` (`openemis_no`), MODIFY username VARCHAR(100) NULL, ADD UNIQUE INDEX `username_UNIQUE_username` (`username`)");
		
		$this->execute('CREATE TABLE `z_5009_security_users` LIKE `security_users`');
		$this->execute('INSERT INTO `z_5009_security_users` SELECT * FROM `security_users`');
		$sql = "SELECT z_5009_security_users.* FROM z_5009_security_users GROUP BY openemis_no HAVING COUNT(openemis_no) > 1";
		$query = $this->fetchAll($sql);
		
		foreach ($query as $key => $value)
		{
			$this->updateDuplicateOpenEmisNo($value['openemis_no']);
			//sleep(2);
		}
		
		$sqlUser = "SELECT z_5009_security_users.* FROM z_5009_security_users GROUP BY username HAVING COUNT(username) > 1";
		$queryUser = $this->fetchAll($sqlUser);
		foreach ($queryUser as $key => $value)
		{
			$this->updateDuplicateUsername($value['username']);
			//sleep(2);
		}
		$this->execute('INSERT INTO `temp_security_users` SELECT * FROM `z_5009_security_users`');	
		$this->execute('RENAME TABLE `security_users` TO `z_5009_org_security_users`');
		$this->execute('RENAME TABLE `temp_security_users` TO `security_users`');
		
		$this->execute('INSERT INTO security_users SELECT * FROM z_5009_org_security_users WHERE id > (SELECT id FROM `security_users` ORDER BY id DESC  LIMIT 1)');
		

		$this->execute('DROP TABLE `z_5009_security_users`');
    }
	
	private function updateDuplicateOpenEmisNo($openemisNo = null) 
	{
		if ($openemisNo == null) {
			return ;
		}
		
		$sql = "SELECT z_5009_security_users.id ,z_5009_security_users.username FROM z_5009_security_users WHERE `openemis_no` = '".$openemisNo."' ORDER BY openemis_no,created DESC";
		$Data = $this->fetchAll($sql);
		$count = count($Data);
		$flag = 1;
		
		if ($count > 1) {
			foreach ($Data as $key => $value)
			{
				if ( $key != $count-1)
				{
					$id = $value['id'];
					$flag++;
					$newOpenEmisNo =  $this->getUniqueOpenemisId($flag);
					$this->execute('UPDATE `z_5009_security_users` SET `openemis_no` = "'.$newOpenEmisNo.'" WHERE `id` = "'.$id.'"');
					
					
					if($openemisNo  == $value['username']) {
						$this->execute('UPDATE `z_5009_security_users` SET `username` = "'.$newOpenEmisNo.'" WHERE `id` = "'.$id.'"');
						
					}
					
					sleep(2);
				}
				
			}
		}
		
	}
	
	private function updateDuplicateUsername($userName = null) 
	{
		if ($userName == null) {
			$this->execute('UPDATE `z_5009_security_users` SET username = openemis_no WHERE openemis_no = "'.$userName.'" AND username = ""');
		}
		
		$this->execute('UPDATE `z_5009_security_users` SET username = openemis_no WHERE username = "'.$userName.'" AND username != openemis_no');
		
	}
	
	public function getUniqueOpenemisId($flag)
	{
            $User = TableRegistry::get('User.Users');
            $prefix = '';

            $prefix = TableRegistry::get('Configuration.ConfigItems')->value('openemis_id_prefix');
            $prefix = explode(",", $prefix);
            $prefix = ($prefix[1] > 0) ? $prefix[0] : '';

            $latest = $User->find()
                    ->order($User->aliasField('id') . ' DESC')
                    ->first();


            if (is_array($latest)) {
                $latestOpenemisNo = $latest['SecurityUser']['openemis_no'];
            } else {
                $latestOpenemisNo = $latest->openemis_no;
            }

            if (empty($prefix)) {
                $latestDbStamp = $latestOpenemisNo;
            } else {
                $latestDbStamp = substr($latestOpenemisNo, strlen($prefix));
            }

            $currentStamp = time();

            if ($latestDbStamp >= $currentStamp) {
                $newStamp = $latestDbStamp + 1;
            } else {
                $newStamp = $currentStamp;
            }

            $newStamp = $newStamp + $flag;
        return $prefix . $newStamp;
	}
		
	public function down()
       {
	    	$this->execute('DROP TABLE IF EXISTS `security_users`');
            $this->execute('RENAME TABLE `z_5009_org_security_users` TO `security_users`');
       }

}
