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
        $this->loadModel('Archive.DeletedLogs');
        Configure::write('debug', 2);

    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        //echo '<pre>'; print_r($this->request->action); die;

        $this->Security->config('unlockedActions', 'add');
        $this->Security->config('unlockedActions', 'downloadExportDB');

        $header = 'Archive';
        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);

        //Customize header because model name created was different.
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

        $this->Auth->allow(['index', 'download']);

    }

    /*public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Archive') . ' - ' . __($model->alias());
        $this->set('contentHeader', $header); 
    }*/

    /**
     * Index method
     *
     * @return \Cake\Network\Response|null
     */
    public function index(){

        $archives = $this->paginate($this->Archives);

        $this->set(compact('archives'));
        $this->set('_serialize', ['archives']);
    }

    /**
     * Add method
     *
     * @return \Cake\Network\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    { 

        //get database size
        $connection = ConnectionManager::get('default');
        $results = $connection->execute('SELECT table_schema AS "Database",  ROUND(SUM(data_length + index_length) / 1024 / 1024 / 1024, 2) AS "Size" FROM information_schema.TABLES WHERE table_schema = "openemis_core" ORDER BY (data_length + index_length) DESC')->fetch('assoc');
        
        $dbsize = $results['Size'];

        //get available disk size
        $available_disksize = round(disk_free_space('/') / 1024 / 1024 / 1024, 2);

        $sizerror = false;
        if($dbsize >= $available_disksize){
            $sizerror = true;
        }
        //echo '<pre>'; print_r($this->request->data); die;
        
        //post add archive log
        //$archive = $this->Archives->newEntity();
        if ($this->request->is('post')) {

            $fileName = 'Backup_SQL_' . time();
            exec('C:/xampp/mysql/bin/mysqldump --user=root --password= --host=localhost openemis_core > C:/xampp/htdocs/pocor-openemis-core/webroot/export/'.$fileName.'.sql');

            $session = $this->request->session();
            $firstName = $session->check('Auth.User.first_name') ? $session->read('Auth.User.first_name') : 'System';
            $lastName = $session->check('Auth.User.last_name') ? $session->read('Auth.User.last_name') : 'Administrator';
            
            $data['name'] = $fileName;
            $data['path'] = "webroot\export\backup";
            $data['generated_on'] = date("Y-m-d H:i:s");
            $data['generated_by'] = $firstName.' '.$lastName;

            unset($data['Size']);
            unset($data['Available_Space']);
            //echo '<pre>'; print_r($this->request->data); die;
            
            //$archive = $this->Archives->patchEntity($archive, $this->request->data);//entity is returning null, check later
            $archive = $this->Archives->newEntity($this->request->data);
            //echo '<pre>'; print_r($archive); die;
            
            if ($this->Archives->save($archive)) {
                //$this->Flash->success(__('The archive has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            //$this->Flash->error(__('The archive could not be saved. Please, try again.'));
        }
        $this->set(compact('archive','available_disksize','dbsize','sizerror'));
        $this->set('_serialize', ['archive']);
    }

    function downloadSql($archiveId){

        $archiveData = $this->Archives->findById($archiveId)->first();
        $fileLink = WWW_ROOT.'export\a.sql';
        
        if (fopen($fileLink, 'r')){
            //echo 'came'; die;
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename('a.sql'));
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

    //Archive backup module log page //currently not in use
    public function BackupLog(){

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Archive.Archives']);
    }

    //Archive delete module log page
    public function Transfer(){

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Archive.DeletedLogs']);
    }

    public function Connection(){

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Archive.ArchiveConnections']);
    }

}
