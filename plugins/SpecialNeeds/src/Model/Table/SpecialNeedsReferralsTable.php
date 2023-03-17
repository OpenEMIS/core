<?php
namespace SpecialNeeds\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class SpecialNeedsReferralsTable extends ControllerActionTable
{
    const COMMENT_MAX_LENGTH = 350;
    public function initialize(array $config)
    {
        $this->table('user_special_needs_referrals');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Referrers', ['className' => 'Security.Users', 'foreignKey' => 'referrer_id']);
        $this->belongsTo('SpecialNeedsReferrerTypes', ['className' => 'SpecialNeeds.SpecialNeedsReferrerTypes']);
        $this->belongsTo('SpecialNeedsTypes', ['className' => 'SpecialNeeds.SpecialNeedsTypes', 'foreignKey' => 'reason_type_id', 'conditions' => array('SpecialNeedsTypes.type' => 1)]);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('OpenEmis.Autocomplete');
        $this->addBehavior('User.AdvancedNameSearch');

        $this->addBehavior('SpecialNeeds.SpecialNeeds');
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Excel', ['pages' => ['index']]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxReferrerAutocomplete'] = 'ajaxReferrerAutocomplete';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ]
            ])
            ->add('comment', 'length', [
                'rule' => ['maxLength', self::COMMENT_MAX_LENGTH],
                'message' => __('Comment must not be more then '.self::COMMENT_MAX_LENGTH.' characters.')
             ])
            ->allowEmpty('file_content');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'referrer_id':
                return __('Referrer Name');
            case 'special_needs_referrer_type_id':
                return __('Referrer Type');
            case 'reason_type_id':
                return __('Reason for Referral');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Academic Periods Filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : '-1';

        $academicPeriodOptions = ['-1' => 'All Academic Period'] + $academicPeriodOptions;
        if ($selectedAcademicPeriod != '-1') {
            $query->where([
                $this->aliasField('academic_period_id') => $selectedAcademicPeriod
            ]);
        }

        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $extra['elements']['controls'] = ['name' => 'SpecialNeeds.Referrals/controls', 'data' => [], 'options' => [], 'order' => 1];
        // Academic Periods Filter - END
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (is_null($this->request->query('academic_period_id'))) {
            $currentAcademicPeriod = $this->AcademicPeriods->getCurrent();
            $url = $this->ControllerAction->url($this->alias());
            $url['academic_period_id'] = '-1';
            $this->controller->redirect($url);
        }

        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->setFieldOrder(['referrer_id', 'referrer_type_id', 'date', 'reason_type_id']);

        // Start POCOR-5188
         if($this->request->params['controller'] == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Referrals','Staff - Special Needs');       
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
        }elseif($this->request->params['controller'] == 'Students'){
            $is_manual_exist = $this->getManualUrl('Institutions','Referrals','Students - Special Needs');       
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

        }elseif($this->request->params['controller'] == 'Directories'){ 
            $is_manual_exist = $this->getManualUrl('Directory','Referrals','Special Needs');       
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

        }elseif($this->request->params['controller'] == 'Profiles'){ 
            $is_manual_exist = $this->getManualUrl('Personal','Referrals','Special Needs');       
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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            if ($entity->has('academic_period_id')) {
                $selectedAcademicPeriodId = $entity->academic_period_id;
            } else {
                $academicPeriodQueryString = $this->request->query('academic_period_id');
                if (!is_null($academicPeriodQueryString) && $this->AcademicPeriods->exists($academicPeriodQueryString)) {
                    $selectedAcademicPeriodId = $academicPeriodQueryString;
                } else {
                    $selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
                }
            }

            if ($selectedAcademicPeriodId == '-1') {
                $selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
            }
            $academicPeriodName = $this->AcademicPeriods
                ->get($selectedAcademicPeriodId)
                ->name;

            $attr['type'] = 'readonly';
            $attr['value'] = $selectedAcademicPeriodId;
            $attr['attr']['value'] = $academicPeriodName;

            return $attr;
        }
    }

    public function onUpdateFieldReferrerId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $dataKey = 'referrer_id';

            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => $dataKey, 'name' => $this->aliasField($dataKey)];
            $attr['noResults'] = __('No User found.');
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
            // $attr['onSelect'] = "$('#reload').click();";

            $urlAction = $this->alias();
            $attr['url'] = ['controller' => $this->controller->name, 'action' => $urlAction, 'ajaxReferrerAutocomplete'];

            $requestData = $this->request->data;
            if (isset($requestData) && !empty($requestData[$this->alias()][$dataKey])) {
                $referrerId = $requestData[$this->alias()][$dataKey];
                $referrerName = $this->Referrers->get($referrerId)->name_with_id;
                $attr['attr']['value'] = $referrerName;
            }

            $entity = $attr['entity'];
            if ($entity->has($dataKey) && !is_null($entity->{$dataKey})) {
                $referrerId = $entity->{$dataKey};
                $referrerName = $this->Referrers->get($referrerId)->name_with_id;
                $attr['attr']['value'] = $referrerName;
            }

            return $attr;
        }
    }

    public function onGetReferrerId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            if ($entity->has('referrer_id')) {
                return $event->subject()->Html->link($entity->referrer->name_with_id, [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'Directories',
                    'view',
                    $this->paramsEncode(['id' => $entity->referrer->id])
                ]);
            }
        } elseif ($this->action == 'index') {
            return $entity->referrer->name_with_id;
        }
    }

    public function ajaxReferrerAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];

            $UserIdentitiesTable = TableRegistry::get('User.Identities');

            $query = $this->Referrers
                ->find()
                ->select([
                    $this->Referrers->aliasField('openemis_no'),
                    $this->Referrers->aliasField('first_name'),
                    $this->Referrers->aliasField('middle_name'),
                    $this->Referrers->aliasField('third_name'),
                    $this->Referrers->aliasField('last_name'),
                    $this->Referrers->aliasField('preferred_name'),
                    $this->Referrers->aliasField('id')
                ])
                ->leftJoin(
                    [$UserIdentitiesTable->alias() => $UserIdentitiesTable->table()],
                    [
                        $UserIdentitiesTable->aliasField('security_user_id') . ' = ' . $this->Referrers->aliasField('id')
                    ]
                )
                ->group([
                    $this->Referrers->aliasField('id')
                ])
                ->limit(100);

            $term = trim($term);

            if (!empty($term)) {
                $query = $this->addSearchConditions($query, ['alias' => 'Referrers', 'searchTerm' => $term, 'OR' => ['`Identities`.number LIKE ' => $term . '%']]);
            }

            $list = $query->all();

            $data = [];
            foreach ($list as $obj) {
                $label = sprintf('%s - %s', $obj->openemis_no, $obj->name);
                $data[] = ['label' => $label, 'value' => $obj->id];
            }

            echo json_encode($data);
            die;
        }
    }

    private function setupFields($entity = null)
    {
        $this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('referrer_id', ['entity' => $entity]);
        $this->field('special_needs_referrer_type_id', ['type' => 'select']);
        $this->field('date');
        $this->field('reason_type_id', ['type' => 'select']);
        $this->field('comment', ['type' => 'text']);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment'), 'required' => true], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);

        $this->setFieldOrder(['academic_period_id', 'referrer_id', 'special_needs_referrer_type_id', 'date', 'reason_type_id', 'comment', 'file_name', 'file_content']);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $studentUserId = $session->read('Institution.StudentUser.primaryKey.id');
        $academicPeriodId = $this->request->query['academic_period_id'];
        $institutionId  = $session->read('Institution.Institutions.id');
        if($academicPeriodId == '-1'){
            $query
            ->where([
                'security_user_id =' .$studentUserId,
            ]);
        }else{
            $query
            ->where([
                'academic_period_id =' .$academicPeriodId,
                'security_user_id =' .$studentUserId,
            ]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key' => '',
            'field' => 'date',
            'type' => 'date',
            'label' => __('Date')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'file_name',
            'type' => 'string',
            'label' => __('File Name')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'academic_period_id',
            'type' => 'string',
            'label' => __('Academic Period')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'security_user_id',
            'type' => 'string',
            'label' => __('Security User')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'referrer_id',
            'type' => 'string',
            'label' => __('Referrer Name')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'special_needs_referrer_type_id',
            'type' => 'string',
            'label' => __('Special Needs Referrer Type')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'reason_type_id',
            'type' => 'string',
            'label' => __('Reason Type')
        ];
        $fields->exchangeArray($extraField);
    }

}
