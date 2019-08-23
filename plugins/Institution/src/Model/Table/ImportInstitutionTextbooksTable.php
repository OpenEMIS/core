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

class ImportInstitutionTextbooksTable extends AppTable
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'TextbookStatuses') {
            return __('Status');
        } else if ($field == 'TextbookConditions') {
            return __('Condition');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
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
                    $row->{$lookupColumn},
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
                    $row->{$lookupColumn}
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
                $tempRow['education_grade_id'] = $textbookEntity->education_grade_id;
                //check for student being assigned 2 same book.
                $InstitutionTextbooks = TableRegistry::get('Institution.InstitutionTextbooks');

                if ($tempRow->offsetExists('code') && empty($tempRow['code'])) {
                    $InstitutionTextbookData = $InstitutionTextbooks->find('all', [
                                'order' => [$InstitutionTextbooks->aliasField('id') => 'DESC']
                            ])->first();
                    $tempRow['code'] = $textbookEntity->code . '-' . ($InstitutionTextbookData->id + 1);
                }

                if ($tempRow->offsetExists('student_id')) {
                    if (!empty($tempRow['student_id'])) {
                        $query = $InstitutionTextbooks->find()
                                ->where([
                                    $InstitutionTextbooks->aliasField('student_id') => $tempRow['student_id'],
                                    $InstitutionTextbooks->aliasField('textbook_id') => $tempRow['textbook_id'],
                                    $InstitutionTextbooks->aliasField('institution_id') => $tempRow['institution_id'],
                                    $InstitutionTextbooks->aliasField('academic_period_id') => $tempRow['academic_period_id'],
                                    $InstitutionTextbooks->aliasField('education_subject_id') => $tempRow['education_subject_id'],
                                    $InstitutionTextbooks->aliasField('education_grade_id') => $tempRow['education_grade_id']
                                ])
                                ->count();
                        if ($query > 0) { //student assigned to same book before
                            $rowInvalidCodeCols['student_id'] = __('Textbook already assigned to the same student before.');
                            return false;
                        }
                    }
                }
            }
        }
        
        return true;
    }
}
