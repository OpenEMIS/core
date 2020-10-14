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
        Configure::write('debug', 2);

    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $this->Security->config('unlockedActions', 'add');

        $header = 'Archive';
        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);
        $this->Navigation->addCrumb($this->request->action);

        $this->set('contentHeader', __($header));
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra){

        $header = __('Archive');
        echo '<pre>'; echo $model->alias; die;
        $header .= ' - ' . __($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

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
     * View method
     *
     * @param string|null $id Archive id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $archive = $this->Archives->get($id, [
            'contain' => []
        ]);

        $this->set('archive', $archive);
        $this->set('_serialize', ['archive']);
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
            //if(shell_exec("mysqldump openemis_core > localhost/pocor-openemis-core/webroot/export/backup/'.$fileName.'.sql")){
            exec('C:\xampp7.1\mysql\bin\mysqldump --user=root --password= --host=localhost openemis_core > ' . WWW_ROOT . 'export' . DS . 'backup' . DS . $fileName .'.sql');
          
            die('ankit');

            $session = $this->request->session();
            $firstName = $session->check('Auth.User.first_name') ? $session->read('Auth.User.first_name') : 'System';
            $lastName = $session->check('Auth.User.last_name') ? $session->read('Auth.User.last_name') : 'Administrator';
            
            $this->request->data['name'] = "Backup_".time();
            $this->request->data['path'] = "/webroot/export/backup/";
            $this->request->data['generated_on'] = date("Y-m-d H:i:s");
            $this->request->data['generated_by'] = $firstName.' '.$lastName;

            //echo '<pre>'; print_r($this->request->data); die;

            /*$archive = $this->Archives->patchEntity($archive, $this->request->data);
            if ($this->Archives->save($archive)) {
                $this->Flash->success(__('The archive has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The archive could not be saved. Please, try again.'));*/
        }
        $this->set(compact('archive','available_disksize','dbsize','sizerror'));
        $this->set('_serialize', ['archive']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Archive id.
     * @return \Cake\Network\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $archive = $this->Archives->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $archive = $this->Archives->patchEntity($archive, $this->request->data);
            if ($this->Archives->save($archive)) {
                $this->Flash->success(__('The archive has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The archive could not be saved. Please, try again.'));
        }
        $this->set(compact('archive'));
        $this->set('_serialize', ['archive']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Archive id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $archive = $this->Archives->get($id);
        if ($this->Archives->delete($archive)) {
            $this->Flash->success(__('The archive has been deleted.'));
        } else {
            $this->Flash->error(__('The archive could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
