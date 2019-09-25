<?php
use Cake\I18n\Date;
use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Cake\Utility\Hash;

class POCOR5009USERNAME extends AbstractMigration
{
    public function up()
    {
		$this->execute('CREATE TABLE `z_5009_username_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_5009_username_security_users` SELECT * FROM `security_users`');
		$sql = "SELECT username FROM security_users GROUP BY username HAVING COUNT(username) > 1";
		$query = $this->fetchAll($sql);
		
		foreach ($query as $key => $value)
		{
			$this->updateDuplicateUserName($value['username']);
			sleep(2);
			
		}
		
	}
	
	private function updateDuplicateUserName($userName = null) 
	{
		if ($userName == null) {
			return ;
		}
		
		$sql = "SELECT security_users.id FROM security_users WHERE `username` = '".$userName."'   ORDER BY username,created DESC";
		$query = $this->fetchAll($sql);
		$count = count($query);
		$flag = 1;
		
		if ($count > 1) {
			foreach ($query as $key => $value)
			{
				if ($key != $count-1)
				{
					$id = $value['id'];
					$flag++;
					$this->execute('UPDATE `security_users` SET `username` = "'.$this->getUniqueUserName($flag).'" WHERE `id` = "'.$id.'"');
				}
				
			}
		}
	}
	
	public function getUniqueUserName($flag)
	{
		$User = TableRegistry::get('User.Users');
		$prefix = '';

        $prefix = TableRegistry::get('Configuration.ConfigItems')->value('openemis_id_prefix');
        $prefix = explode(",", $prefix);
        $prefix = ($prefix[1] > 0)? $prefix[0]: '';

        $latest = $User->find()
            ->order($User->aliasField('id').' DESC')
            ->first();
	

        if (is_array($latest)) {
            $latestOpenemisNo = $latest['SecurityUser']['username'];
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

        return $prefix.$newStamp+$flag;
	}
	
	public function down()
	{
		$this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `z_5009_username_security_users` TO `security_users`');
	}
}		