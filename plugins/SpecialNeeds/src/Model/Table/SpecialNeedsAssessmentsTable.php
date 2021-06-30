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
        $this->belongsTo('SpecialNeedDifficulties', ['className' => 'SpecialNeeds.SpecialNeedsDifficulties', 'foreignKey' => 'special_need_difficulty_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);

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
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
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
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'special_need_type_id':
                return __('Type');
            case 'special_need_difficulty_id':
                return __('Difficulty');
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
        $this->setFieldOrder(['special_need_type_id', 'special_need_difficulty_id']);
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
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment'), 'required' => true], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('comment', ['type' => 'text']);

        $this->setFieldOrder(['date', 'special_need_type_id', 'special_need_difficulty_id', 'file_name', 'file_content', 'comment']);
    }
}
