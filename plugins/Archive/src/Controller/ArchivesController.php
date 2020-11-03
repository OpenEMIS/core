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

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $session = $this->request->session();
        $action = $this->request->params['action'];

        if($action == 'connection'){
            $connectionTable = TableRegistry::get('Archive.TransferConnections');
            $connectionData = $connectionTable->find()->select(['id'])->first()->toArray();
            //echo '<pre>'; print_r($connectionId); die;
            $connectionId = $this->controller->paramsEncode(['id' => $connectionData->id]);

            $this->Navigation->addCrumb('Profile', ['plugin' => 'Archive', 'controller' => 'Archives', 'action' => 'Connection', 'view', $this->ControllerAction->paramsEncode(['id' => $connectionId])]);
        }
        
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {

		$header = 'Archive';    
        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);

        //Customize header because model name created was different and POCOR-5674 requirement was modified.
        if($this->request->action == 'backupLog'){
            $header = __('Archive') . ' - ' . __('Backup');
            $this->Navigation->addCrumb('Backup');
        }elseif($this->request->action == 'transfer'){
            $header = __('Archive') . ' - ' . __('Transfer');
            $this->Navigation->addCrumb('Transfer');
        }elseif($this->request->action == 'connection'){
            $header = __('Archive') . ' - ' . __('Connection');
            $this->Navigation->addCrumb('Connection');
        }
        $this->set('contentHeader', $header); 

        $this->Security->config('unlockedActions', 'add');

        $this->Auth->allow(['index', 'download']);
    }

    function downloadSql($archiveId){

        $backupLog = $this->loadModel('BackupLogs');
        $archiveData = $backupLog->findById($archiveId)->first();
        
        $fileLink = WWW_ROOT .'export\backup' . DS .$archiveData->name . '.sql';
        //$fileLink = WWW_ROOT."export\Backup_SQL_1604298214.sql";
        
        if (fopen($fileLink, 'r')){
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            //header('Content-Disposition: attachment; filename='.basename('Backup_SQL_1604298214.sql'));
            header('Content-Disposition: attachment; filename='.basename($fileLink));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileLink));
            ob_clean();
            flush();
            readfile($fileLink);
            exit;
        }
        return $this->redirect(['action' => 'BackupLog']);
    }

    //Archive backup module log page
    public function BackupLog(){

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Archive.BackupLogs']);
    }

    //Archive delete module log page
    public function Transfer(){

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Archive.TransferLogs']);
    }

    public function Connection(){

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Archive.TransferConnections']);
    }

}
