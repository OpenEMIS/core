<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use ArrayObject;


class ReportCardStatusesEmailTable extends ControllerActionTable
{
    private $alertTypeFeatures = [];

	public function initialize(array $config)
    {
    	$this->table('institution_class_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        $this->addBehavior('Institution.EmailReportCardStatuses');
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);
        $this->setupFields($event, $entity);
        // $this->field('keyword_remarks', ['visible' => false]);
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        // if ($action == 'add' || $action == 'edit') {
            $tableHeaders =[__('Keywords'), __('Remarks')];
            $tableCells = [];
            $fieldKey = 'keyword_remarks';

            // if (!empty($entity->feature)) {
                $featureKey = 'EmailReportCardStatuses';
                $alertTypeDetails = $this->getAlertTypeDetailsByFeature($featureKey);
                $placeholder = $alertTypeDetails[$featureKey]['placeholder'];
                // pr($placeholder);die;

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
            // }

            return $event->subject()->renderElement('ReportCards/' . $fieldKey, ['attr' => $attr]);
        // }
    }

    public function addAlertRuleType($newAlertRuleType, $_config) {
        $this->alertTypeFeatures[$newAlertRuleType] = $_config;
    }

    private function setupTabElements($entity)
    {
        $tabElements = $this->controller->getReportCardStatusesTab($entity->user->id);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    private function setupFields(Event $event, Entity $entity)
    {
    	$this->fields = [];
        $this->field('email_content', ['type' => 'section']);
        $this->field('keyword_remarks', ['type' => 'custom_criterias']);

        // $this->field('alert_features', ['type' => 'custom_criterias', 'after' => 'message']);

        // if ($entity->has('feature') && !empty($entity->feature)) {
        //     $event = $this->dispatchEvent('AlertRule.'.$entity->feature.'.SetupFields', [$entity], $this);
        //     if ($event->isStopped()) { return $event->result; }
        // }
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