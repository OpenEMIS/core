<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

use App\Model\Traits\OptionsTrait;

class PerformanceTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('report_assessment_missing_mark_entry');
        parent::initialize($config);

        //associations
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Providers', ['className' => 'Institution.Providers', 'foreignKey' => 'institution_provider_id']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->alias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('area_id');
        $this->ControllerAction->field('institution_id');
        $this->ControllerAction->field('education_grade_id');
        $this->ControllerAction->field('assessment_period_id');
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('academic_period_name', ['type' => 'hidden']);
        $this->ControllerAction->field('assessment_id', ['type' => 'hidden']);
        $this->ControllerAction->field('assessment_code', ['type' => 'hidden']);
        $this->ControllerAction->field('assessment_name', ['type' => 'hidden']);
        $this->ControllerAction->field('assessment_period_name', ['type' => 'hidden']);
        $this->ControllerAction->field('education_grade', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_code', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_name', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_provider_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_provider', ['type' => 'hidden']);
        $this->ControllerAction->field('area_name', ['type' => 'hidden']);
        $this->ControllerAction->field('count_students', ['type' => 'hidden']);
        $this->ControllerAction->field('count_marked_students', ['type' => 'hidden']);
        $this->ControllerAction->field('missing_marks', ['type' => 'hidden']);
    }
}