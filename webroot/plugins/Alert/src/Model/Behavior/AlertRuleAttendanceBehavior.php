<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Alert\Model\Behavior\AlertRuleBehavior;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AlertRuleAttendanceBehavior extends AlertRuleBehavior
{
    protected $_defaultConfig = [
        'feature' => 'Attendance',
        'name' => 'Student Absent',
        'method' => 'Email',
        'threshold' => [],
        'placeholder' => [
            '${total_days}' => 'Total number of unexcused absence.',
            '${threshold}' => 'Threshold value.',
            '${user.openemis_no}' => 'Student OpenEMIS ID.',
            '${user.first_name}' => 'Student first name.',
            '${user.middle_name}' => 'Student middle name.',
            '${user.third_name}' => 'Student third name.',
            '${user.last_name}' => 'Student last name.',
            '${user.preferred_name}' => 'Student preferred name.',
            '${user.email}' => 'Student email.',
            '${user.address}' => 'Student address.',
            '${user.postal_code}' => 'Student postal code.',
            '${user.date_of_birth}' => 'Student date of birth.',
            '${user.identity_number}' => 'Student identity number.',
            // '${user.photo_name}' => 'Student photo name.',
            // '${user.photo_content}' => 'Student photo content.',
            '${user.main_identity_type.name}' => 'Student identity type.',
            '${user.main_nationality.name}' => 'Student nationality.',
            '${user.gender.name}' => 'Student gender.',
            '${institution.name}' => 'Institution name.',
            '${institution.code}' => 'Institution code.',
            '${institution.address}' => 'Institution address.',
            '${institution.postal_code}' => 'Institution postal code.',
            '${institution.contact_person}' => 'Institution contact person.',
            '${institution.telephone}' => 'Institution telephone number.',
            '${institution.fax}' => 'Institution fax number.',
            '${institution.email}' => 'Institution email.',
            '${institution.website}' => 'Institution website.',
        ]
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->alertRule) {
            if (isset($data['submit']) && $data['submit'] == 'save') {
                $validator = $model->validator();
                $validator->add('threshold', [
                    'ruleRange' => [
                        'rule' => ['range', 1, 30]
                    ]
                ]);
            }
        }
    }

    public function onAttendanceSetupFields(Event $event, Entity $entity)
    {
        $this->onAlertRuleSetupFields($event, $entity);
    }

    public function onUpdateFieldAttendanceThreshold(Event $event, array $attr, $action, Request $request)
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
