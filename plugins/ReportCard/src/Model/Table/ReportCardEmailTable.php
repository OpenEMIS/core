<?php
namespace ReportCard\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;


class ReportCardEmailTable extends ControllerActionTable
{
    private $alertTypeFeatures = [];

	public function initialize(array $config)
    {
    	$this->table('report_cards');
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ReportCardSubjects', ['className' => 'ReportCard.ReportCardSubjects', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']);
        $this->hasMany('StudentReportCards', ['className' => 'Institution.InstitutionStudentsReportCards', 'dependent' => true, 'cascadeCallbacks' => true]);

        // Main table should be email_template.

        // $this->hasOne('email_templates', ['className' => 'ReportCard.ReportCardEmail', 'foreignKey' => 'id']);

        // pr($this);
        // pr('----------------------');
        // pr($this->find()->first()->toArray());die;

        /*
        $this->hasMany('email_templates', ['className' => 'ReportCard.TableName???']);
        
        Q1) How to link to the email_templates table ?

        Q2) Why when I try $this->table('email_templates') they undefined index must I create an entity in the entity folder for this email_template table?

        Q3) Shouldnt be the model_alias be "ReportCardEmail" ? How come "ReportCard.ReportCardEmail" << How they determine the value before the DOT?


        */

        parent::initialize($config);

        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('ReportCard.EmailTemplate');
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra) {
        // $this->field('description', ['visible' => false]);
        // $this->field('start_date', ['visible' => false]);
        // $this->field('end_date', ['visible' => false]);
        $this->field('principal_comments_required', ['visible' => false]);
        $this->field('homeroom_teacher_comments_required', ['visible' => false]);
        $this->field('teacher_comments_required', ['visible' => false]);
        $this->field('excel_template_name', ['visible' => false]);
        $this->field('excel_template', ['visible' => false]);
        // $this->field('academic_period_id', ['visible' => false]);
        // $this->field('education_grade_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);
        $this->setupFields($event, $entity);

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        unset($toolbarButtonsArray['remove']);
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->field('keyword_remarks', ['visible' => false]);
    }

    public function editBeforeAction(Event $event, ArrayObject $extra) {
        $this->field('description', ['type' => 'readonly', 'after' => 'name']);
        $this->field('start_date', ['type' => 'readonly', 'after' => 'description', 'attr' => ['required' => false]]);
        $this->field('end_date', ['type' => 'readonly', 'after' => 'start_date', 'attr' => ['required' => false]]);
        $this->field('academic_period_id', ['type' => 'readonly', 'after' => 'end_date', 'attr' => ['required' => false]]);
        $this->field('education_grade_id', ['type' => 'readonly', 'after' => 'academic_period_id', 'attr' => ['required' => false]]);

        $this->field('principal_comments_required', ['visible' => false]);
        $this->field('homeroom_teacher_comments_required', ['visible' => false]);
        $this->field('teacher_comments_required', ['visible' => false]);
        $this->field('excel_template_name', ['visible' => false]);
        $this->field('excel_template', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);
        $this->setupFields($event, $entity);
    }
    
    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'edit') {
            $tableHeaders =[__('Keywords'), __('Remarks')];
            $tableCells = [];
            $fieldKey = 'keyword_remarks';

            $featureKey = 'ReportCardEmail';
            $alertTypeDetails = $this->getAlertTypeDetailsByFeature($featureKey);
            $placeholder = $alertTypeDetails[$featureKey]['placeholder'];

            if (!empty($placeholder)) {
                foreach ($placeholder as $placeholderKey => $placeholderObj) {
                    $rowData = [];
                    $rowData[] = __($placeholderKey);
                    $rowData[] = __($placeholderObj);

                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            return $event->subject()->renderElement($fieldKey, ['attr' => $attr]);
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $startEndDate = $this
                        ->find()
                        ->where([
                            'id' => $event->data['data']['id']
                        ])
                        ->select([
                            'start_date',
                            'end_date'
                        ])
                        ->first();

        $data['start_date'] = $startEndDate->start_date->format('Y-m-d');
        $data['end_date'] = $startEndDate->end_date->format('Y-m-d');
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $reportCardId = $this->paramsDecode($request->params['pass'][1]);

        $academicPeriodEntity = $this->find()->where(['ReportCardEmail.id' => $reportCardId['id']])->contain(['AcademicPeriods'])->first();

        $attr['attr']['value'] = $academicPeriodEntity->academic_period->name;

        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $reportCardId = $this->paramsDecode($request->params['pass'][1]);

        $academicGradeEntity= $this->find()->where(['ReportCardEmail.id' => $reportCardId['id']])->contain(['EducationGrades'])->first();

        $attr['attr']['value'] = $academicGradeEntity->education_grade->name;

        return $attr;
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
    {
        $reportCardId = $this->paramsDecode($request->params['pass'][1]);

        $startDateEntity= $this->find()->where(['ReportCardEmail.id' => $reportCardId['id']])->first();

        $startDate = new Date($startDateEntity->start_date);
        $startDate = $this->formatDate($startDate);

        $attr['attr']['value'] = $startDate;

        return $attr;
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
    {
        $reportCardId = $this->paramsDecode($request->params['pass'][1]);

        $endDateEntity= $this->find()->where(['ReportCardEmail.id' => $reportCardId['id']])->first();

        $endDateEntity = new Date($endDateEntity->end_date);
        $endDateEntity = $this->formatDate($endDateEntity);

        $attr['attr']['value'] = $endDateEntity;

        return $attr;
    }

    public function addAlertRuleType($newAlertRuleType, $_config) {
        $this->alertTypeFeatures[$newAlertRuleType] = $_config;
    }

    private function setupTabElements($entity)
    {
        $tabElements = $this->controller->getReportCardTab($entity->id);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    private function setupFields(Event $event, Entity $entity)
    {
        $this->field('report_card_information', ['type' => 'section']);
        $this->field('code', ['type' => 'readonly', 'attr' => ['required' => false]]);
        $this->field('name', ['type' => 'readonly', 'attr' => ['required' => false]]);
        $this->field('email_content', ['type' => 'section', 'after' => 'education_grade_id']);
        $this->field('subject', ['type' => 'string', 'after' => 'email_content']);
        $this->field('message', ['type' => 'text', 'after' => 'subject']);
        $this->field('keyword_remarks', ['type' => 'custom_criterias', 'after' => 'message']);
    }

    private function getAlertTypeDetailsByFeature($feature)
    {
        $alertTypeDetails = [];
        foreach ($this->alertTypeFeatures as $key => $obj) {
            if ($obj['feature'] == $feature) {
                $alertTypeDetails[$obj['feature']] = $obj;
            }
        }

        return $alertTypeDetails;
    }
}