<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class InstitutionFeesTable extends ControllerActionTable
{
    use MessagesTrait;

    private $institutionId = 0;
    private $_selectedAcademicPeriodId = 0;
    private $_academicPeriodOptions = [];
    private $_gradeOptions = [];
    public $currency = '';


    /******************************************************************************************************************
    **
    ** CakePHP default methods
    **
    ******************************************************************************************************************/
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->hasMany('InstitutionFeeTypes', ['className' => 'Institution.InstitutionFeeTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentFees', ['className' => 'Institution.StudentFeesAbstract']);
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('RestrictAssociatedDelete', ['message' => 'InstitutionFees.fee_payments_exists']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $this->institutionId = $session->read('Institution.Institutions.id');

        $this->field('total', ['type' => 'float', 'visible' => ['add' => false, 'edit' => false, 'index' => true, 'view' => true]]);
        $this->field('institution_id', ['type' => 'hidden', 'visible' => ['edit'=>true]]);
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true], 'onChangeReload'=>true]);
        $this->field('education_grade_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('education_programme', ['type' => 'select', 'visible' => ['index'=>true]]);

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $this->currency = $ConfigItems->value('currency');
        $this->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $this->currency, 'visible' => ['view'=>true, 'edit'=>true]]);
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action)
    {
        if ($action == 'edit' || $action == 'add') {
            $includes['fees'] = [
                'include' => true,
                'js' => ['Institution.../js/fees']
            ];
        }
    }


    /******************************************************************************************************************
    **
    ** index action methods
    **
    ******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'education_programme', 'education_grade_id', 'total'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        if (empty($request->query['academic_period_id'])) {
            $request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }

        $selectedOption = $this->queryString('academic_period_id', $academicPeriodOptions);
        $Fees = $this;
        $institutionId = $this->institutionId;

        $this->advancedSelectOptions($academicPeriodOptions, $selectedOption, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noProgrammeGradeFees')),
            'callable' => function ($id) use ($Fees, $institutionId) {
                return $Fees->find()->where(['institution_id'=>$institutionId, 'academic_period_id'=>$id])->count();
            }
        ]);

        $this->controller->set('selectedOption', $selectedOption);
        $this->controller->set(compact('academicPeriodOptions'));
        $extra['elements']['custom'] = [
            'name' => 'Institution.Fees/controls',
            'order' => 0
        ];

        $academicPeriodId = $selectedOption;
        $query
            ->contain(['InstitutionFeeTypes'])
            ->find('withProgrammes')
            ->where([$this->aliasField('academic_period_id') => $academicPeriodId]);
    }

    public function findWithProgrammes(Query $query, array $options)
    {
        return $query->contain(['EducationGrades'=>['EducationProgrammes']]);
    }


    /******************************************************************************************************************
    **
    ** view action methods
    **
    ******************************************************************************************************************/
    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'academic_period_id', 'education_grade_id', 'fee_types'
        ]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'EducationGrades',
            'InstitutionFeeTypes.FeeTypes'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $feeTypes = [];
        $amount = 0.00;
        foreach ($entity->institution_fee_types as $key=>$obj) {
            $feeTypes[$obj->fee_type->order] = [
                'id' => $obj->id,
                'type' => $obj->fee_type->name,
                'fee_type_id' => $obj->fee_type_id,
                'amount' => number_format($obj->amount, 2)
            ];
            $amount = (float)$amount + (float)$obj->amount;
        }
        ksort($feeTypes);
        $this->fields['fee_types']['data'] = $feeTypes;
        $this->fields['fee_types']['total'] = $this->currency.' '.number_format($amount, 2);
    }


    /******************************************************************************************************************
    **
    ** edit action methods
    **
    ******************************************************************************************************************/
    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'academic_period_id', 'education_grade_id'
        ]);

        $this->fields['academic_period_id']['type'] = 'readonly';
        $this->fields['education_grade_id']['type'] = 'readonly';
        $this->field('total', ['visible' => false]);
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $this->cleanFeeTypes($data);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $feeTypes = [];
        foreach ($this->fields['fee_types']['options'] as $key=>$obj) {
            $feeTypes[] = [
                'id' => Text::uuid(),
                'type' => $obj,
                'fee_type_id' => $key,
                'amount' => '',
                // 'error' => ''
            ];
        }
        $this->fields['fee_types']['data'] = $feeTypes;

        $exists = [];
        $types = $this->fields['fee_types']['options']->toArray();
        foreach ($entity->institution_fee_types as $key=>$obj) {
            $exists[] = [
                'id' => $obj->id,
                'type' => $types[$obj->fee_type_id],
                'fee_type_id' => $obj->fee_type_id,
                'amount' => $obj->amount,
                'error' => $obj->errors('amount')
            ];
        }
        $this->fields['fee_types']['exists'] = $exists;
        $this->fields['fee_types']['currency'] = $this->currency;

        // $this->fields['academic_period_id']['attr']['value'] = $this->_academicPeriodOptions[$entity->academic_period_id];
        $this->fields['academic_period_id']['value'] = $entity->academic_period_id;
        $this->fields['academic_period_id']['attr']['value'] = $this->AcademicPeriods->get($entity->academic_period_id)->name;
        $this->fields['education_grade_id']['attr']['value'] = isset($this->_gradeOptions[$entity->education_grade_id]) ? $this->_gradeOptions[$entity->education_grade_id] : $entity->education_grade->name;
        // $this->fields['education_grade_id']['attr']['value'] = $this->_gradeOptions[$entity->education_grade_id];
    }


    /******************************************************************************************************************
    **
    ** add action methods
    **
    ******************************************************************************************************************/
    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'academic_period_id', 'education_grade_id'
        ]);

        $this->fields['academic_period_id']['options'] = $this->_academicPeriodOptions;

        // find the grades that already has fees
        $existedGrades = $this->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade_id'])
                            ->where([
                                $this->aliasField('institution_id') => $this->institutionId,
                                $this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId
                            ])
                            ->toArray();
        // remove the existed grades from the options
        $gradeOptions = array_diff_key($this->_gradeOptions, $existedGrades);
        $this->fields['education_grade_id']['options'] = $gradeOptions;
        $this->fields['institution_id']['value'] = $this->institutionId;
        // $attr['attr']['value'] = $this->institutionId;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $this->cleanFeeTypes($data);
        $newOptions = ['InstitutionFeeTypes'=>['validate'=>false]];
        if (isset($options['associated'])) {
            $options['associated'] = array_merge($options['associated'], $newOptions);
        } else {
            $options['associated'] = $newOptions;
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $feeTypes = [];
        foreach ($this->fields['fee_types']['options'] as $key=>$obj) {
            $feeTypes[] = [
                'id' => Text::uuid(),
                'type' => $obj,
                'fee_type_id' => $key,
                'amount' => ''
            ];
        }
        $this->fields['fee_types']['data'] = $feeTypes;
        $this->fields['fee_types']['currency'] = $this->currency;
    }



    /******************************************************************************************************************
    **
    ** delete action events
    **
    ******************************************************************************************************************/

    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->InstitutionFeeTypes->alias()
        ];
    }
    
    /******************************************************************************************************************
    **
    ** field specific methods
    **
    ******************************************************************************************************************/
    public function onGetEducationProgramme(Event $event, Entity $entity)
    {
        return $entity->education_grade->education_programme->name;
    }

    public function onGetTotal(Event $event, Entity $entity)
    {
        return $this->currency.' '.number_format($this->getTotal($entity), 2);
    }

    public function getTotal(Entity $entity)
    {
        /**
         * PHPOE-1414
         * Not using $this->total anymore since it only saves till 11 digits with 2 decimal places
         * and when a feeType is for example, 999,999,999.99, the rest of the fee types cannot be added saved into the "total" record.
         * Implements a manual count of the extracted feeTypes.
         */
        $amount = 0.00;
        foreach ($entity->institution_fee_types as $key=>$feeType) {
            $amount = (float)$amount + (float)$feeType->amount;
        }
        return $amount;
    }

    public function onUpdateFieldFeeTypes(Event $event, array $attr, $action, $request)
    {
        $attr['options'] = $this->InstitutionFeeTypes->FeeTypes->getList();
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        $this->_academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $this->_selectedAcademicPeriodId = $this->postString('academic_period_id');
        if ($this->_selectedAcademicPeriodId == 0 || is_null($this->_selectedAcademicPeriodId)) {
            $this->_selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
        }
        $this->advancedSelectOptions($this->_academicPeriodOptions, $this->_selectedAcademicPeriodId);

        $attr['options'] = $this->_academicPeriodOptions;
        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request)
    {
        if (empty($this->request->data[$this->alias()]['academic_period_id'])) {
            $this->request->data[$this->alias()]['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }
        $this->_selectedAcademicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
        $this->_gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
        $attr['options'] = $this->_gradeOptions;
        return $attr;
    }


    /******************************************************************************************************************
    **
    ** essential methods
    **
    ******************************************************************************************************************/
    private function cleanFeeTypes(&$data)
    {
        if (isset($data[$this->alias()]['institution_fee_types'])) {
            $types = $data[$this->alias()]['institution_fee_types'];
            $total = 0;
            foreach ($types as $i => $obj) {
                if (empty($obj['amount'])) {
                    unset($data[$this->alias()]['institution_fee_types'][$i]);
                } else {
                    $total = $total + $obj['amount'];
                }
            }
            $data[$this->alias()]['total'] = $total;
        }
    }
}
