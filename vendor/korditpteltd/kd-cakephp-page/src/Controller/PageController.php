<?php
namespace Page\Controller;

use ArrayObject;
use Exception;

use Cake\Event\Event;
use Cake\Network\Response;
use Cake\Network\Exception\NotFoundException;
use Cake\Controller\Exception\MissingActionException;
use Cake\Utility\Inflector;
use Cake\Log\Log;

use App\Controller\AppController;

class PageController extends AppController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('Page.Page');
        $this->loadComponent('Paginator');
        $this->loadComponent('RequestHandler');

        $this->Page->setHeader(Inflector::humanize(Inflector::underscore($this->name)));
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
    }

    public function index()
    {
        $page = $this->Page;
        $request = $this->request;
        if (!$page->isActionAllowed(__FUNCTION__)) {
            $page->throwMissingActionException();
        }
        $requestQueries = $request->query;

        $showData = !array_key_exists('data', $requestQueries);

        if ($showData == false && $requestQueries['data'] == 'true') {
            $showData = true;
        }

        if ($request->is(['get', 'ajax']) && $page->hasMainTable() && $showData) {
            $table = $page->getMainTable();

            $primaryKey = $table->primaryKey();
            if (!is_array($primaryKey)) { // if primary key is not composite key, then hide from index page
                $page->exclude($primaryKey);
            }

            $querystring = $page->getQueryString();
            $queryOptions = $page->getQueryOptions();
            $queryOptions->offsetSet('querystring', $querystring);
            $query = $table->find('all');

            $page->autoConditions($table, $query, $querystring); // add where conditions if it exists in querystring

            if ($table->hasFinder('index')) {
                $query = $table->find('index', $queryOptions->getArrayCopy());
            }

            if ($page->isAutoContain()) {
                $contains = $page->getContains($table);
                $query->contain($contains);
            }

            if ($page->isActionAllowed('add')) {
                $page->addToToolbar('add', [
                    'element' => 'Page.button',
                    'title' => __('Add'),
                    'iconClass' => 'fa kd-add',
                    'url' => ['action' => 'add']
                ]);
            }

            if ($page->isActionAllowed('search')) {
                $page->addToToolbar('search', ['element' => 'Page.search']);
            }

            if ($page->hasSearchText()) {
                $searchOptions = new ArrayObject([
                    'searchText' => $page->getSearchText(),
                    'defaultSearch' => true, // default search is turned on
                    'wildcard' => true, // false | left | right (wildcard used by default search)
                    'exclude' => [] // exclude any fields from default search
                ]);

                if ($table->hasFinder('search')) {
                    $query->find('search', ['search' => $searchOptions]);
                }
                if ($searchOptions['defaultSearch'] == true) {
                    $page->defaultSearch($table, $query, $searchOptions);
                }
            }

            $data = [];
            $paginateOptions = $page->getPaginateOptions();

            try {
                $data = $this->Paginator->paginate($query, $paginateOptions->getArrayCopy());
            } catch (NotFoundException $ex) { // if invalid page provided
                if ($request->query('page')) {
                    unset($request->query['page']);
                }
                $data = $this->Paginator->paginate($query, $paginateOptions->getArrayCopy());
            }
            foreach ($data as $entity) {
                $page->attachPrimaryKey($table, $entity);
            }
            $this->set('data', $data);

            if ($page->isAutoRender()) {
                $this->render('Page.Page/index');
            }
        }
    }

    public function add()
    {
        $page = $this->Page;
        $request = $this->request;
        if (!$page->isActionAllowed(__FUNCTION__)) {
            $page->throwMissingActionException();
        }
        $extra = new ArrayObject();

        if ($page->hasMainTable()) {
            $table = $page->getMainTable();
            $entity = $table->newEntity();

            $page->addToToolbar('back', [
                'element' => 'Page.button',
                'title' => __('Back'),
                'iconClass' => 'fa kd-back',
                'url' => ['action' => 'index']
            ]);

            if ($request->is(['post'])) {
                try {
                    $extra['result'] = false;
                    $entity = $table->patchEntity($entity, $request->data, []);
                    $extra['result'] = $table->save($entity);
                } catch (Exception $ex) {
                    Log::write('error', $ex->getMessage());
                }
                $event = $this->dispatchEvent('Controller.Page.addAfterSave', [$entity, $extra], $this);
                if ($event->result instanceof Response) {
                    return $event->result;
                }
            }
            $this->set('data', $entity);

            if ($page->isAutoRender()) {
                $this->render('Page.Page/add');
            }
        }
    }

    public function view($id)
    {
        $page = $this->Page;
        $request = $this->request;
        if (!$page->isActionAllowed(__FUNCTION__)) {
            $page->throwMissingActionException();
        }

        if ($request->is(['get', 'ajax']) && $page->hasMainTable()) {
            $primaryKeyValue = json_decode($page->hexToStr($id), true);

            $page->addToToolbar('back', [
                'element' => 'Page.button',
                'title' => __('Back'),
                'iconClass' => 'fa kd-back',
                'url' => ['action' => 'index']
            ])
            ->addToToolbar('edit', [
                'element' => 'Page.button',
                'title' => __('Edit'),
                'iconClass' => 'fa kd-edit',
                'url' => ['action' => 'edit', $id]
            ]);

            $table = $page->getMainTable();
            $primaryKey = $table->primaryKey();
            if (!is_array($primaryKey)) { // if primary key is not composite key, then hide from index page
                $page->exclude($primaryKey);
            }

            if ($table->exists($primaryKeyValue)) {
                $queryOptions = $page->getQueryOptions();
                $queryOptions->offsetSet('querystring', $page->getQueryString());
                if ($table->hasFinder('view')) {
                    $queryOptions->offsetSet('finder', 'view');
                }
                if ($page->isAutoContain()) {
                    $contains = $page->getContains($table);
                    $queryOptions->offsetSet('contain', $contains);
                }

                $entity = $table->get($primaryKeyValue, $queryOptions->getArrayCopy());
                $page->attachPrimaryKey($table, $entity);
                $this->set('data', $entity);
            }

            if ($page->isAutoRender()) {
                $this->render('Page.Page/view');
            }
        }
    }

    public function edit($id)
    {
        $page = $this->Page;
        $request = $this->request;
        if (!$page->isActionAllowed(__FUNCTION__)) {
            $page->throwMissingActionException();
        }
        $extra = new ArrayObject();

        if ($page->hasMainTable()) {
            $primaryKeyValue = json_decode($page->hexToStr($id), true);
            $page->addToToolbar('back', [
                'element' => 'Page.button',
                'title' => __('Back'),
                'iconClass' => 'fa kd-back',
                'url' => ['action' => 'view', $id]
            ]);
            $table = $page->getMainTable();
            if ($table->exists($primaryKeyValue)) {
                $queryOptions = $page->getQueryOptions();
                $queryOptions->offsetSet('querystring', $page->getQueryString());
                if ($table->hasFinder('edit')) {
                    $queryOptions->offsetSet('finder', 'edit');
                }

                if ($page->isAutoContain()) {
                    $contains = $page->getContains($table);
                    $queryOptions->offsetSet('contain', $contains);
                }
                $entity = $table->get($primaryKeyValue, $queryOptions->getArrayCopy());
                $page->attachPrimaryKey($table, $entity);
            }

            if ($request->is(['post', 'put'])) {
                try {
                    $extra['result'] = false;
                    $entity = $table->patchEntity($entity, $request->data, []);
                    pr($entity->errors());
                    pr($request->data);die;
                    $extra['result'] = $table->save($entity);
                } catch (Exception $ex) {
                    Log::write('error', $ex->getMessage());
                }
                $event = $this->dispatchEvent('Controller.Page.editAfterSave', [$entity, $extra], $this);
                if ($event->result instanceof Response) {
                    return $event->result;
                }
            }
            $this->set('data', $entity);

            if ($page->isAutoRender()) {
                $this->render('Page.Page/edit');
            }
        }
    }

    public function delete($id)
    {
        $page = $this->Page;
        $request = $this->request;
        if (!$page->isActionAllowed(__FUNCTION__)) {
            $page->throwMissingActionException();
        }
    }
}
