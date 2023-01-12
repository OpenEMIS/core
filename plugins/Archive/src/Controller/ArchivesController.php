<?php
namespace Archive\Controller;

use ArrayObject;
use Exception;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use Cake\I18n\Date;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use App\Model\Traits\OptionsTrait;
use Archive\Controller\AppController;
use ControllerAction\Model\Traits\UtilityTrait;
use Cake\Datasource\ConnectionManager;


/**
 * Archives Controller
 *
 * @property \App\Model\Table\ArchivesTable $Archives */
class ArchivesController extends AppController
{

    use OptionsTrait;
    use UtilityTrait;

    public function initialize(){

        parent::initialize();
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {

		/*$header = 'Archive';    
        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);*/

        
        //Customize header because model name created was different and POCOR-5674 requirement was modified.
        // POCOR-6816  change breadcrumb for every url action.
        if($this->request->action == 'BackupLog'){
            $headers = 'Data Management';    
            $this->Navigation->addCrumb($headers, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);
            $header = __('Data Management') . ' - ' . __('Backup');
            $this->Navigation->addCrumb('Backup');
        }elseif($this->request->action == 'Transfer'){
            $headers = 'Data Management';    
            $this->Navigation->addCrumb($headers, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);
            $header = __('Data Management') . ' - ' . __('Archive');
            $this->Navigation->addCrumb('Archive');
        }elseif($this->request->action == 'Connection'){
            $headers = 'Data Management';    
            $this->Navigation->addCrumb($headers, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);
            $header = __('Data Management') . ' - ' . __('Connection');
            $this->Navigation->addCrumb('Connection');
        }elseif($this->request->action == 'CopyData'){
            $headers = 'Data Management';    
            $this->Navigation->addCrumb($headers, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);
            $header = __('Data Management') . ' - ' . __('Copy');
            $this->Navigation->addCrumb('Copy');
        }
        $this->set('contentHeader', $header); 

        $this->Security->config('unlockedActions', 'add');

        $this->Auth->allow(['index', 'download']);
    }

    function downloadSql($archiveId){

        $backupLog = $this->loadModel('BackupLogs');
        $archiveData = $backupLog->findById($archiveId)->first();
        $fileLink = WWW_ROOT .'export/backup' . DS .$archiveData->name . '.sql';
        $filetype=filetype($fileLink);
        $filename=basename($fileLink);
        header ("Content-Type: ".$filetype);
        header ("Content-Length: ".filesize($fileLink));
        header ("Content-Disposition: attachment; filename=".$filename);
        readfile($fileLink);
        exit();
    }

    //POCOR-6281[START]
    public function CopyData(){
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Archive.DataManagementCopy']);
    }
    //POCOR-6281[END]

    //Archive backup module log page
    public function BackupLog(){

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Archive.BackupLogs']);
    }

    //Archive delete module log page
    public function Transfer(){

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Archive.TransferLogs']);
    }

    public function Connection(){

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Archive.DataManagementConnections']);
    }

}
