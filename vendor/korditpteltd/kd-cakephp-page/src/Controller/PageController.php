<?php
namespace Page\Controller;

use ArrayObject;
use Exception;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Network\Response;
use Cake\Network\Exception\NotFoundException;
use Cake\Controller\Exception\MissingActionException;
use Cake\Utility\Inflector;
use Cake\Log\Log;

use Page\Model\Entity\PageStatus;
use Page\Model\Entity\PageElement;
use App\Controller\AppController;
use App\Model\Table\AppTable;

class PageController extends AppController
{
    private $excludedFields = ['order', 'modified', 'modified_user_id', 'created', 'created_user_id'];

    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('Page.Page');
        $this->loadComponent('Page.Alert');
        $this->loadComponent('Paginator');
        $this->loadComponent('RequestHandler');

        $this->Auth->allow(['onchange']);

        if ($this->{$this->name} instanceof AppTable) {
            $this->Page->loadElementsFromTable($this->{$this->name});
        }
    }

    public function beforeFilter(Event $event)
    {
        $action = $this->request->action;
        if (in_array($action, ['index', 'add', 'edit', 'delete'])) {
            $this->Page->exclude($this->excludedFields);
        }
        if (!$this->Page->isActionAllowed($action) && !in_array($action, ['onchange', 'reorder'])) {
            $this->Page->throwMissingActionException();
        }
    }

    public function index()
    {
        $page = $this->Page;
        $request = $this->request;
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

            $page->autoConditions($table); // add where conditions if field exists in querystring
            $page->autoContains($table); // auto contain all belongsTo association

            $queryOptions = $page->getQueryOptions();

            // Remove all default ordering if sort key exists in querystring
            if (array_key_exists('sort', $requestQueries) && $queryOptions->offsetExists('order')) {
                $queryOptions->offsetUnset('order');
            }
            $query = $table->find('all', $queryOptions->getArrayCopy());

            if ($table->hasFinder('Index')) {
                $query->find('index');
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
            if (count($data) == 0) {
                $page->setAlert('There are no records.', 'info');
            }
            $page->setVar('data', $data);
        }
    }

    public function add()
    {
        $page = $this->Page;
        $request = $this->request;

        if ($page->hasMainTable()) {
            $table = $page->getMainTable();
            $response = null;
            $entity = null;

            if ($request->is('get')) {
                $entity = $table->newEntity();
            } elseif ($request->is(['post'])) {
                $pageStatus = $page->getStatus();

                try {
                    $queryOptions = $page->getQueryOptions();
                    $patchOption = $queryOptions['user'];
                    $entity = $table->newEntity($request->data, $patchOption);
                    $result = $table->save($entity, $patchOption);

                    if ($page->isDebugMode()) {
                        pr($request->data);
                    }
                    if ($result) {
                        $pageStatus->setMessage('The record has been added successfully.');
                        $page->setAlert($pageStatus->getMessage());
                        $response = $page->redirect(['action' => 'index']);
                    } else {
                        Log::write('debug', $entity->errors());

                        if ($entity->errors()) {
                            $page->setVar('error', $entity->errors());
                        }

                        $pageStatus->setCode(PageStatus::VALIDATION_ERROR)
                            ->setType('error')
                            ->setMessage('The record is not added due to errors encountered.');

                        $page->setAlert($pageStatus->getMessage(), 'error');
                    }
                } catch (Exception $ex) {
                    Log::write('error', $ex);
                    $msg = $ex->getMessage();
                    $pageStatus->setCode(PageStatus::UNEXPECTED_ERROR)
                        ->setType('error')
                        ->setError(true)
                        ->setMessage($msg);

                    $page->setAlert($pageStatus->getMessage(), 'error');
                }
            }
            $page->attachPrimaryKey($table, $entity);
            $page->setVar('data', $entity);

            if (!is_null($response)) {
                return $response;
            }
        }
    }

    public function view($id)
    {
        $page = $this->Page;
        $request = $this->request;

        if ($request->is(['get', 'ajax']) && $page->hasMainTable()) {
            $primaryKeyValue = $page->decode($id);
            $table = $page->getMainTable();
            $primaryKey = $table->primaryKey();
            if (!is_array($primaryKey)) { // if primary key is not composite key, then hide from index page
                $page->exclude($primaryKey);
            }

            if ($table->exists($primaryKeyValue)) {
                $page->autoContains($table);
                $queryOptions = $page->getQueryOptions();

                if ($table->hasFinder('View')) {
                    $queryOptions->offsetSet('finder', 'View');
                }

                $entity = $table->get($primaryKeyValue, $queryOptions->getArrayCopy());
                $page->attachPrimaryKey($table, $entity);
                $page->setVar('data', $entity);
            }
        }
    }

    public function edit($id)
    {
        $page = $this->Page;
        $request = $this->request;

        if ($page->hasMainTable()) {
            $primaryKeyValue = $page->decode($id);
            $table = $page->getMainTable();
            $pageStatus = $page->getStatus();
            $response = null;
            $entity = null;

            if ($table->exists($primaryKeyValue)) {
                // autoContain and findEdit needs to be executed on POST/PUT/PATCH
                // so that on validation error, correct values will be displayed
                $page->autoContains($table);
                $queryOptions = $page->getQueryOptions();

                if ($table->hasFinder('Edit')) {
                    $queryOptions->offsetSet('finder', 'Edit');
                }
                $entity = $table->get($primaryKeyValue, $queryOptions->getArrayCopy());
                $page->attachPrimaryKey($table, $entity);

                if ($request->is(['post', 'put', 'patch'])) {
                    try {
                        $patchOption = ['user' => $queryOptions['user']];
                        $entity = $table->patchEntity($entity, $request->data, $patchOption);
                        $result = $table->save($entity, $patchOption);

                        if ($result) {
                            $pageStatus->setMessage('The record has been updated successfully.');
                            $page->setAlert($pageStatus->getMessage());
                            $response = $page->redirect(['action' => 'view']);
                        } else {
                            Log::write('debug', $entity->errors());
                            if ($entity->errors()) {
                                $page->setVar('error', $entity->errors());
                            }

                            $pageStatus->setCode(PageStatus::VALIDATION_ERROR)
                                ->setType('error')
                                ->setMessage('The record is not updated due to errors encountered.');

                            $page->setAlert($pageStatus->getMessage(), 'error');
                        }
                    } catch (Exception $ex) { // should catch more specific exceptions to handle the exception appropriately
                        Log::write('error', $ex);
                        $msg = $ex->getMessage();
                        $pageStatus->setCode(PageStatus::UNEXPECTED_ERROR)
                            ->setType('error')
                            ->setError(true)
                            ->setMessage($msg);

                        $page->setAlert($pageStatus->getMessage(), 'error');
                    }

                    $errors = $entity->errors();
                    $page->setVar('errors', $errors);
                }
                $page->setVar('data', $entity);
            } else { // if primary key does not exists
                $pageStatus->setCode(PageStatus::RECORD_NOT_FOUND)
                    ->setType('warning')
                    ->setError(true)
                    ->setMessage('The record does not exists.');

                $page->setAlert($pageStatus->getMessage(), 'warning');
                $response = $page->redirect(['action' => 'view']);
            }
            if (!is_null($response)) {
                return $response;
            }
        }
    }

    public function delete($id)
    {
        $page = $this->Page;
        $request = $this->request;
        $extra = new ArrayObject();
        $entity = null;
        $pageStatus = $page->getStatus();

        if ($page->hasMainTable()) {
            $primaryKeyValue = $page->decode($id);
            $table = $page->getMainTable();

            if (!$table->exists($primaryKeyValue)) {
                $pageStatus->setCode(PageStatus::RECORD_NOT_FOUND)
                    ->setType('warning')
                    ->setError(true)
                    ->setMessage('The record does not exists.');

                $page->setAlert($pageStatus->getMessage(), 'warning');
                $response = $page->redirect(['action' => 'index'], 'QUERY');

                return $response;
            }
            $queryOptions = $page->getQueryOptions();

            if ($request->is(['get'])) {
                $page->autoContains($table);
                if ($table->hasFinder('Delete')) {
                    $queryOptions->offsetSet('finder', 'Delete');
                }

                $entity = $table->get($primaryKeyValue, $queryOptions->getArrayCopy());
            } elseif ($request->is(['delete'])) {
                $patchOption = ['user' => $queryOptions['user']];
                $entity = $table->get($primaryKeyValue);
                $table->delete($entity, $patchOption);
                $errors = $entity->errors();
                if ($errors) {
                    $errorFieldKeys = array_keys($errors);
                    $pageStatus->setCode(PageStatus::VALIDATION_ERROR)
                        ->setError(true)
                        ->setType('error');
                    if (in_array('associated_records', $errorFieldKeys)) {
                        $pageStatus->setMessage($entity->errors('associated_records')['restrictDelete']);
                    } else {
                        $pageStatus->setMessage('The record is not deleted due to errors encountered.');
                    }
                    $page->setVar('errors', $errors);
                    $page->setAlert($pageStatus->getMessage(), 'error');
                } else {
                    $pageStatus->setMessage('The record has been deleted successfully.');
                    $page->setAlert($pageStatus->getMessage());
                    $response = $page->redirect(['action' => 'index'], 'QUERY');
                    $page->setVar('data', $entity);
                    return $response;
                }
            }

            $page->attachPrimaryKey($table, $entity);

            $msg = __('All associated information related to this record will also be removed. Are you sure you want to delete this record?');
            $page->setAlert($msg, 'warning');
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
                            $conditions[$assocTable->aliasField($foreignKey)] = $primaryKeyValue['id'];
                        }

                        $query = $assocTable->find()->where($conditions);
                        $count = $query->count();
                        $title = Inflector::humanize(Inflector::underscore($assoc->name()));

                        $isAssociated = true;
                        // if ($extra->offsetExists('excludedModels')) {
                        //     if (in_array($title, $extra['excludedModels'])) {
                        //         $isAssociated = false;
                        //     }
                        // }
                        if ($isAssociated) {
                            $cells[] = ['feature' => $title, 'record' => $count];
                        }
                    }
                }
            }

            $displayTypes = ['string', 'integer', 'text', 'date', 'time', 'decimal', 'textarea'];
            $elements = $page->getElements();
            foreach ($elements as $element) {
                $type = $element->getControlType();
                if ($type == 'select') {
                    $element->setControlType('string');
                    $type = 'string';
                }
                if (in_array($type, $displayTypes)) {
                    $element->setDisabled(true);
                } else {
                    $element->setVisible(false);
                }
            }

            if (!empty($cells)) {
                $page->addNew('associated_records')
                    ->setControlType('table')
                    ->addColumn('feature', ['label' => __('Features')])
                    ->addColumn('record', ['label' => __('Records')])
                    ->addRows($cells);
            }

            $page->setVar('data', $entity);
        }
    }

    public function download($id, $fileColumn)
    {
        $page = $this->Page;
        $request = $this->request;

        if ($page->hasMainTable()) {
            $table = $page->getMainTable();
            $primaryKeyValue = $page->decode($id);
            if ($table->exists($primaryKeyValue) && $table->hasBehavior('FileUpload')) {
                $entity = $table->get($primaryKeyValue);
                $fileName = $entity->$fileColumn;
                $binaryColumn = $table->getBinaryColumn($fileColumn);
                $content = $entity->$binaryColumn;

                $response = $this->response;
                $response->body(function () use ($fileName, $content) {
                    $file = '';
                    while (!feof($content)) {
                        $file .= fread($content, 8192);
                    }
                    fclose($content);

                    return $file;
                });

                $pathInfo = pathinfo($fileName);
                $response->type($pathInfo['extension']);
                $response->download($fileName);

                return $response;
            }
        } else {
            // need error handling
        }
    }

    public function reorder()
    {
        $page = $this->Page;
        $request = $this->request;
        $this->autoRender = false;

        if ($page->hasMainTable()) {
            $table = $page->getMainTable();

            if ($request->is('ajax')) {
                $primaryKey = $table->primaryKey();
                $orderField = $page->config('sequence');

                $encodedIds = json_decode($request->data("ids"));

                $ids = [];
                $idKeys = [];
                foreach ($encodedIds as $id) {
                    $ids[] = $page->decode($id);
                    $idKeys[] = $table->getIdKeys($table, $page->decode($id));
                }

                if (!empty($ids)) {
                    $originalOrder = $table
                        ->find()
                        ->select($primaryKey)
                        ->select($orderField)
                        ->where(['OR' => $idKeys])
                        ->order([$table->aliasField($orderField)])
                        ->hydrate(false)
                        ->toArray();

                    $originalOrder = array_reverse($originalOrder);
                    foreach ($ids as $id) {
                        $orderValue = array_pop($originalOrder);
                        $table->updateAll([$orderField => $orderValue[$orderField]], [$id]);
                    }
                }
            }
        }
    }

    public function onchange($type, $model, $finder = 'OptionList')
    {
        $request = $this->request;
        $page = $this->Page;

        $options = $page->getFilterOptions(implode('/', [$model, $finder]));

        $response = [
            'type' => $type,
            'data' => $options
        ];

        // Added pretty print option. Will change this to serialise to make use of cakephp rendering function
        $this->response->body(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->response->type('json');

        return $this->response;
    }
}
