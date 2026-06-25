<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Network\Response;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry; // POCOR-5188
use App\Model\Traits\OptionsTrait;

class InstitutionContactPersonsTable extends ControllerActionTable {
    use OptionsTrait;
    public function initialize(array $config): void
    {

        parent::initialize($config);
	
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        // $this->addBehavior('ContactExcel', [ //POCOR-6889
        //     'pages' => ['index']
        // ]);
        $this->addBehavior('Excel', ['pages' => ['index']]);
        $this->addBehavior('Institution.InstitutionTab');       
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);

        return $validator
            ->allowEmpty('telephone')
            ->add('telephone', 'ruleCustomTelephone', [
                'rule' => ['validateCustomPattern', 'institution_contact_person_telephone'],
                'provider' => 'table'
            ])
            ->add('telephone', 'ruleContactNumberPattern', [
                'rule' => ['validateContactNumberPattern', 'validate_contact_person_telephone'],
                'provider' => 'table',
                'last' => true
            ])
            ->allowEmptyString('mobile_number') //POCOR-9543
            ->add('mobile_number', 'ruleCustomMobile', [
                'rule' => ['validateCustomPattern', 'institution_contact_person_mobile'],
                'provider' => 'table'
            ])
            ->add('mobile_number', 'ruleMobileNumberPattern', [
                'rule' => ['validateMobileNumberPattern', 'validate_contact_person_mobile_number'],
                'provider' => 'table'
            ])
            /*->allowEmpty('fax')
            ->add('fax', 'ruleCustomFax', [
                'rule' => ['validateCustomPattern', 'institution_contact_person_fax'],
                'provider' => 'table'
            ])*/
            ->notEmpty('contact_person') //POCOR-9543
            ->allowEmptyString('email') //POCOR-9543
            ->add('email', [
                'ruleValidEmail' => [
                    'rule' => 'email',
                    'message' => 'Invalid email address'
                ]
            ])
            ->requirePresence('preferred');
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->getDirty('preferred')) {
            $institutionId = $entity->institution_id;

            if ($entity->preferred == 1) {
                $this->updateAll(
                    ['preferred' => 0],
                    [
                        'institution_id' => $institutionId,
                        'id <> ' => $entity->id
                    ]
                 );

                $this->Institutions->updateAll(
                    ['contact_person' => $entity->contact_person],
                    ['id' => $institutionId]
                );
            } else {
                $results = $this->find()
                    ->where([
                        'institution_id' => $institutionId,
                        'preferred' => 1
                    ])
                    ->all();

                if ($results->isEmpty()) {
                    $this->Institutions->updateAll(
                        ['contact_person' => null],
                        ['id' => $institutionId]
                    );
                }
            }
        }
    }

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->preferred == 1) {
            $this->Institutions->updateAll(
                ['contact_person' => null],
                ['id' => $entity->institution_id]
            );
        }
    }
    //START:POCOR-6889
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
    	$institutionId = $this->getInstitutionID();
    	$query
    	->where([
            'institution_id' =>  $institutionId
        ]);
    }
    //END:POCOR-6889

    
    // Start POCOR-5188
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra) {
        $is_manual_exist = $this->getManualUrl('Institutions','Contacts - People','General');       
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

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('preferred', ['type' => 'select', 'after' => 'contact_person']);
    }


    public function onUpdateFieldPreferred(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
//        $functionName = __FUNCTION__;
//        $this->log($functionName, 'debug');
        if ($action == 'view' || $action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->getSelectOptions('general.yesno');
        }

        return $attr;
    }

    public function onGetPreferred(EventInterface $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno');
        return $options[$entity->preferred];
    }

    // End POCOR-5188

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'contact_person':
                return __('Contact Person');
            case 'preferred':
                return __('Preferred');
            case 'designation':
                return __('Designation');
            case 'department':
                return __('Department');
            case 'telephone':
                return __('Telephone');
            case 'mobile_number':
                return __('Mobile Number');
            case 'email':
                return __('Email');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'modified_user_id':
                return __('Modified By');
            case 'modified':
                return __('Modified');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    
}