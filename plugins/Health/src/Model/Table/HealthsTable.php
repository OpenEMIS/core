<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class HealthsTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('user_healths');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        // $this->addBehavior('ClassExcel', ['excludes' => ['security_group_id'], 'pages' => ['view']]);
        
        $this->addBehavior('Health.Health');
        //$this->addBehavior('User.UserTab');
        $this->addBehavior('Institution.InstitutionTab');

        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Excel',[
            'excludes' => ['security_user_id'],
            'pages' => ['index','view'],
        ]);
    }

    public function onGetBloodType(EventInterface $event, Entity $entity)
    {
        $bloodTypeOptions = $this->getSelectOptions('Health.blood_types');
        return $bloodTypeOptions[$entity->blood_type];
    }

    public function onGetHealthInsurance(EventInterface $event, Entity $entity)
    {
        $healthInsuranceOptions = $this->getSelectOptions('general.yesno');
        return $healthInsuranceOptions[$entity->health_insurance];
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $userID = $this->getUserID();
        $institutionID = $this->getInstitutionID();
        $studentID = $this->getStudentID();
        // always redirect to view page if got record
        if ($data->count() == 1) {
            $entity = $data->first();
            $action = $this->url('view');
            if($institutionID != '' && $studentID != ''){
                $action[1] = $this->paramsEncode([
                    'id' => $entity->id,
                    'user_id' => $userID,
                    'student_id' => $studentID,
                    'institution_id' => $institutionID
                ]);
            }else{
                if($this->request->getParam('plugin') == 'Profile' && $this->request->getParam('controller') == 'Profiles' && $this->request->getParam('action') == 'Healths'){
                    $action[1] = $this->paramsEncode([
                        'id' => $entity->id,
                        'user_id' => $userID,
                        'staff_id' =>  $userID
                    ]);
                }else{
                    $action[1] = $this->paramsEncode([
                        'id' => $entity->id,
                        'user_id' => $userID,
                        'staff_id' =>  $userID,
                        'institution_id' => $institutionID
                    ]);
                }
            }
            
            $event->stopPropagation();
            return $this->controller->redirect($action);
        }
        
        // Start POCOR-5188
        if($this->request->getParam('controller') == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Overview','Staff - Health');       
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
        }elseif($this->request->getParam('controller') == 'Students'){
            $is_manual_exist = $this->getManualUrl('Institutions','Overview','Students - Health');       
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

        }elseif($this->request->getParam('controller') == 'Directories'){ 
            $is_manual_exist = $this->getManualUrl('Directory','Overview','Health');       
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

        }elseif($this->request->getParam('controller') == 'Profiles'){ 
            $is_manual_exist = $this->getManualUrl('Personal','Overview','Health');       
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

        }
        // End POCOR-5188
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setupFields($entity);

        // Remove back toolbarButton from directory>health>overview (POCOR-3358)
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        unset($toolbarButtonsArray['back']);
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end POCOR-3358
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setupFields($entity);
    }

    public function onUpdateFieldBloodType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['options'] = $this->getSelectOptions('Health.blood_types');
        return $attr;
    }

    public function onUpdateFieldHealthInsurance(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('blood_type');
        $this->field('health_insurance', ['after' => 'medical_facility']);
        $this->field('file_content', ['after' => 'health_insurance','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $userID = $this->getUserID();
        $this->field('security_user_id', ['after' => 'file_content', 'attr' => ['value' => $userID], 'type' => 'hidden']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->allowEmpty('file_content');
        return $validator;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'blood_type',
            'field' => 'blood_type',
            'type'  => 'string',
            'label' => __('Blood Type')
        ];

        $extraField[] = [
            'key'   => 'doctor_name',
            'field' => 'doctor_name',
            'type'  => 'string',
            'label' => __('Doctor Name')
        ];

        $extraField[] = [
            'key'   => 'doctor_contact',
            'field' => 'doctor_contact',
            'type'  => 'string',
            'label' => __('Doctor Contact')
        ];

        $extraField[] = [
            'key'   => 'medical_facility',
            'field' => 'medical_facility',
            'type'  => 'string',
            'label' => __('Medical Facility')
        ];

        $extraField[] = [
            'key'   => 'health_insurance_new',
            'field' => 'health_insurance_new',
            'type'  => 'integer',
            'label' => __('Health Insurance')
        ];

        $extraField[] = [
            'key'   => 'file_name',
            'field' => 'file_name',
            'type'  => 'string',
            'label' => __('File Name')
        ];

        $fields->exchangeArray($extraField);
    }

    //POCOR-6131
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query){
        $userID = $this->getUserID();

        $query
        ->select([
            'health_insurance_new' => "(CASE WHEN health_insurance = 1 THEN 'Yes'
            ELSE 'No' END)"
        ])
        ->where([
            // $this->aliasField('security_user_id = ').$staffUserId
            $this->aliasField('security_user_id') => $userID
        ]);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'blood_type') {
            return __('Blood Type');
        } elseif ($field == 'doctor_name') {
            return __('Doctor Name');
        }elseif ($field == 'doctor_contact') {
            return __('Doctor Contact');
        } elseif ($field == 'medical_facility') {
            return __('Medical Facility');
        } elseif ($field == 'health_insurance') {
            return __('Health Insurance');
        } elseif ($field == 'file_content') {
            return __('Attachment');
        } elseif ($field == 'modified') {
            return __('Modified On');
        }elseif ($field == 'created_user_id') {
            return __('Modified By');
        } elseif ($field == 'created') {
            return __('Created On');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-8293
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra) {
        $userId = $this->getUserID();
        $query->where([ $this->aliasField('security_user_id') => $userId]);
        return $query;
    }
}
