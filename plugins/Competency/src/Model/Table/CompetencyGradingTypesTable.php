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
        $this->hasMany('Criterias', ['className' => 'Competency.CompetencyCriterias']);
        $this->hasMany('GradingOptions', ['className' => 'Competency.CompetencyGradingOptions', 'foreignKey' => 'competency_grading_type_id', 'saveStrategy' => 'replace']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentCompetencies' => ['index']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('grading_options')
            ->allowEmpty('code')
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['checkUniqueCode', ''],
                    'provider' => 'table'
                ]
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getCompetencyTabs();

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

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $criteriaForm = $this->getQueryString(null, 'criteriaForm');
        if ($criteriaForm) {
            $toolbarButtons = $extra['toolbarButtons'];
            if ($toolbarButtons->offsetExists('back')) {
                $toolbarButtons['back']['url']['action'] = 'Criterias';
                $toolbarButtons['back']['url'][0] = 'add';
            }
            $extra['criteriaForm'] = $criteriaForm;
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
        if (!isset($requestData[$this->alias()]['grading_options']) || empty($requestData[$this->alias()]['grading_options'])) {
                $this->Alert->warning($this->aliasField('noGradingOptions'));
            } else if (isset($requestData[$this->alias()]['grading_options']) && is_array($requestData[$this->alias()]['grading_options'])) {
                $gradingOptions = $requestData[$this->alias()]['grading_options'];
                $codes = array_column($gradingOptions, 'code');
                $codes = array_filter($codes);
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

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if ($extra->offsetExists('criteriaForm')) {
            $url = $this->url('add');
            $url['action'] = 'Criterias';
            $criteriaForm = $extra['criteriaForm'];
            $criteriaForm['competency_grading_type_id'] = $entity->id;
            $url = $this->setQueryString($url, $criteriaForm, 'criteriaForm');
            $extra['redirect'] = $url;
        }
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {

        if (!isset($requestData[$this->alias()]['grading_options']) || empty($requestData[$this->alias()]['grading_options'])) {
            $this->Alert->warning($this->aliasField('noGradingOptions'));
            } else if (isset($requestData[$this->alias()]['grading_options']) && is_array($requestData[$this->alias()]['grading_options'])) {
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

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->GradingOptions->alias()
        ];
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
