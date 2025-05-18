<?php
namespace ProfileTemplate\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use App\Model\Table\ControllerActionTable;
use Cake\Http\ServerRequest;

class StudentTemplatesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('student_profile_templates');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('User.AdvancedNameSearch');
        
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'excel_template_name',
            'content' => 'excel_template',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'document',
            'useDefaultName' => true
        ]);
        $this->behaviors()->get('Download')->setConfig(
            'name',
            'excel_template_name'
        );
        $this->behaviors()->get('Download')->setConfig(
            'content',
            'excel_template'
        );
        $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            true
        );
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['view']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadTemplate'] = 'downloadTemplate';
        return $events;
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                'provider' => 'table'
            ])
            ->add('generate_start_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            ])
            ->add('generate_end_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'generate_start_date', false]
                ]
            ])
            ->allowEmptyFile('excel_template');
    }

    public function validationSubjects(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator = $validator->requirePresence('subjects');
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['excel_template_name']['visible'] = false;
        $this->field('generate_start_date', ['type' => 'date']);
        $this->field('generate_end_date', ['type' => 'date']);
        $this->field('excel_template');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['description']['visible'] = false;
        $this->setFieldOrder(['code', 'name', 'generate_start_date', 'generate_end_date', 'excel_template']);
        $this->setupTabElements();

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Personal','Generate Students Profile','Profiles');       
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Academic Period filter
        $serverRequest = $this->request;
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($serverRequest->getQuery('academic_period_id')) ? $serverRequest->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End

        $extra['elements']['controls'] = ['name' => 'ProfileTemplate.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where($where);
    }

    private function setupFields($entity)
    {
        $this->field('code');
        $this->field('name');
        $this->field('description');
        $this->field('academic_period_id', ['entity' => $entity]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function() use ($entity) {
            $filename = $entity->excel_template;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->getConfig(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupFields($entity);
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'generate_start_date', 'generate_end_date', 'excel_template']);
    }

    public function onGetExcelTemplate(Event $event, Entity $entity)
    {
        if ($entity->has('excel_template_name')) {
            return $entity->excel_template_name;
        }
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        //POCOR-5191 :: Strat
        $Roles = TableRegistry::get('Security.SecurityRoles');  
        $roles = $Roles->find('list',['keyField' => 'id', 'valueField' => 'name'])->toArray();  
        $this->field('student_profile_template_id', [   
            'type' => 'chosenSelect',   
            'attr' => [ 
                'label' => __('Security Roles') 
            ]   
        ]); 
        $this->fields['student_profile_template_id']['options'] = $roles;
        //POCOR-5191 :: End
        // to set template download button
        $downloadUrl = $this->url('downloadTemplate');
        $this->controller->set('downloadOnClick', "javascript:window.location.href='". Router::url($downloadUrl) ."'");
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'student_profile_template_id','generate_start_date', 'generate_end_date', 'excel_template']);
    }

    //POCOR-5191 :: Strat
    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $ProfileSecurityRoles = TableRegistry::get('Student.StudentProfileSecurityRoles');  
                $ProfileSecurityRolesData = $ProfileSecurityRoles->find()->where(['student_profile_template_id'=>$row->id])->toArray();
               
                $arr =[];
                foreach($ProfileSecurityRolesData as $k =>$data1){
                    $arr[$k] = ['id'=>$data1->security_role_id];
                }
                $row['student_profile_template_id'] = $arr;
                return $row;
            });
        });
    }
    
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)   
    {   
        $ProfileSecurityRoles = TableRegistry::get('Student.StudentProfileSecurityRoles');  
        //Delete all Records for this student_profile_template
        $AlreadyRecord = $ProfileSecurityRoles->find('all',['conditions'=>['student_profile_template_id' => $entity->id]])->toArray();
        foreach($AlreadyRecord as $k=> $del){
            $ProfileSecurityRoles->delete($del);
        }
        if(!empty($entity['student_profile_template_id']['_ids'])){ 
            foreach($entity['student_profile_template_id']['_ids'] as $profile){    
                $ProfileSecurityRolesEntity = $ProfileSecurityRoles->newEntity([
                    'security_role_id' => $profile,
                    'student_profile_template_id' => $entity->id
                ]);
                $ProfileSecurityRoles->save($ProfileSecurityRolesEntity);
            }   
        }   
    }
    //POCOR-5191 :: End

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {

    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $this->fields['code']['type'] = 'readonly';
        $this->fields['name']['type'] = 'readonly';
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'generate_start_date', 'generate_end_date', 'excel_template']);
    }

    public function onUpdateFieldExcelTemplate(Event $event, array $attr, $action, ServerRequest $request) {
        if ($action == 'index' || $action == 'view') {
            $attr['type'] = 'string';
        } elseif($action == 'edit') {
            $requestId = $this->request->getParam('pass')[1]; 
            $paramsDecode = $this->paramsDecode($requestId);
            $recordId = $paramsDecode['id']; // Added semicolon

            $record = $this->find()
                ->where([$this->aliasField('id') => $recordId])
                ->first();
            $excelName = $record ? $record->excel_template_name : null;
            $attr['startWithOneLeftButton'] = 'download';
            $attr['type'] = 'binary';
            $attr['value'] = $excelName;
            $attr['attr']['value'] = $excelName;
        }else{
            $attr['startWithOneLeftButton'] = 'download';
            $attr['type'] = 'binary';
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['type'] = 'select';
            $attr['options'] = $periodOptions;

        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->academic_period_id;
            $attr['attr']['value'] = $this->AcademicPeriods->get($attr['entity']->academic_period_id)->name;
        }
        return $attr;
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
       
    }

    public function checkIfHasTemplate($reportCardId=0)
    {
        $hasTemplate = false;

        if (!empty($reportCardId)) {
            $entity = $this->get($reportCardId);
            $hasTemplate = !empty($entity->excel_template) ? true : false;
        }

        return $hasTemplate;
    }

    public function downloadTemplate()
    {
        $filename = 'student_profile_template';
        $fileType = 'xlsx';
        $filepath = WWW_ROOT . 'export' . DS . 'customexcel'. DS . 'default_templates'. DS . $filename . '.' . $fileType;

        // header("Pragma: public", true);
        // header("Expires: 0"); // set expiration time
        // header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        // header("Content-Type: application/force-download");
        // header("Content-Type: application/octet-stream");
        // header("Content-Type: application/download");
        // header("Content-Disposition: attachment; filename=".basename($filepath));
        // header("Content-Transfer-Encoding: binary");
        // header("Content-Length: ".filesize($filepath));
        // echo file_get_contents($filepath);

        if (file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Expires: 0');

            readfile($filepath);
            exit;
        } 
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
        $generate_start_date = $this->request->getData()['StudentTemplates']['generate_start_date'];
        $generate_end_date = $this->request->getData()['StudentTemplates']['generate_end_date'];
        if (!empty($generate_start_date)) {
            $entity->generate_start_date = (new FrozenDate($generate_start_date))->format('Y-m-d H:i:s');
        }  
        if (!empty($generate_end_date)) {
            $entity->generate_end_date = (new FrozenDate($generate_end_date))->format('Y-m-d H:i:s');
        } 

    } 
    
    private function setupTabElements() {
        $options['type'] = 'StaffTemplates';
        $tabElements = $this->getStudentTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Templates');
    }

    public function getStudentTabElements($options = [])
    {
        $tabElements = [];
        $tabUrl = ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates'];
        $templateUrl = ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates'];
        $tabElements = [
            'Profiles' => ['text' => __('Profile')],
            'Templates' => ['text' => __('Templates')]
        ];
        
        $tabElements['Profiles']['url'] = array_merge($tabUrl, ['action' => 'StudentProfiles']);
        $tabElements['Templates']['url'] = array_merge($tabUrl, ['action' => 'Students']);

        return $tabElements;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return __('Name');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'description') {
            return __('Description');  
        }elseif ($field == 'academic_period_id') {
            return __('Academic Period');
        }elseif ($field == 'generate_start_date') {
            return __('Generate Start Date');
        }elseif ($field == 'generate_end_date') {
            return __('Generate End Date');
        }elseif ($field == 'excel_template') {
            return __('Excel Template');
        }elseif ($field == 'security_role_id') {
            return __('Security Roles');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateFieldGenerateStartDate(Event $event, array $attr, $action, ServerRequest $request)
    {
        
        if ($action == 'add') {
            return $this->updateDateRangeField('generate_start_date', $attr, $request);
        }
        if ($action == 'edit') {
            $queryString = $this->request->getParam('pass')[1];
            $DecodedQueryString = $this->paramsDecode($queryString);
            $id = $DecodedQueryString['id'];
            $selectDate = $this->find()->where([$this->aliasField('id') => $id])->first()->generate_start_date;
            $entity = $attr['entity'];
            $attr['value'] = (new FrozenDate($selectDate))->modify('+1 day')->format('Y-m-d');
            $attr['attr']['value'] = (new FrozenDate($selectDate))->modify('+1 day')->format('Y-m-d');
            return $attr;
            
        }
        
    }

    public function onUpdateFieldGenerateEndDate(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            return $this->updateDateRangeField('generate_end_date', $attr, $request);
        }

        if ($action == 'edit') {
            $queryString = $this->request->getParam('pass')[1];
            $DecodedQueryString = $this->paramsDecode($queryString);
            $id = $DecodedQueryString['id'];
            $selectDate = $this->find()->where([$this->aliasField('id') => $id])->first()->generate_end_date;
            $entity = $attr['entity'];
            $attr['value'] = (new FrozenDate($selectDate))->modify('+1 day')->format('Y-m-d');
            $attr['attr']['value'] = (new FrozenDate($selectDate))->modify('+1 day')->format('Y-m-d');
            return $attr;
            
        }
        
    }

    // Misc
    private function updateDateRangeField($key, $attr, ServerRequest $request)
    {
        $requestData = $request->getData();
        if (array_key_exists($this->getAlias(), $requestData) && array_key_exists('academic_period_id', $requestData[$this->getAlias()])) {
            $selectedPeriodId = $requestData[$this->getAlias()]['academic_period_id'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }

        $selectedPeriod = $this->AcademicPeriods->get($selectedPeriodId);
        $attr['type'] = 'date';
        $attr['date_options']['generateStartDate'] = $selectedPeriod->generate_start_date;
        $attr['date_options']['generateEndDate'] = $selectedPeriod->generate_end_date;
        if (!array_key_exists($this->getAlias(), $requestData) || !array_key_exists($key, $requestData[$this->getAlias()])) {
            if ($selectedPeriodId != $this->AcademicPeriods->getCurrent()) {
                $attr['value'] = $selectedPeriod->generate_start_date;
            } else {
                $attr['value'] = FrozenTime::now();
            }
        }

        return $attr;
    }

    public function onGetGenerateStartDate(Event $event, Entity $entity)
    {
        $generate_start_date = $entity->generate_start_date;
        $generate_end_date = $entity->generate_end_date;
        if (!empty($generate_start_date)) {
            $entity->generate_start_date = (new FrozenDate($generate_start_date))->modify('+1 day')->format('Y-m-d');
        }

        if (!empty($generate_end_date)) {
            $entity->generate_end_date = (new FrozenDate($generate_end_date))->modify('+1 day')->format('Y-m-d');
        }
    }

}
