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
        $this->execute('CREATE TABLE `z_5009_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `z_5009_security_users` SELECT * FROM `security_users`');
        // alter
		$this->execute("ALTER TABLE security_users MODIFY openemis_no VARCHAR(100) NULL");
		$this->execute("ALTER TABLE security_users MODIFY username VARCHAR(100) NULL");
		$sql = "SELECT security_users.* FROM security_users GROUP BY openemis_no HAVING COUNT(openemis_no) > 1";
		$query = $this->fetchAll($sql);
		
		foreach ($query as $key => $value)
		{
			$this->updateDuplicateOpenEmisNo($value['openemis_no']);
			sleep(2);
		}
			
		$this->execute("ALTER TABLE `security_users` DROP INDEX `openemis_no`, ADD UNIQUE INDEX `openemis_no_UNIQUE` (`openemis_no`)");
		$this->execute("ALTER TABLE `security_users` DROP INDEX `username`, ADD UNIQUE INDEX `username_UNIQUE` (`username`)");
		
    }
	
	private function updateDuplicateOpenEmisNo($openemisNo = null) 
	{
		if ($openemisNo == null) {
			return ;
		}
		
		$sql = "SELECT security_users.id ,security_users.username FROM security_users WHERE `openemis_no` = '".$openemisNo."' ORDER BY openemis_no,created DESC";
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
					$this->execute('UPDATE `security_users` SET `openemis_no` = "'.$newOpenEmisNo.'" WHERE `id` = "'.$id.'"');
					
					
					if($openemisNo  == $value['username']) {
						$this->execute('UPDATE `security_users` SET `username` = "'.$newOpenEmisNo.'" WHERE `id` = "'.$id.'"');
						
					}
					
					sleep(2);
				}
				
			}
		}
	}
	
	public function getUniqueOpenemisId($flag)
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

        return $prefix.$newStamp+$flag;
	}
		
	public function down()
    {
		$this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `z_5009_security_users` TO `security_users`');
    }
}
