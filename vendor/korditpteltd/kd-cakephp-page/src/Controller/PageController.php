<?php
namespace Page\Controller;

use ArrayObject;
use Exception;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
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

            $page->autoConditions($table); // add where conditions if field exists in querystring
            $page->autoContains($table); // auto contain all belongsTo association

            $queryOptions = $page->getQueryOptions();
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

                    if ($page->isDebugMode()) {
                        pr($request->data);
                    }
                    if ($result) {
                        $pageStatus->setMessage('The record has been added successfully');

                        return;
                    } else {
                        Log::write('debug', $entity->errors());
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
        if (!$page->isActionAllowed(__FUNCTION__)) {
            $page->throwMissingActionException();
        }
        $extra = new ArrayObject();

        if ($page->hasMainTable()) {
            $primaryKeyValue = json_decode($page->hexToStr($id), true);
            $table = $page->getMainTable();
            if ($table->exists($primaryKeyValue)) {
                $page->autoContains($table);
                $queryOptions = $page->getQueryOptions();

                if ($table->hasFinder('Edit')) {
                    $queryOptions->offsetSet('finder', 'Edit');
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

            $page->autoContains($table);
            $queryOptions = $page->getQueryOptions();

            if ($table->hasFinder('Delete')) {
                $queryOptions->offsetSet('finder', 'Delete');
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
                            $cells[] = [$title, $count];
                        }
                    }
                }
            }

            $displayTypes = ['string', 'integer', 'text', 'date', 'decimal', 'textarea'];
            $elements = $page->getElements();
            foreach ($elements as $element) {
                $type = $element->getControlType();
                if (in_array($type, $displayTypes)) {
                    $element->setDisabled(true);
                } else {
                    $element->setVisible(false);
                }
            }

            if (!empty($cells)) {
                $page->addNew('associated_records')
                    ->setControlType('table')
                    ->set('headers', [__('Feature'), __('No of records')])
                    ->set('cells', $cells)
                    ;
            }

            $page->setVar('data', $entity);
        }
    }

    public function onchange($model, $finder = 'OptionList')
    {
        $request = $this->request;
        $table = $this->{$model};

        if ($table === false) {
            $table = TableRegistry::get($model);
        }

        $options = [];
        $finderOptions = [];
        $conditions = [];
        $isReset = false;

        $requestQueries = $this->request->query;
        $columns = $table->schema()->columns();
        foreach ($requestQueries as $key => $value) {
            if (in_array($key, $columns)) { // $key exists as a table column, automatically add as a condition
                $conditions[$key] = $value;
            } elseif ($key == 'multiple') { // if multiple flag is set, then turn off default option
                $finderOptions['defaultOption'] = false;
            } elseif ($key == 'reset') { // default selection has been chosen
                $isReset = true;
                break;
            } elseif ($key == 'querystring') { // if querystring exists, decode the value
                $finderOptions['querystring'] = $this->Page->decode($value);
            } else { // any other values will be included in the finder options
                $finderOptions[$key] = $value;
            }
        }

        if (!$isReset) {
            $finderOptions['conditions'] = $conditions;
            $finderOptions['limit'] = 1000; // maximum number of options set to 1000 to prevent out of memory
            if ($table->hasFinder($finder)) {
                $options = $table->find($finder, $finderOptions)->toArray();
            }
        } else {
            $options[] = ['value' => '', 'text' => __('No Options')];
        }

        $this->response->body(json_encode($options, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');

        return $this->response;
    }

    /* To be added to AppTable for onchange to work
    public function findOptionList(Query $query, array $options)
    {
        $options += [
            'keyField' => $this->primaryKey(),
            'valueField' => $this->displayField(),
            'groupField' => null
        ];

        if (!$query->clause('select') &&
            !is_object($options['keyField']) &&
            !is_object($options['valueField']) &&
            !is_object($options['groupField'])
        ) {
            $fields = array_merge(
                (array)$options['keyField'],
                (array)$options['valueField'],
                (array)$options['groupField']
            );
            $columns = $this->schema()->columns();
            if (count($fields) === count(array_intersect($fields, $columns))) {
                $query->select($fields);
            }
        }

        $options = $this->_setFieldMatchers(
            $options,
            ['keyField', 'valueField', 'groupField']
        );

        return $query->formatResults(function ($results) use ($options) {
            $returnResult = [];
            $groupField = $options['groupField'];
            $keyField = $options['keyField'];
            $valueField = $options['valueField'];
            if (array_key_exists('defaultOption', $options) && !$options['defaultOption']) {
                $returnResult = [];
            } else if ($results->count() == 0) {
                $returnResult[] = ['value' => '', 'text' => __('No Options')];
            } else if (array_key_exists('defaultOption', $options) && is_string($options['defaultOption'])) {
                $returnResult[] = ['value' => '', 'text' => __($options['defaultOption'])];
            } else {
                $returnResult[] = ['value' => '', 'text' => '-- '.__('Select').' --'];
            }
            foreach ($results as $result) {
                $result = $result->toArray();

                if (array_key_exists('flatten', $options) && $options['flatten']) {
                    $result = Hash::flatten($result);
                }
                $key = array_key_exists($keyField, $result) ? $result[$keyField] : null;
                $value = array_key_exists($valueField, $result) ? $result[$valueField] : null;
                if ($options['groupField']) {
                    $group = array_key_exists($groupField, $result) ? $result[$groupField] : null;
                    $returnResult[] = ['group' => $group, 'value' => $key, 'text' => $value];
                } else {
                    $returnResult[] = ['value' => $key, 'text' => $value];
                }
            }
            return $returnResult;
        });
    }
    */
}
