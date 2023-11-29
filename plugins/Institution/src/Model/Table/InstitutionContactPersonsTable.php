<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Network\Response;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry; // POCOR-5188
use App\Model\Traits\OptionsTrait;

class InstitutionContactPersonsTable extends ControllerActionTable {

    public function initialize(array $config): void
    {

        parent::initialize($config);
	
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->addBehavior('ContactExcel', [ //POCOR-6889
            'pages' => ['index']
        ]);
    }

    /*public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('telephone')
            ->add('telephone', 'ruleCustomTelephone', [
                'rule' => ['validateCustomPattern', 'institution_contact_person_telephone'],
                'provider' => 'table'
            ])
            ->allowEmpty('mobile_number')
            ->add('mobile_number', 'ruleCustomMobile', [
                'rule' => ['validateCustomPattern', 'institution_contact_person_mobile'],
                'provider' => 'table'
            ])
            ->allowEmpty('fax')
            ->add('fax', 'ruleCustomFax', [
                'rule' => ['validateCustomPattern', 'institution_contact_person_fax'],
                'provider' => 'table'
            ])
            ->allowEmpty('email')
            ->add('email', [
                'ruleValidEmail' => [
                    'rule' => 'email'
                ]
            ]);
           // ->requirePresence('preferred'); //comment cakephp4
    }*/

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //comment cakephp 4
        /*if ($entity->dirty('preferred')) {
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
        }*/
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->preferred == 1) {
            $this->Institutions->updateAll(
                ['contact_person' => null],
                ['id' => $entity->institution_id]
            );
        }
    }
    //START:POCOR-6889
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
    	$institutionId = $this->Session->read('Institution.Institutions.id');
    	$query
    	->where([
            'institution_id' =>  $institutionId
        ]);
    }
    //END:POCOR-6889

    
    // Start POCOR-5188
    public function indexBeforeAction(Event $event, ArrayObject $extra) {
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('preferred', ['type' => 'select', 'after' => 'contact_person']);
    }


    public function onUpdateFieldPreferred(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view' || $action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['select'] = false;
           // $attr['options'] = $this->getSelectOptions('general.yesno');
        }

        return $attr;
    }

    public function onGetPreferred(Event $event, Entity $entity)
    {
       // $options = $this->getSelectOptions('general.yesno');
        return $options[$entity->preferred];
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        
            switch ($field) {
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