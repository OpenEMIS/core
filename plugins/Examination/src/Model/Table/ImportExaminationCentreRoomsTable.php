<?php
namespace Examination\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportExaminationCentreRoomsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Examination',
            'model' => 'ExaminationCentreRooms',
            'backUrl' => ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres']
        ]);

        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->ExaminationCentres = TableRegistry::get('Examination.ExaminationCentres');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateExaminationCentresData'] = 'onImportPopulateExaminationCentresData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->ControllerAction->field('academic_period_id', [
            'type' => 'select',
            'select' => false,
            'before' => 'select_file'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriod($this->request->query('period'), true));

        if ($action == 'add') {
            $attr['default'] = $selectedPeriod;
            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function getAcademicPeriod($querystringPeriod, $withOptions = false)
    {
        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        if ($withOptions){
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            return compact('periodOptions', 'selectedPeriod');
        } else {
            return $selectedPeriod;
        }
    }

    public function onImportPopulateExaminationCentresData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectedPeriod = $this->getAcademicPeriod($this->request->query('period'));
        
        $selectFields = [
            $lookedUpTable->aliasField($lookupColumn),
            $lookedUpTable->aliasField('code'), 
            $lookedUpTable->aliasField('name'), 
            $this->AcademicPeriods->aliasField('code'), 
            $this->AcademicPeriods->aliasField('name'),
            $this->AcademicPeriods->aliasField('name')
        ];

        $order = [$lookedUpTable->aliasField('name')];

        //populate exams and centre combination based on selected academic period
        $modelData = $lookedUpTable
                    ->find('all')
                    ->select($selectFields)
                    ->matching($this->AcademicPeriods->alias())
                    ->where([
                        $this->AcademicPeriods->aliasField('id') => $selectedPeriod
                    ])
                    ->group([
                        $lookedUpTable->aliasField('id')
                    ])
                    ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 1;
        $data[$columnOrder]['data'][] = [__('ID'), $translatedReadableCol, __('Academic Period')];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->id,
                    $row->code . ' - ' . $row->name,
                    $row->_matchingData[$this->AcademicPeriods->alias()]->name
                ];
            }
        }    
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $selectedPeriod = $this->getAcademicPeriod($this->request->query('period'));
        
        //since academic period is pre-selected and mandatory, then we pass the academic period manually.
        if ($selectedPeriod) {
            $tempRow['academic_period_id'] = $selectedPeriod;
        }

        //match selected academic period with examination centres selected.
        if ($tempRow->offsetExists('examination_centre_id') && !empty($tempRow['examination_centre_id'])) {

            $ExaminationCentre = $this->ExaminationCentres
                                ->find()
                                ->where([
                                    $this->ExaminationCentres->aliasField('id') => $tempRow['examination_centre_id'],
                                    $this->ExaminationCentres->aliasField('academic_period_id') => $selectedPeriod
                                ]);

            if ($ExaminationCentre->isEmpty()) {
                $rowInvalidCodeCols['examination_centre_id'] = $this->getExcelLabel('Import', 'exam_centre_dont_match');
                return false;
            }
        }
        return true;
    }
}
