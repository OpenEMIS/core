<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\ResultSet;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class StudentIndexesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_indexes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('Indexes', ['className' => 'Indexes.Indexes', 'foreignKey' =>'index_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' =>'student_id']);

        $this->hasMany('StudentIndexesCriterias', ['className' => 'Institution.StudentIndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('add', false);
        $this->toggle('search', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('average_index',['visible' => false]);
        $this->field('student_id',['visible' => false]);
        $this->field('total_index',['after' => 'index_id']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('generated_by',['after' => 'total_index']);
        $this->field('generated_on',['after' => 'generated_by']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('indexes_criterias', ['type' => 'custom_criterias', 'after' => 'total_index']);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $studentId = $session->read('Student.Students.id');

        $query = $query
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
            ])
            ->order(['academic_period_id', 'index_id'])
            ;

        return $query;
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        // from indexes table
        $Indexes = TableRegistry::get('Indexes.Indexes');
        $indexId = $entity->index->id;
        $generatedById = $Indexes->get($indexId)->generated_by;

        $userName = '';
        if (!empty($generatedById)) {
            $userName = $this->Users->get($generatedById)->first_name . ' ' . $this->Users->get($generatedById)->last_name;
        }

        return $userName;
    }

    public function onGetGeneratedOn(Event $event, Entity $entity)
    {
        // from indexes table
        $Indexes = TableRegistry::get('Indexes.Indexes');
        $indexId = $entity->index->id;
        $generatedOn = $Indexes->get($indexId)->generated_on->format('F d, Y - H:i:s');

        return $generatedOn;
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');
        $tableHeaders = $this->getMessage('Indexes.TableHeader');
        array_splice($tableHeaders, 3, 0, __('Value')); // adding value header
        $tableHeaders[] = __('References');
        $tableCells = [];
        $fieldKey = 'indexes_criterias';

        $indexId = $entity->index->id;
        $institutionId = $entity->institution->id;
        $studentId = $entity->user->id;
        $academicPeriodId = $entity->academic_period_id;

        $institutionStudentIndexId = $this->paramsDecode($this->paramsPass(0))['id']; // paramsPass(0) after the hash of Id

        if ($action == 'view') {
            $StudentIndexesCriteriasResults = $this->StudentIndexesCriterias->find()
                ->contain(['IndexesCriterias'])
                ->where([
                    $this->StudentIndexesCriterias->aliasField('institution_student_index_id') => $institutionStudentIndexId,
                    // $this->StudentIndexesCriterias->aliasField('value') . ' <> ' => 0
                    $this->StudentIndexesCriterias->aliasField('value') . ' IS NOT NULL'
                ])
                ->order(['criteria','threshold'])
                ->all();

            foreach ($StudentIndexesCriteriasResults as $key => $obj) {
                $indexesCriteriasId = $obj->indexes_criteria->id;

                $criteriaName = $obj->indexes_criteria->criteria;
                $operator = $obj->indexes_criteria->operator;
                $threshold = $obj->indexes_criteria->threshold;

                $value = $this->StudentIndexesCriterias->getValue($institutionStudentIndexId, $indexesCriteriasId);

                $criteriaDetails = $this->Indexes->getCriteriasDetails($criteriaName);
                $CriteriaModel = TableRegistry::get($criteriaDetails['model']);



// pr($obj);
// pr($criteriaDetails);
// pr($CriteriaModel);
// pr('indexesCriteriasId');
// pr($indexesCriteriasId);
// pr('criteriaKey');
// pr($criteriaKey);
// pr('operator');
// pr($operator);
// pr('threshold');
// pr($threshold);
// pr('value');
// pr($value);
// die;


                if ($value == 'True') {
                    // Comparison like behaviour
                    // $criteriaDetails = $this->Indexes->getCriteriasDetails($criteriaKey);
                    $LookupModel = TableRegistry::get($criteriaDetails['threshold']['lookupModel']);
                    // $CriteriaModel = TableRegistry::get($criteriaKey);

                    // to get total number of behaviour
                    $getValueIndex = $CriteriaModel->getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName);
                    $quantity = '';
                    if ($getValueIndex[$threshold] > 1) {
                        $quantity = ' ( x'. $getValueIndex[$threshold]. ' )';
                    }

                    $indexValue = '<div style="color : red">' . $obj->indexes_criteria->index_value . $quantity  .'</div>';

                    // for reference tooltip
                    $reference = $CriteriaModel->getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName);

                    // for threshold name
                    $thresholdName = $LookupModel->get($threshold)->name;
                    $threshold = $thresholdName;
                } else {
                    // numeric value come here (absence quantity, results)
                    // $CriteriaModel = TableRegistry::get($criteriaKey);
// pr($criteriaKey);
// pr($CriteriaModel);
// pr('numeric');
// die;

                    // for value
                    $indexValue = '<div style="color : red">'.$obj->indexes_criteria->index_value.'</div>';

                    // for the reference tooltip
                    $reference = $CriteriaModel->getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName);
                }

                // blue info tooltip
                $tooltipReference = '<i class="fa fa-info-circle fa-lg icon-blue" data-placement="left" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="'.$reference.'"></i>';

                // to put in the table
                $rowData = [];
                $rowData[] = $this->Indexes->getCriteriasDetails($criteriaName)['name'];
                $rowData[] = $this->Indexes->getCriteriasDetails($criteriaName)['operator'][$operator];
                $rowData[] = $threshold;
                $rowData[] = $value;
                $rowData[] = $indexValue;
                $rowData[] = $tooltipReference;

                $tableCells [] = $rowData;
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Indexes.Indexes/' . $fieldKey, ['attr' => $attr]);
    }
}
