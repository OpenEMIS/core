<?php
namespace App\Shell;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\Mailer\Email;
use Cake\Datasource\ConnectionManager;

class DatabaseSqlDumpShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {
        $connection = ConnectionManager::get('default');

        $dbConfig = $connection->config();
        $username = $dbConfig['username']; 
        $host = $dbConfig['host']; 
        $dbname = $dbConfig['database']; 
        $password = $dbConfig['password']; 
        $fileName = !empty($this->args[0]) ? $this->args[0] : 0;

        //echo 'mysqldump --user='.$username.' --password='.$password.' --host='.$host.' '.$dbname.' > '.WWW_ROOT.'export/backup' . DS .$fileName.'.sql'; die;
        exec('mysqldump --user='.$username.' --password='.$password.' --host='.$host.' '.$dbname.' > '.WWW_ROOT.'export/backup' . DS .$fileName.'.sql');

    }
}
