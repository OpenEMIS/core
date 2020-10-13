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

        /*$this->ControllerAction->models = [
            'Backup'  => ['className' => 'Archive.ArchiveTableLogs', 'actions' => ['index', 'add']],
            'Delete'  => ['className' => 'Archive.DeletedTableLogs', 'actions' => ['index', 'delete']],
        ];*/
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        // $this->Navigation->addCrumb('Archive', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Archive', 'index']);

/**
$header = __('Archive');
        echo $model->alias;
        $header .= ' - ' . __($model->getHeader($model->alias));

        $this->set('contentHeader', $header);

*/

        $header = __('Archive');
        $alias = $model->alias();
        print_r($alias);die;
        if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4
            $excludedModel = ['Archive'];

            if (!in_array($alias, $excludedModel)) {
                $model->toggle('add', false);
                $model->toggle('edit', false);
                $model->toggle('remove', false);

                $applicantId = $this->ControllerAction->getQueryString('applicant_id');
                $header = $this->Users->get($applicantId)->name;

                $this->Navigation->addCrumb('Applications', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Applications', 'index']);
                $this->Navigation->addCrumb($header);
                $this->Navigation->addCrumb($model->getHeader($alias));
            }
        }

        $header .= ' - ' . $model->getHeader($alias);
        $this->set('contentHeader', $header);

        $persona = false;
        $event = new Event('Model.Navigation.breadcrumb', $this, [$this->request, $this->Navigation, $persona]);
        $event = $model->eventManager()->dispatch($event);
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $header = 'Archive';
        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);
        $this->Navigation->addCrumb($this->request->action);

        $this->set('contentHeader', __($header));
    }

    // public function onInitialize(Event $event, Table $model, ArrayObject $extra){

    //     $header = __('Archive');
    //     echo $model->alias;
    //     $header .= ' - ' . __($model->getHeader($model->alias));

    //     $this->set('contentHeader', $header);
    // }

    /**
     * Index method
     *
     * @return \Cake\Network\Response|null
     */
    public function index(){

        //echo 'came'; die;
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
        $archive = $this->Archives->newEntity();
        if ($this->request->is('post')) {
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
