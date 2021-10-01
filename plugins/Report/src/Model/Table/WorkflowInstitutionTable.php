<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowInstitutionTable extends AppTable
{

    public function initialize(array $config)
    {
        $this->table("institution_surveys");
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        /*POCOR-6296 starts*/
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $areaId = $requestData->area;
        $institutionId = $requestData->institution_id;
        $where = [];
        if ($institutionId > 0) {
            $where[$this->aliasField('institution_id')] = $institutionId;
        }
        if (!empty($areaId) && $areaId != -1) {
            $where[$this->aliasField('Institutions.area_id')] = $areaId;
        }
        if ($academicPeriodId != 0) {
            $where[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        /*POCOR-6296 ends*/
        $query
            ->where([
                $this->aliasField('status_id !=') => '-1',
                $where //POCOR-6269
            ]);
    }
}
