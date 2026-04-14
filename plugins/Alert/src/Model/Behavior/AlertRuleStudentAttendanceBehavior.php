<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;

class AlertRuleStudentAttendanceBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StudentAttendance', //POCOR-6584
        'name' => 'Student Absent',
        'method' => ['Email','SMS'], // POCOR-8286
        'threshold' => [],
        'placeholder' => [
            // POCOR-9391 start
            '${total_times}' => 'Total number of absence.',
            '${total_days}' => 'Total days of absence.',
            '${threshold}' => 'Threshold (times) value.',
            '${student.openemis_no}' => 'Student OpenEMIS ID.',
            '${student.name}' => 'Student name.',
            '${student.first_name}' => 'Student first name.',
            '${student.middle_name}' => 'Student middle name.',
            '${student.third_name}' => 'Student third name.',
            '${student.last_name}' => 'Student last name.',
            '${student.preferred_name}' => 'Student preferred name.',
            '${student.email}' => 'Student email.',
            '${student.address}' => 'Student address.',
            '${student.postal_code}' => 'Student postal code.',
            '${student.date_of_birth}' => 'Student date of birth.',
            '${student.identity_number}' => 'Student identity number.',
            // '${user.photo_name}' => 'Student photo name.',
            // '${user.photo_content}' => 'Student photo content.',
            '${student.identity_type}' => 'Student identity type.',
            '${student.main_nationality}' => 'Student nationality.',
            '${student.gender}' => 'Student gender.',
            // POCOR-9391 end
            '${institution.name}' => 'Institution name.',
            '${institution.code}' => 'Institution code.',
            '${institution.address}' => 'Institution address.',
            '${institution.postal_code}' => 'Institution postal code.',
            '${institution.contact_person}' => 'Institution contact person.',
            '${institution.telephone}' => 'Institution telephone number.',
//            '${institution.fax}' => 'Institution fax number.',
            '${institution.email}' => 'Institution email.',
            '${institution.website}' => 'Institution website.',
        ]
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);

    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {

        $model = $this->_table;
        if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->alertRule) {
            if (isset($data['submit']) && $data['submit'] == 'save') {
                $validator = $model->getValidator();
                //POCOR-9509: start - validate threshold is required
                $validator->notEmptyString('threshold', __('Threshold cannot be empty'));
                //POCOR-9509: end
                $validator->add('threshold', [
                    'ruleRange' => [
                        'rule' => ['range', 1, 30]
                    ]
                ]);
                $model->setValidator('forSave', $validator); // POCOR-8286

            }
        }
    }

    public function onStudentAttendanceSetupFields(EventInterface $event, Entity $entity)
    { ////echo "heey";die;
        $this->onAlertRuleSetupFields($event, $entity);

    }

    public function onUpdateFieldStudentAttendanceThreshold(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['visible'] = true;

        // info tooltip
        $message = $this->_table->getmessage('AlertRules.Attendance.threshold');

        $attr['attr']['label']['escape'] = false;
        $attr['attr']['label']['class'] = 'tooltip-desc';
        $attr['attr']['label']['text'] = __('Threshold') . $this->tooltipMessage($message);
        // end of info tooltip

        if ($action == 'add') {
            $attr['type'] = 'integer';
            $attr['attr']['min'] = 1;
            $attr['attr']['max'] = 30;
            $attr['attr']['required'] = true; //POCOR-9509: mark threshold as required
        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }

        return $attr;
    }
}
