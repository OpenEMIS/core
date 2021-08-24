<?php
namespace ReportCard\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;

class ReportCardProcessesTable extends ControllerActionTable
{
    const NEW_PROCESS = 1;
    const RUNNING = 2;
    const COMPLETED = 3;
    const ERROR = -1;

    public function initialize(array $config)
    {
        $this->table('report_card_processes');

        parent::initialize($config);

        $this->belongsTo('ReportCards', ['className' => 'ReportCard.ReportCards']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('search', false);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'institution_class_id') {
            return __('Class');
        } else if($field == 'student_id'){
            return __('OpenEMIS ID');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetStudentID(Event $event, Entity $entity)
    {
        if (isset($entity->student->openemis_no) && !empty($entity->student->openemis_no)) {
            return $entity->student->openemis_no;
        }
        return ' - ';
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        /*
        $query
        ->leftJoin(
            [$this->InstitutionClasses->alias() => $this->InstitutionClasses->table()],
            [$this->InstitutionClasses->aliasField('id = ') . $this->aliasField('institution_class_id')]
        )
        ->leftJoin(
            [$this->Institutions->alias() => $this->Institutions->table()],
            [$this->Institutions->aliasField('id = ') . $this->aliasField('institution_id')]
        )
        ->leftJoin(
            [$this->Students->alias() => $this->Students->table()],
            [$this->Students->aliasField('id = ') . $this->aliasField('student_id')]
        )
        ->select([
            'status' => $this->aliasField('status'),
            'institution_class_id' => $this->InstitutionClasses->aliasField('name'),
            'institution_id' => $this->Institutions->aliasField('name'),
            'student_id' => $this->Students->aliasField('openemis_no'),
        ]);
        */
        $query->order([$this->aliasField('status DESC')]);
        return $query;
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        $status = [
            '1'  => "New Process",
            '2'  => 'Running',
            '3'  => 'Completed',
            '-1' => 'Error'
        ];
        if (isset($status[$entity->status])) {
            return $status[$entity->status];
        }
        return 'Error';
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['institution_id']['visible']       = true;
        $this->fields['institution_class_id']['visible'] = true;
        $this->fields['student_id']['visible']           = true;
        $this->fields['status']['visible']               = true;

        $this->fields['report_card_id']['visible']       = false;
        $this->fields['education_grade_id']['visible']   = false;
        $this->fields['academic_period_id']['visible']   = false;
        $this->fields['created']['visible']              = false;

        $this->setFieldOrder([
            'institution_id',
            'institution_class_id',
            'student_id',
            'status'
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupNewTabElements();
    }

    private function setupNewTabElements()
    {
        $tabElements = $this->controller->getReportTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Processes');
    }
}
