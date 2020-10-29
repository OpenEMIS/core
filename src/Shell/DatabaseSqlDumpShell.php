<?php
namespace App\Shell;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\Mailer\Email;

class DatabaseSqlDumpShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {

        $fileName = !empty($this->args[0]) ? $this->args[0] : 0;

        exec('mysqldump --user=root --password= --host=localhost openemis_core > '.WWW_ROOT.'export/backup/'.$fileName.'.sql');

    }
}
