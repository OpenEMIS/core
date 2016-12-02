<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportTextbooksTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'InstitutionTextbooks'
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateTextbooksData'] = 'onImportPopulateTextbooksData';
        $events['Model.import.onImportPopulateUsersData'] = 'onImportPopulateUsersData';
        $events['Model.import.onImportPopulateTextbookStatusesData'] = 'onImportPopulateTextbookStatusesData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function beforeAction($event) {
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
    }

    public function onImportPopulateTextbooksData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = [$lookedUpTable->aliasField('title'), $lookedUpTable->aliasField('code'), $lookedUpTable->aliasField($lookupColumn), $lookedUpTable->aliasField('ISBN')];
        $order = [$lookedUpTable->aliasField('title')];

        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'title');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, __('Code'), $translatedCol, __('ISBN')];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->title,
                    $row->code,
                    $row->$lookupColumn,
                    $row->ISBN
                ];
            }
        }
    }

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        unset($data[$columnOrder]);
    }

    public function onImportPopulateTextbookStatusesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = [$lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn)];
        $order = [$lookedUpTable->aliasField('name')];

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

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }
        $tempRow['institution_id'] = $this->institutionId;

        if ($tempRow->offsetExists('textbook_id') && !empty($tempRow['textbook_id'])) {
            $Textbooks = TableRegistry::get('Textbook.Textbooks');
            $textbookResults = $Textbooks
                ->find()
                ->where([$Textbooks->aliasField('id') => $tempRow['textbook_id']])
                ->all();

            if ($textbookResults->isEmpty()) {
                $rowInvalidCodeCols['textbook_id'] = $this->getExcelLabel('Import', 'value_not_in_list');
                return false;
            } else {
                $textbookEntity = $textbookResults->first();
                $tempRow['academic_period_id'] = $textbookEntity->academic_period_id;
                $tempRow['education_subject_id'] = $textbookEntity->education_subject_id;
            }
        }

        return true;
    }
}
