<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class ImmunizationsTable extends ControllerActionTable
{
    use HealthLookupTrait; //POCOR-9718

    public function initialize(array $config): void
    {
        $this->setTable('user_health_immunizations');
        parent::initialize($config);

        $this->belongsTo('ImmunizationTypes', ['className' => 'Health.ImmunizationTypes', 'foreignKey' => 'health_immunization_type_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['HealthImmunizations' =>
                ['health_immunization_type_id']
            ]
        ]);
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        $this->addBehavior('Excel',[
            'excludes' => [],
            'pages' => ['index'],
        ]);
    }

    //POCOR-5890 starts remain work
    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('health_immunization_type_id', ['attr'=>['label'=>'Vaccination Type'], 'type' => 'select', 'before' => 'comment']);
        $this->field('dosage',['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $userID = $this->getUserID();
        $this->field('security_user_id', ['after' => 'file_content', 'attr' => ['value' => $userID], 'type' => 'hidden']);
    }

    public function indexAfterAction(EventInterface $event, $data)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('health_immunization_type_id', ['attr'=>['label'=>'Vaccination Type'], 'before' => 'comment']);
        $this->field('dosage',['visible' => false]);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'health_immunization_type_id':
                return __('Vaccination Type');
            case 'file_content':
                return __('Attachment');
            case 'date':
                return __('Date');
            case 'comment':
                return __('Comment');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {   
        $this->field('dosage', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function viewBeforeAction(EventInterface $event)
    {
        $this->field('health_immunization_type_id', ['attr'=>['label'=>'Vaccination Type'], 'before' => 'comment']);
        $this->field('dosage', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }
    //POCOR-5890 ends

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->allowEmpty('file_content');
        return $validator;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'date',
            'field' => 'date',
            'type'  => 'date',
            'label' => __('Date')
        ];

        $extraField[] = [
            'key'   => 'health_immunization_type_id',
            'field' => 'health_immunization_type_id',
            'type'  => 'string',
            'label' => __('Vaccination Type')
        ];

        $extraField[] = [
            'key'   => 'comment',
            'field' => 'comment',
            'type'  => 'string',
            'label' => __('Comment')
        ];

        $extraField[] = [
            'key'   => 'file_name',
            'field' => 'file_name',
            'type'  => 'string',
            'label' => __('File Name')
        ];

        $fields->exchangeArray($extraField);
    }

     // POCOR-6131
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query){
        // $staffUserId = $session->read('Institution.StaffUser.primaryKey.id');
        //POCOR-9584: use getUserID() (reads user_id from encoded query string) like all other Health tables;
        //            the previous session-based read returned null in Staff context causing CakePHP 5
        //            InvalidArgumentException: Expression missing IS/IS NOT operator with null value
        $userId = $this->getUserID();

        $query
        ->where([
            $this->aliasField('security_user_id') => $userId
        ]);
    }

    // Start POCOR-5188
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
		if($this->request->getParam('controller') == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Vaccinations','Staff - Health');       
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
            $is_manual_exist = $this->getManualUrl('Institutions','Vaccinations','Students - Health');       
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
            $is_manual_exist = $this->getManualUrl('Directory','Vaccinations','Health');       
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
            $is_manual_exist = $this->getManualUrl('Personal','Vaccinations','Health');       
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
    }
    // End POCOR-5188

    //POCOR-8293
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra) {
        $userId = $this->getUserID();
        $query->where([ $this->aliasField('security_user_id') => $userId]);
        return $query;
    }

    //POCOR-9718: populate health_immunization_type_id select from Health.ImmunizationTypes.
    public function onUpdateFieldHealthImmunizationTypeId(EventInterface $event, array $attr, $action)
    {
        return $this->populateLookupSelect($attr, $action, 'Health.ImmunizationTypes');
    }
}
