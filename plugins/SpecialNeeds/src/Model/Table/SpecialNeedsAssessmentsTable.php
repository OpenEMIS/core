<?php
namespace SpecialNeeds\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class SpecialNeedsAssessmentsTable extends ControllerActionTable
{
    const COMMENT_MAX_LENGTH = 350;
    public function initialize(array $config)
    {
        $this->table('user_special_needs_assessments');
        parent::initialize($config);

        $this->belongsTo('SpecialNeedsTypes', ['className' => 'SpecialNeeds.SpecialNeedsTypes', 'foreignKey' => 'special_need_type_id', 'conditions' => array('SpecialNeedsTypes.type' => 2, )]);
        $this->belongsTo('Assessor', ['className' => 'Security.Users', 'foreignKey' => 'assessor_id']);    //POCOR-6873
        $this->belongsTo('SpecialNeedDifficulties', ['className' => 'SpecialNeeds.SpecialNeedsDifficulties', 'foreignKey' => 'special_need_difficulty_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('OpenEmis.Autocomplete');    //POCOR-6873
        $this->addBehavior('User.AdvancedNameSearch');  //POCOR-6873

        $this->addBehavior('SpecialNeeds.SpecialNeeds');
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

      

        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }
        $this->addBehavior('Excel', ['pages' => ['index']]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->notEmpty('assessor_id')
            ->add('comment', 'length', [
                'rule' => ['maxLength', self::COMMENT_MAX_LENGTH],
                'message' => __('Comment must not be more then '.self::COMMENT_MAX_LENGTH.' characters.')
             ])
            ->allowEmpty('file_content');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStudentRisks.calculateRiskValue'] = 'institutionStudentRiskCalculateRiskValue';
        $events['ControllerAction.Model.ajaxAssessorAutocomplete'] = 'ajaxAssessorAutocomplete';  //POCOR-6873
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'special_need_type_id':
                return __('Type');
            case 'special_need_difficulty_id':
                return __('Difficulty');
            case 'assessor_id':
                return __('Assessor Name');  //POCOR-6873
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('date', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->setFieldOrder(['special_need_type_id', 'special_need_difficulty_id','assessor_id']);  //POCOR-6873


        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Personal','Assessments','Special Needs');       
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

    public function institutionStudentRiskCalculateRiskValue(Event $event, ArrayObject $params)
    {
        $institutionId = $params['institution_id'];
        $studentId = $params['student_id'];
        $academicPeriodId = $params['academic_period_id'];

        $quantityResult = $this->find()
            ->where([$this->aliasField('security_user_id') => $studentId])
            ->all()
            ->toArray();
        $quantity = !empty(count($quantityResult)) ? count($quantityResult) : 0;

        return $quantity;
    }

    public function getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName)
    {
        $specialNeedList = $this->find()
            ->contain(['SpecialNeedsTypes', 'SpecialNeedDifficulties'])
            ->where([$this->aliasField('security_user_id') => $studentId])
            ->all();

        $referenceDetails = [];
        foreach ($specialNeedList as $key => $obj) {
            $specialNeedName = $obj->special_needs_type->name;
            $specialNeedDifficulties = $obj->special_need_difficulty->name;

            $referenceDetails[$obj->id] = __($specialNeedName) . ' (' . __($specialNeedDifficulties) . ')';
        }

        // tooltip only receieved string to be display
        $reference = '';
        if (!empty($referenceDetails)) {
            foreach ($referenceDetails as $key => $referenceDetailsObj) {
                $reference = $reference . $referenceDetailsObj . ' <br/>';
            }
        } else {
            $reference = __('No Special Need');
        }

        return $reference;
    }

    private function setupFields($entity = null)
    {
        $this->field('date');
        $this->field('special_need_type_id', ['type' => 'select']);
        $this->field('special_need_difficulty_id', ['type' => 'select']);
        $this->field('assessor_id', ['entity' => $entity]);  //POCOR-6873
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment'), 'required' => true], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('comment', ['type' => 'text']);

        $this->setFieldOrder(['date', 'assessor_id', 'special_need_type_id', 'special_need_difficulty_id','file_name', 'file_content', 'comment']); //POCOR-6873
    }

    //POCOR-6873[START]
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
            'field' => 'special_need_type_id',
            'type' => 'string',
            'label' => __('Special Need Type')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'special_need_difficulty_id',
            'type' => 'string',
            'label' => __('Difficulty')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'security_user_id',
            'type' => 'string',
            'label' => __('Security User')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'assessor_id',
            'type' => 'string',
            'label' => __('Assessor Name')
        ];
        $fields->exchangeArray($extraField);
    }
    //POCOR-6873[END]

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $studentUserId = $session->read('Institution.StudentUser.primaryKey.id');


        $query
        ->where([
            'security_user_id =' .$studentUserId,
        ]);
    }

    /**
     * Get all Assessor ids as key and name as value
     * @usage  It is used as drop-down options for auto search
     * @author Rahul Singh <rahul.singh@mail.valuecoders.com>
     * @ticket POCOR-6873
     */

    public function onUpdateFieldAssessorId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $dataKey = 'assessor_id';

            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => $dataKey, 'name' => $this->aliasField($dataKey)];
            $attr['noResults'] = __('No User found.');
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
            // $attr['onSelect'] = "$('#reload').click();";

            $urlAction = $this->alias();
            $attr['url'] = ['controller' => $this->controller->name, 'action' => $urlAction, 'ajaxAssessorAutocomplete'];

            $requestData = $this->request->data;
            if (isset($requestData) && !empty($requestData[$this->alias()][$dataKey])) {
                $assessorId = $requestData[$this->alias()][$dataKey];
                $assessorName = $this->Assessor->get($assessorId)->name_with_id;
                $attr['attr']['value'] = $assessorName;
            }

            $entity = $attr['entity'];
            if ($entity->has($dataKey) && !is_null($entity->{$dataKey})) {
                $assessorId = $entity->{$dataKey};
                $assessorName = $this->Assessor->get($assessorId)->name_with_id;
                $attr['attr']['value'] = $assessorName;
            }

            return $attr;
        }
    }

    /**
     * Get all Assessor ids as key and name as value
     * @usage  It is used get value
     * @author Rahul Singh <rahul.singh@mail.valuecoders.com>
     * @ticket POCOR-6873
     */

    public function onGetAssessorId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            if ($entity->has('assessor_id')) {
                return $event->subject()->Html->link($entity->assessor->name_with_id, [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'Directories',
                    'view',
                    $this->paramsEncode(['id' => $entity->assessor->id])
                ]);
            }
        } elseif ($this->action == 'index') {
            return $entity->assessor->name_with_id;
        }
    }

    /**
     * Get all OpenEMIS ID, Identity Number or Name as value
     * @usage  It is used as drop-down options for auto search
     * @author Rahul Singh <rahul.singh@mail.valuecoders.com>
     * @ticket POCOR-6873
     */

    public function ajaxAssessorAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];

            $UserIdentitiesTable = TableRegistry::get('User.Identities');

            $query = $this->Assessor
                ->find()
                ->select([
                    $this->Assessor->aliasField('openemis_no'),
                    $this->Assessor->aliasField('first_name'),
                    $this->Assessor->aliasField('middle_name'),
                    $this->Assessor->aliasField('third_name'),
                    $this->Assessor->aliasField('last_name'),
                    $this->Assessor->aliasField('preferred_name'),
                    $this->Assessor->aliasField('id')
                ])
                ->leftJoin(
                    [$UserIdentitiesTable->alias() => $UserIdentitiesTable->table()],
                    [
                        $UserIdentitiesTable->aliasField('security_user_id') . ' = ' . $this->Assessor->aliasField('id')
                    ]
                )
                ->group([
                    $this->Assessor->aliasField('id')
                ])
                ->limit(100);

            $term = trim($term);

            if (!empty($term)) {
                $query = $this->addSearchConditions($query, ['alias' => 'Assessor', 'searchTerm' => $term, 'OR' => ['`Identities`.number LIKE ' => $term . '%']]);
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

}
