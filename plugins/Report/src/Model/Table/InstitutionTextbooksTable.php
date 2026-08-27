<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionTextbooksTable extends AppTable  {
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->belongsTo('Textbooks', ['className' => 'Textbook.Textbooks', 'foreignKey' => ['textbook_id', 'academic_period_id']]);
        $this->belongsTo('TextbookStatuses', ['className' => 'Textbook.TextbookStatuses']);
        $this->belongsTo('TextbookConditions', ['className' => 'Textbook.TextbookConditions']);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);

        $this->addBehavior('Excel', [
            'excludes' => ['id'],
            'pages' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('academic_period_id', ['select' => false]);
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, Request $request) {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->AcademicPeriods->getYearList();
        $attr['default'] = $this->AcademicPeriods->getCurrent();
        return $attr;
    }

    private function parseFilterInstitutionIds($institutionId)
    {
        $filterInstitutionIds = [];
        if (is_object($institutionId) && isset($institutionId->_ids)) {
            $filterInstitutionIds = array_values(array_filter((array)$institutionId->_ids, function ($id) {
                return $id !== '' && $id !== null && $id !== '0' && $id !== 0;
            }));
        } elseif (is_array($institutionId) && isset($institutionId['_ids'])) {
            $filterInstitutionIds = array_values(array_filter((array)$institutionId['_ids'], function ($id) {
                return $id !== '' && $id !== null && $id !== '0' && $id !== 0;
            }));
        } elseif (!empty($institutionId) && $institutionId != 0 && $institutionId != '0' && !is_array($institutionId)) {
            $filterInstitutionIds = [(int)$institutionId];
        }

        return $filterInstitutionIds;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $areaId = $requestData->area_id;
        $institutionId = $requestData->institution_id;
        $filterInstitutionIds = $this->parseFilterInstitutionIds($institutionId);

        if (!empty($filterInstitutionIds)) {
            $query->where([
                $this->aliasField('institution_id') . ' IN' => $filterInstitutionIds
            ]);
        } else {
            $superAdmin = $requestData->super_admin;
            $userId = $requestData->user_id;
            if (!$superAdmin) {
                $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
                $institutionIds = [];
                $instituitionData = $InstitutionsTable->find('byAccess', ['userId' => $userId])->toArray();
                foreach ($instituitionData as $value) {
                    $institutionIds[] = $value->id;
                }
                if (!empty($institutionIds)) {
                    $query->where([
                        $this->aliasField('institution_id') . ' IN' => $institutionIds
                    ]);
                }
            }
        }

        if (!empty($areaId) && $areaId != -1) {
            $query->where([
                'Institutions.area_id' => $areaId
            ]);
        }

        if ($academicPeriodId != 0) {
            $query->where([
                $this->aliasField('academic_period_id') => $academicPeriodId
            ]);
        }

        $query->contain(['Textbooks', 'Institutions']);
    }

    public function onExcelGetInstitutionId(EventInterface $event, Entity $entity) {
        return $entity->institution->code_name;
    }

    public function onExcelGetTextbookId(EventInterface $event, Entity $entity) {
        return $entity->textbook->code_title;
    }

    public function onExcelGetStudentId(EventInterface $event, Entity $entity) {
        return $entity->user->name_with_id;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        foreach ($fields as $key => $field) {
            if ($field['field'] == 'textbook_id') {
                $fields[$key] = [
                    'key' => 'Textbooks.title',
                    'field' => 'textbook_id',
                    'type' => 'string',
                    'label' => 'Textbook'
                ];
                break;
            }
        }
    }
}
