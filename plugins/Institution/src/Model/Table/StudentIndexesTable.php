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
            ]);

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
        // pr('onGetGeneratedBy');
        // return
    }

    public function onGetGeneratedOn(Event $event, Entity $entity)
    {
        // from indexes table
        // pr('onGetGeneratedOn');
        // return
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $indexesCriterias = TableRegistry::get('Indexes.indexesCriterias');
        $tableHeaders = $this->getMessage('Indexes.TableHeader');
        array_splice($tableHeaders, 3, 0, __('Value')); // adding value header
        $tableCells = [];
        $fieldKey = 'indexes_criterias';

        $indexId = $entity->index->id;
        $institutionId = $entity->institution->id;
        $studentId = $entity->user->id;
        $institutionStudentIndexId = $this->paramsPass(0);

        if ($action == 'view') {
            $indexesCriteriasResults = $indexesCriterias->find()
                ->where([$indexesCriterias->aliasField('index_id') => $indexId])
                ->order(['criteria'])
                ->all();

            if (!$indexesCriteriasResults->isEmpty()) {
                foreach ($indexesCriteriasResults as $i => $obj) {
                    $criteriaKey = $obj->criteria;
                    $indexesCriteriaId = $obj->id;
                    $operator = $obj->operator; // need this as an operator to do the red font
                    $threshold = $obj->threshold; // need this as an operator to do the red font

                    $value = $this->StudentIndexesCriterias->getValue($institutionStudentIndexId, $indexesCriteriaId);

                    // to get the red highlighted font
                    switch ($operator) {
                    case 1: // '<'
                        if ($value < $threshold) {
                            $indexValue = '<div style="color : red">'.$obj->index_value.'</div>';
                        } else {
                            $indexValue = $obj->index_value;
                        }
                        break;

                     case 2: // '>'
                        if ($value > $threshold) {
                            $indexValue = '<div style="color : red">'.$obj->index_value.'</div>';
                        } else {
                            $indexValue = $obj->index_value;
                        }
                        break;

                    case 3: // '='
                        $criteriaDetails = $this->Indexes->getCriteriasDetails($criteriaKey);
                        $lookupModel = TableRegistry::get($criteriaDetails['threshold']['lookupModel']);
                        $criteriaModel = TableRegistry::get($criteriaKey);
                        $threshold = $lookupModel->get($threshold)->name;

                        if ($value == 'True') {
                            // to get total number of behaviour
                            $getValueIndex = $criteriaModel->getValueIndex($institutionId, $studentId);
                            $totalBehaviour = $getValueIndex[$obj->threshold];

                            $indexValue = '<div style="color : red">' . $obj->index_value . ' ( ' . $totalBehaviour . ' )' . '</div>';
                        } else {
                            $indexValue = $obj->index_value;
                        }
                        break;
                    }

                    $rowData = [];
                    $rowData[] = $this->Indexes->getCriteriasDetails($criteriaKey)['name'];
                    $rowData[] = $this->Indexes->getCriteriasDetails($criteriaKey)['operator'][$obj->operator];
                    $rowData[] = $threshold;
                    $rowData[] = $value;
                    $rowData[] = $indexValue; // need this as an operator to do the red font


                    $tableCells[] = $rowData;
                }
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Indexes.Indexes/' . $fieldKey, ['attr' => $attr]);
    }
}
