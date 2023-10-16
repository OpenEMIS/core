<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionTextbooksTable extends AppTable  {
    public function initialize(array $config) {
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

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('academic_period_id', ['select' => false]);
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->AcademicPeriods->getYearList();
        $attr['default'] = $this->AcademicPeriods->getCurrent();
        return $attr;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        /*POCOR-6296 starts*/
        $areaId = $requestData->area_id;
        $institutionId = $requestData->institution_id;
        if (!empty($institutionId) && $institutionId > 0) {
            $query->where([
                $this->aliasField('institution_id') => $institutionId
            ]);
        }
        if (!empty($areaId) && $areaId != -1) {
            $query->where([
                'Institutions.area_id' => $areaId
            ]);
        }
        /*POCOR-6296 ends*/
        if ($academicPeriodId != 0) {
            $query->where([
                $this->aliasField('academic_period_id') => $academicPeriodId
            ]);
        }

        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $institutionIds = [];
        if (!$superAdmin) {
            $InstitutionsTable = TableRegistry::get('Institution.Institutions');
            $instituitionData = $InstitutionsTable->find('byAccess', ['userId' => $userId])->toArray();
            if (isset($instituitionData)) {
                foreach ($instituitionData as $key => $value) {
                    $institutionIds[] = $value->id;
                }
            }
        }
        if ($institutionId == 0) {
            $query->where([
                $this->aliasField('institution_id IN') => $institutionIds
            ]);
        }

        $query->contain('Textbooks', 'Institutions');
        pr($query);
    }

    public function onExcelGetInstitutionId(Event $event, Entity $entity) {
        return $entity->institution->code_name;
    }

    public function onExcelGetTextbookId(Event $event, Entity $entity) {
        return $entity->textbook->code_title;
    }

    public function onExcelGetStudentId(Event $event, Entity $entity) {
        return $entity->user->name_with_id;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        foreach ($fields as $key => $field) {
            //get the value from the table, but change the label to become default identity type.
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
