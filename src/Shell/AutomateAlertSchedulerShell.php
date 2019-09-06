<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Console\Shell;

class AutomateAlertSchedulerShell extends Shell
{
    //CONST SLEEP_TIME = 10;
    //CONST LIMIT = 15;
    

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Alert.Alerts');
    }

    public function main()
    {
       $this->Alerts->alertToProcess();
    }

    
    
}
