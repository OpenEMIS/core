<?php
namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

use App\Model\Table\ControllerActionTable;

class CompetencyGradingTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('competency_grading_types');

        parent::initialize($config);

        $this->hasMany('GradingOptions', ['className' => 'Competency.CompetencyGradingOptions', 'foreignKey' => 'competency_grading_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('code')
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['checkUniqueCode', ''],
                    'provider' => 'table'
                ]
            ]);
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {

        if ($this->action == 'add' || $this->action == 'edit' || $this->action == 'view') {
            $this->field('grading_options', [
                'type' => 'element',
                'element' => 'Competency.grading_options',
                'fields' => $this->GradingOptions->fields,
                'formFields' => [],
                'attr' => [
                    'label' => 'Criteria Grading Options'
                ]
            ]);
        }
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['grading_options']['formFields'] = array_keys($this->GradingOptions->getFormFields());

        $this->setFieldOrder([
            'code', 'name', 'grading_options',
        ]);
    }

    public function addEditAfterAction (Event $event, Entity $entity, ArrayObject $extra)
    {
        // $gradingOptions will contain the GradeOptionId and the association.(1 for true and 0 for false)
        // $GradingOptions = TableRegistry::get('Competency.CompetencyGradingOptions');
        $gradingOptions = [];
        if (!is_null($entity->grading_options)) {
            foreach ($entity->grading_options as $key => $gradingOption) {
                $gradingOptionId = $gradingOption->id;
                $gradingOptions[$gradingOptionId] = 0;
                if ($this->hasAssociatedRecords($this->GradingOptions, $gradingOption, $extra)) {
                    $gradingOptions[$gradingOptionId] = 1;
                }
            }
        }

        // to passed the array of the association to the view (grading_options.ctp).
        $this->controller->set('gradingOptions', $gradingOptions);
    }

    public function addEditOnReload(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions)
    {
        $groupOptionData = $this->GradingOptions->getFormFields();
        if (!empty($entity->id)) {
            $groupOptionData['competency_grading_type_id'] = $entity->id;
        }
        $newGroupOption = $this->GradingOptions->newEntity($groupOptionData);
        $requestData[$this->alias()]['grading_options'][] = $newGroupOption->toArray();
        $newOptions = [$this->GradingOptions->alias() => ['validate'=>false]];
        if (isset($patchOptions['associated'])) {
            $patchOptions['associated'] = array_merge($patchOptions['associated'], $newOptions);
        } else {
            $patchOptions['associated'] = $newOptions;
        }
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if (isset($requestData[$this->alias()]['grading_options']) && is_array($requestData[$this->alias()]['grading_options'])) {
            $gradingOptions = $requestData[$this->alias()]['grading_options'];
            $codes = array_column($gradingOptions, 'code');
            $vals = array_count_values($codes);
            foreach ($vals as $count) {
                if ($count > 1) {
                    $entity->errors('grading_options', __('Duplicated Code'));
                    $this->Alert->error('general.uniqueCodeForm');
                    break;
                }
            }
        }
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if (isset($requestData[$this->alias()]['grading_options']) && is_array($requestData[$this->alias()]['grading_options'])) {
            $gradingOptions = $requestData[$this->alias()]['grading_options'];
            $codes = array_column($gradingOptions, 'code');
            $vals = array_count_values($codes);
            foreach ($vals as $count) {
                if ($count > 1) {
                    $entity->errors('grading_options', __('Duplicated Code'));
                    $this->Alert->error('general.uniqueCodeForm');
                    break;
                }
            }
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        // get the array of the original gradeOptions
        $GradingOptions = TableRegistry::get('Competency.CompetencyGradingOptions');
        $query = $GradingOptions
            ->find()
            ->where(['competency_grading_type_id' => $entity->id])
            ->toArray();

        if (!empty($query)) {
            $gradingOptions = [];
            foreach ($query as $key => $gradingOption) {
                $gradingOptionId = $gradingOption->id;
                $gradingOptions[$gradingOptionId] = 0;
                if ($this->hasAssociatedRecords($GradingOptions, $gradingOption, $extra)) {
                    $gradingOptions[$gradingOptionId] = 1;
                }
            }

            // it will check if there are any in-used gradeOption, can't delete all the gradeOptions.
            $allowedDeleteAll = max($gradingOptions);

            $currentGradingOptionIds = (new Collection($entity->grading_options))->extract($this->GradingOptions->primaryKey())->toArray();
            $originalGradingOptionIds = (new Collection($entity->getOriginal('grading_options')))->extract($this->GradingOptions->primaryKey())->toArray();
            $tempRemovedGradingOptionIds = array_diff($originalGradingOptionIds, $currentGradingOptionIds);

            // get the array of gradeOption that will be deleted, if the gradeOption was in-used it will be excluded from this array.
            $removedGradingOptionIds = [];
            foreach ($tempRemovedGradingOptionIds as $key => $value) {
                if (!$gradingOptions[$value]) {
                    $removedGradingOptionIds[$key] = $value;
                }
            }

            // remove the gradeOption inside the removed gradeOptions array.
            // remove all the gradeOptions if no in-use gradeOption.
            if (!empty($removedGradingOptionIds)) {
                $this->GradingOptions->deleteAll([
                    $this->GradingOptions->aliasField($this->GradingOptions->primaryKey()) . ' IN ' => $removedGradingOptionIds
                ]);
            } else if ((!array_key_exists('grading_options', $requestData['CompetencyGradingTypes'])) && (!$allowedDeleteAll)){
                $this->GradingOptions->deleteAll([
                    $this->GradingOptions->aliasField('competency_grading_type_id') => $entity->id
                ]);
            }
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['grading_options']['formFields'] = array_keys($this->GradingOptions->getFormFields('view'));

        $this->setFieldOrder([
            'code', 'name', 'grading_options'
        ]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            $this->GradingOptions->alias()
        ]);
    }

    public function getCustomList($params = []) {
        if (array_key_exists('keyField', $params)) {
            $keyField = $params['keyField'];
        } else {
            $keyField = 'id';
        }
        if (array_key_exists('valueField', $params)) {
            $valueField = $params['valueField'];
        } else {
            $valueField = 'name';
        }
        $query = $this->find('list', ['keyField' => $keyField, 'valueField' => $valueField]);
        return $this->getList($query);
    }
}
