<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;

class AlertRuleStaffAttendanceBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'StaffAttendance', //POCOR-6584
        'name' => 'Staff Absent',
        'method' => ['Email','SMS'], // POCOR-8286
        'threshold' => [],
        'placeholder' => [
            // POCOR-9391 start
            '${total_times}' => 'Total number of absence.',
            '${total_days}' => 'Total days of absence.',
            '${threshold}' => 'Threshold (times) value.',
            '${staff.openemis_no}' => 'Staff OpenEMIS ID.',
            '${staff.name}' => 'Staff name.',
            '${staff.first_name}' => 'Staff first name.',
            '${staff.middle_name}' => 'Staff middle name.',
            '${staff.third_name}' => 'Staff third name.',
            '${staff.last_name}' => 'Staff last name.',
            '${staff.preferred_name}' => 'Staff preferred name.',
            '${staff.email}' => 'Staff email.',
            '${staff.address}' => 'Staff address.',
            '${staff.postal_code}' => 'Staff postal code.',
            '${staff.date_of_birth}' => 'Staff date of birth.',
            '${staff.identity_number}' => 'Staff identity number.',
            // '${user.photo_name}' => 'Staff photo name.',
            // '${user.photo_content}' => 'Staff photo content.',
            '${staff.main_identity_type}' => 'Staff identity type.',
            '${staff.main_nationality}' => 'Staff nationality.',
            '${staff.gender}' => 'Staff gender.',
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
                $validator->add('threshold', [
                    'ruleRange' => [
                        'rule' => ['range', 1, 30]
                    ]
                ]);
                $model->setValidator('forSave', $validator); // POCOR-8286

            }
        }
    }

    public function onStaffAttendanceSetupFields(EventInterface $event, Entity $entity)
    { ////echo "heey";die;
        $this->onAlertRuleSetupFields($event, $entity);

    }

    public function onUpdateFieldStaffAttendanceThreshold(EventInterface $event, array $attr, $action, ServerRequest $request)
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
        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }

        return $attr;
    }
}
