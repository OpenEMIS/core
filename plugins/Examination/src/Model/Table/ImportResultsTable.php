<?php
namespace Examination\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportResultsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Examination',
            'model' => 'ExaminationItemResults',
            'backUrl' => ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamResults']
        ]);

        // register the target table once
        $this->ExaminationItemResults = TableRegistry::get('Examination.ExaminationItemResults');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateAcademicPeriodsData'] = 'onImportPopulateAcademicPeriodsData';
        $events['Model.import.onImportPopulateExaminationsData'] = 'onImportPopulateExaminationsData';
        $events['Model.import.onImportPopulateExaminationCentresData'] = 'onImportPopulateExaminationCentresData';
        $events['Model.import.onImportPopulateEducationSubjectsData'] = 'onImportPopulateEducationSubjectsData';
        $events['Model.import.onImportPopulateUsersData'] = 'onImportPopulateUsersData';
        $events['Model.import.onImportPopulateInstitutionsData'] = 'onImportPopulateInstitutionsData';
        $events['Model.import.onImportPopulateExaminationGradingOptionsData'] = 'onImportPopulateExaminationGradingOptionsData';
        return $events;
    }

    public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        $modelData = $lookedUpTable->find('all')
            ->find('years')
            ->select($selectFields);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->$lookupColumn
                ];
            }
        }
    }

    public function onImportPopulateExaminationsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.name', $lookupModel.'.code'];

        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->group(['code'])
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->$lookupColumn
                ];
            }
        }
    }

    public function onImportPopulateExaminationCentresData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.name', $lookupModel.'.code'];

        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->group(['institution_id'])
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->$lookupColumn
                ];
            }
        }
    }

    public function onImportPopulateEducationSubjectsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.name', $lookupModel.'.code'];

        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $ExaminationCentreSubjects = TableRegistry::get('Examination.ExaminationCentreSubjects');
        $selectFields = [$lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn)];
        $modelData = $ExaminationCentreSubjects->find('all')
            ->select($selectFields)
            ->matching($lookedUpTable->alias())
            ->group(['education_subject_id'])
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->_matchingData[$lookedUpTable->alias()]->name,
                    $row->_matchingData[$lookedUpTable->alias()]->$lookupColumn
                ];
            }
        }
    }

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        unset($data[$columnOrder]);
    }

    public function onImportPopulateInstitutionsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.name', $lookupModel.'.code'];

        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->$lookupColumn
                ];
            }
        }
    }

    public function onImportPopulateExaminationGradingOptionsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $ExaminationGradingTypes = TableRegistry::get('Examination.ExaminationGradingTypes');
        $selectFields = [$lookedUpTable->aliasField('code'), $lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn), $ExaminationGradingTypes->aliasField('code'), $ExaminationGradingTypes->aliasField('name')];
        $order = [$ExaminationGradingTypes->aliasField('name'), $lookupModel.'.order'];
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->matching($ExaminationGradingTypes->alias())
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, __('Code'), $translatedCol, __('Grading Types')];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->code,
                    $row->$lookupColumn,
                    $row->_matchingData[$ExaminationGradingTypes->alias()]->name
                ];
            }
        }
    }
}
