<?php
namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;
use Cake\Core\Configure;

class ConfigItemsBehavior extends Behavior
{
    private $model;
    private $selectedType;

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = ['callable' => 'indexBeforeAction'];
        $events['Model.custom.onUpdateToolbarButtons'] =  ['callable' => 'onUpdateToolbarButtons'];//POCOR-8751
        if ($this->isCAv4()) {
            $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction'];
        }
        return $events;
    }

    public function initialize(array $config): void
    {
        $this->model = $this->_table;
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function buildSystemConfigFilters()
    {
        $toolbarElements = [
            ['name' => 'Configuration.controls', 'data' => [], 'options' => []]
        ];
        $this->model->controller->set('toolbarElements', $toolbarElements);
        $ConfigItem = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');


        $typeList = $ConfigItem
            ->find('list', [
                'keyField' => 'type',
                'valueField' => 'type'
            ])
            ->order('type')
            ->where([$ConfigItem->aliasField('visible') => 1])
            ->toArray();

        $typeOptions = array_keys($typeList);
        foreach ($typeOptions as $key => $value) {
            $value = $value != 'Authentication' ? $value : 'Sso';
            if (in_array($value, (array) Configure::read('School.excludedPlugins'))) {
                unset($typeOptions[$key]);
            }
            if ($value === 'Webhooks') { //POCOR-9257: Webhooks has its own dedicated page under System Setup
                unset($typeOptions[$key]);
            }
        }
        //POCOR-8883 code logic change start
        $selectedType = $this->model->queryString('type', $typeOptions);
        $this->selectedType = $selectedType;
        $typeValue = $typeOptions[$selectedType];
        if(empty($typeValue)){
           $typeValue = $this->model->request->getQueryParams()['type'];
        }

        //POCOR-8951 start
        if($typeValue == 'Themes'){
            $productThemes = $ConfigItem
                ->find('list', [
                    'keyField' => 'code',
                    'valueField' => 'name'
                ])
                ->order('type')
                ->where([$ConfigItem->aliasField('type') => 'Online Services'])
                ->toArray();
            $this->model->request = $this->model->request->withQueryParams(
                array_merge($this->model->request->getQueryParams(),
                ['type_value' => $typeValue]));
            $this->model->advancedSelectOptions($typeOptions, $selectedType);
            $this->model->controller->set('typeOptions', $typeOptions);
            $controlElement = $toolbarElements[0];
            $selectedProduct = $this->model->request->getQueryParams()['online_service'] ?? 'openemis_core';
            $controlElement['data'] = [
                'typeOptions' => $typeOptions,
                'productThemes' => $productThemes,
                'selectedProduct' => $selectedProduct];
            $controlElement['order'] = 1;
            return $controlElement;
            //POCOR-8951 end
        }elseif($typeValue !== 'Custom Validation'){
            //POCOR-9257: merge type_value into existing params instead of replacing, so other filter params (status, method, etc.) survive
            $queryParams = $this->model->request->getQueryParams();
            $queryParams['type_value'] = $typeValue;
            $this->model->request = $this->model->request->withQueryParams($queryParams);
            $this->model->advancedSelectOptions($typeOptions, $selectedType);
            $this->model->controller->set('typeOptions', $typeOptions);
            $controlElement = $toolbarElements[0];
            $controlElement['data'] = ['typeOptions' => $typeOptions];
            $controlElement['order'] = 1;
            return $controlElement;
        }else{
            if ($typeValue !== null) {
                $queryParams = $this->model->request->getQueryParams();
                $queryParams['type'] = $selectedType;
                $queryParams['type_value'] = $typeValue;
                $this->model->request = $this->model->request->withQueryParams($queryParams);
            }
            $this->model->advancedSelectOptions($typeOptions, $selectedType);
            $this->model->controller->set('typeOptions', $typeOptions);
            $controlElement = $toolbarElements[0];
            $controlElement['data'] = ['typeOptions' => $typeOptions];
            $controlElement['order'] = 1;
            return $controlElement;
            //POCOR-8883 end
        }
    }

    public function checkController()
    {
        $typeValue = $this->model->request->getQuery('type_value');

        $typeValue = Inflector::camelize($typeValue, ' ');
        $action = '';
        if ($this->isCAv4()) {
            $url = $this->model->url('index');
            $action = $this->model->request->getParam('action');
        } else {
            $url = $this->model->controller->ControllerAction->url('index');
            $action = $this->model->action;
        }

        // Start POCOR-7507
        if($typeValue == 'ExternalDataSource-Identity'){
            $typeValue = 'ExternalDataSourceIdentity';
        }
        // POCOR-9403
        if($typeValue == 'ExternalDataSource-Webhook'){
            $typeValue = 'ExternalDataSourceWebhook';
        }
        //POCOR-7531 start
        if($typeValue == 'ExternalDataSource-Exams'){
            $typeValue = 'ExternalDataSourceExams';
        }
        if($typeValue == 'ExternalDataSource-LMS'){ //POCOR-8386
            $typeValue = 'ExternalDataSourceLMS';
        }

        if($typeValue == 'AutoGeneratedCandidateNumber'){ //POCOR-9164
            $typeValue = 'AutoGeneratedCandidateNumber';
        }
        if($typeValue == 'ExternalAlertService-SMS'){ //POCOR-8386
            $typeValue = 'ExternalAlertServiceSMS';
        }

//        if($typeValue == 'PDFService'){ //POCOR-9303
//            $typeValue = 'PrintingServicePDF';
//        }
        //POCOR-7531 start
         // End POCOR-7507

        // Start POCOR-8689
        if($typeValue == 'DefaultInstitutionsForAutomatedStudentEnrolme'){
            $typeValue = 'AutomatedStudentEnrollment';
        }
        // Start POCOR-8689
        if (method_exists($this->model->controller, $typeValue) && $action != $typeValue) {

            $url['action'] = $typeValue;
            $url['type_value'] = $typeValue;  // POCOR-7507
            $this->model->controller->redirect($url);
        } elseif ($action != $typeValue && $action != 'index') {
            $this->model->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'index',
                '?' => ['type' => $this->selectedType] //POCOR-8883
            ]);
        }
    }

    public function beforeAction(EventInterface $event, $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Configurations', 'action' => 'index'];
    }

    public function indexBeforeAction(EventInterface $event, $extra)
    {
        if ($this->isCAv4()) {
            $extra['elements']['controls'] = $this->buildSystemConfigFilters();
            $this->checkController();
        } else {
            $this->buildSystemConfigFilters();
            $this->checkController();
        }
    }
    //POCOR-8751 start
    /**
     * Handles updating the toolbar buttons during the action.
     *
     * @param EventInterface $event The event triggered during the action.
     * @param ArrayObject $buttons The existing buttons for the action.
     * @param ArrayObject $toolbarButtons The toolbar buttons that will be modified.
     * @param array $attr Additional attributes or options for the action.
     * @param string $action The action being performed (e.g., 'view', 'edit').
     * @param bool $isFromModel Flag indicating if the action is originating from a model.
     */

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($this->_table->action == 'view') {
            $session = $this->_table->request->getSession();
            $key = $session->read('Configuration.ConfigItems.primaryKey.id');
            $entity =  TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->get($key);
            if($entity->code=="edition" && $entity->type=="System"){
                if (isset($toolbarButtons['edit'])) {
                    unset($toolbarButtons['edit']);
                }
            }
        }

    }
     //POCOR-8751 end

}

