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

use Page\Model\Entity\PageStatus;
use App\Controller\AppController;

class PageController extends AppController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('Page.Page');
        $this->loadComponent('Page.Alert');
        $this->loadComponent('Paginator');
        $this->loadComponent('RequestHandler');
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
            $page->setVar('data', $data);
        }
    }

    public function add()
    {
        $page = $this->Page;
        $request = $this->request;
        if (!$page->isActionAllowed(__FUNCTION__)) {
            $page->throwMissingActionException();
        }

        if ($page->hasMainTable()) {
            $table = $page->getMainTable();
            $entity = $table->newEntity();

            if ($request->is(['post'])) {
                $pageStatus = $page->getStatus();
                try {
                    $entity = $table->patchEntity($entity, $request->data, []);
                    $result = $table->save($entity);

                    if ($result) {
                        $pageStatus->setMessage('The record has been added successfully');

                        return;
                    } else {
                        $pageStatus->setCode(PageStatus::VALIDATION_ERROR)
                            ->setType('error')
                            ->setMessage('The record is not added due to errors encountered');
                    }
                } catch (Exception $ex) {
                    $msg = $ex->getMessage();
                    $pageStatus->setCode(PageStatus::UNEXPECTED_ERROR)
                        ->setType('error')
                        ->setError(true)
                        ->setMessage($msg);
                    Log::write('error', $msg);
                }
            }
            $page->setVar('data', $entity);
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
                $page->loadDataToElements($entity);
                $page->setVar('data', $entity);
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
                $pageStatus = $page->getStatus();
                try {
                    $entity = $table->patchEntity($entity, $request->data, []);
                    $result = $table->save($entity);

                    if ($result) {
                        $pageStatus->setMessage('The record has been updated successfully');

                        return;
                    } else {
                        $pageStatus->setCode(PageStatus::VALIDATION_ERROR)
                            ->setType('error')
                            ->setMessage('The record is not updated due to errors encountered');
                    }
                } catch (Exception $ex) {
                    $msg = $ex->getMessage();
                    $pageStatus->setCode(PageStatus::UNEXPECTED_ERROR)
                        ->setType('error')
                        ->setError(true)
                        ->setMessage($msg);
                    Log::write('error', $msg);
                }
            }
            $page->setVar('data', $entity);
        }
    }

    public function delete($id)
    {
        $page = $this->Page;
        $request = $this->request;
        if (!$page->isActionAllowed(__FUNCTION__)) {
            $page->throwMissingActionException();
        }
        $extra = new ArrayObject();

        if ($page->hasMainTable()) {
            $primaryKeyValue = $page->decode($id);
            $table = $page->getMainTable();

            if (!$table->exists($primaryKeyValue)) {
                $page->getStatus()
                    ->setCode(PageStatus::RECORD_NOT_FOUND)
                    ->setType('warning')
                    ->setError(true)
                    ->setMessage('The record does not exists');

                return;
            }

            $queryOptions = $page->getQueryOptions();
            $queryOptions->offsetSet('querystring', $page->getQueryString());
            if ($table->hasFinder('delete')) {
                $queryOptions->offsetSet('finder', 'delete');
            }

            if ($page->isAutoContain()) {
                $contains = $page->getContains($table);
                $queryOptions->offsetSet('contain', $contains);
            }
            $entity = $table->get($primaryKeyValue, $queryOptions->getArrayCopy());

            if ($request->is(['delete'])) {
                $extra['result'] = $table->delete($entity);
                $page->getStatus()->setMessage('The record has been deleted successfully');

                return;
            }

            $page->attachPrimaryKey($table, $entity);

            $msg = __('All associated information related to this record will also be removed. Are you sure you want to delete this record?');
            $this->set('alert', ['type' => 'warning', 'message' => $msg]);
            $cells = [];
            foreach ($table->associations() as $assoc) {
                if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
                    if (!array_key_exists($assoc->alias(), $cells)) {
                        $count = 0;
                        $assocTable = $assoc;
                        if ($assoc->type() == 'manyToMany') {
                            $assocTable = $assoc->junction();
                        }
                        $bindingKey = $assoc->bindingKey();
                        $foreignKey = $assoc->foreignKey();

                        $conditions = [];

                        if (is_array($foreignKey)) {
                            // foreach ($foreignKey as $index => $key) {
                            //     $conditions[$assocTable->aliasField($key)] = $ids[$bindingKey[$index]];
                            // }
                        } else {
                            $conditions[$assocTable->aliasField($foreignKey)] = $id;
                        }

                        $query = $assocTable->find()->where($conditions);
                        $count = $query->count();
                        $title = $assoc->name();

                        $isAssociated = true;
                        // if ($extra->offsetExists('excludedModels')) {
                        //     if (in_array($title, $extra['excludedModels'])) {
                        //         $isAssociated = false;
                        //     }
                        // }
                        if ($isAssociated) {
                            $cells[$assoc->alias()] = [$title, $count];
                        }
                    }
                }
            }

            $displayTypes = ['string', 'integer', 'text', 'date'];
            $elements = $page->getElements();
            foreach ($elements as $element) {
                $type = $element->getControlType();
                if (in_array($type, $displayTypes)) {
                    $element->setDisabled(true);
                } else {
                    $element->setVisible(false);
                }
            }
            $page->addNew('associated_records')
                ->setControlType('table')
                ->set('headers', [__('Feature'), __('No of records')])
                ->set('cells', $cells)
                ;

            $page->setVar('data', $entity);
        }
    }

    public function onchange($table, $finder, $value)
    {
        $results = $this->{$table}->find($finder, ['value' => $value])->toArray();
        $options = [];

        foreach ($results as $value => $text) {
            $options[] = ['value' => $value, 'text' => $text];
        }
        $this->response->body(json_encode($options, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');

        return $this->response;
    }
}
