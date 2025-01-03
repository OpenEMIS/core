<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use Cake\Log\Log;
use Cake\I18n\FrozenTime;
use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;

class AppraisalsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_staff_appraisals');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
        $this->belongsTo('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods']);
        $this->hasMany('AppraisalTextAnswers', [
            'className' => 'StaffAppraisal.AppraisalTextAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            // 'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalSliderAnswers', [
            'className' => 'StaffAppraisal.AppraisalSliderAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            // 'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalDropdownAnswers', [
            'className' => 'StaffAppraisal.AppraisalDropdownAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            // 'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalNumberAnswers', [
            'className' => 'StaffAppraisal.AppraisalNumberAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            // 'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AppraisalScoreAnswers', [
            'className' => 'StaffAppraisal.AppraisalScoreAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->addBehavior('Institution.Appraisal');
        $this->addBehavior('Excel',[
            'excludes' => ['security_user_id'],
            'pages' => false,
        ]);

        $this->addBehavior('Report.ReportList');

        
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        
        $controllerName = $this->controller->getName();
        $institutions_crumb = __('Institutions');
        $parent_crumb       = __('Statistics');
		$reportName         = __('Standard');
        
        //# START: Crumb
        $this->Navigation->removeCrumb($this->getHeader($this->getAlias()));
        $this->Navigation->addCrumb($institutions_crumb . ' ' . $parent_crumb);
        //# END: Crumb
        $this->controller->set('contentHeader', __($institutions_crumb) . ' ' . $parent_crumb . ' - ' . $reportName);
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, ServerRequest $request)
    {
        $session = $this->request->getSession();
        $institution_id = $session->read('Institution.Institutions.id');
        $requestData = $request->getData($this->getAlias());
        $requestData['current_institution_id'] = $institution_id;
        $requestData['institution_id'] = $institution_id;
        if ($action == 'add') {
            $attr['value'] = 'xlsx';
            $attr['attr']['value'] = 'Excel';
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, ServerRequest $request)
    {
        $options = $this->controller->getInstitutionStatisticStandardReportFeature();
        $attr['options'] = $options;
        $attr['onChangeReload'] = true;
        $requestData = $request->getData($this->getAlias());
        if (!(isset($requestData['feature']))) {
            $option = $attr['options'];
            reset($option);
            $requestData['feature'] = key($option);
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $requestData = $request->getData($this->getAlias());
        if (isset($requestData['feature'])) {
            $feature                = $this->request->getData($this->getAlias())['feature'];
            $AcademicPeriodTable    = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions  = $AcademicPeriodTable->getYearList();
            $currentPeriod          = $AcademicPeriodTable->getCurrent();
            $attr['options']        = $academicPeriodOptions;
            $attr['type']           = 'select';
            $attr['select']         = false;
            $attr['onChangeReload'] = true;
            if (empty($requestData['academic_period_id'])) {
                $requestData['academic_period_id'] = $currentPeriod;
            }
            return $attr;
        }
    }

    public function onExcelRenderDate(Event $event, Entity $entity, $attr)
    {
        $field = $entity->{$attr['field']};
        
        if (!empty($field)) {
            if ($field instanceof FrozenTime || $field instanceof FrozenDate) {
                return $this->formatDate($field);
            } else {
                $date = new FrozenTime($field);
                return $this->formatDate($date);
            }
        } else {
            return $field;
        }
        
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $appraisal_form_id = $requestData->appraisal_form_id;
        $query->contain([
            'AppraisalPeriods.AcademicPeriods', 'AppraisalForms',
            'AppraisalTypes'
        ]); 
        $query->where([$this->aliasField('institution_id') => $institutionId]);
        $query->where(['AppraisalForms.id' => $appraisal_form_id]);
        $query->where(['AppraisalPeriods.academic_period_id' =>  $academicPeriodId]);
       
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $fields[] = [
            'key' => 'AppraisalPeriods.AcademicPeriods.AcademicPeriods',
            'field' => 'code',
            'type' => 'string',
            'label' => 'Academic Period',
        ];
    }

    public function onExcelGetCode(Event $event, Entity $entity)
    {
       return $entity->appraisal_period->academic_period->name;
    }

}