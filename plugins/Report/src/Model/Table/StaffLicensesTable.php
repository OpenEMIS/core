<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffLicensesTable extends AppTable  {
    public function initialize(array $config)
    {
        $this->table('staff_licenses');
        parent::initialize($config);
        $this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('LicenseTypes', ['className' => 'FieldOption.LicenseTypes']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsToMany('Classifications', [
            'className' => 'FieldOption.LicenseClassifications',
            'joinTable' => 'staff_licenses_classifications',
            'foreignKey' => 'staff_license_id',
            'targetForeignKey' => 'license_classification_id',
            'through' => 'Staff.StaffLicensesClassifications',
            'dependent' => true
        ]);

        $this->addBehavior('Excel', [
            'excludes' => ['status_id', 'assignee_id', 'staff_id', 'license_type_id'],
            'pages' => false,
            'orientation' => 'landscape'
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;

        $query
            ->select(['openemis_no' => 'Users.openemis_no'])
            ->contain(['Users', 'Classifications'])
            ->order([$this->aliasField('staff_id')]);

        if ($selectedStatus != '-1') {
            $query
                ->matching('WorkflowSteps.WorkflowStatuses', function ($q) use ($selectedStatus) {
                    return $q->where(['WorkflowStatuses.id' => $selectedStatus]);
                });
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newArray = [];

        $newArray[] = [
            'key' => 'StaffLicenses.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newArray[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => '',
        ];

        $newArray[] = [
            'key' => 'StaffLicenses.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newArray[] = [
            'key' => 'Classifications.name',
            'field' => 'classification',
            'type' => 'string',
            'label' => __('Classification'),
        ];

        $newArray[] = [
            'key' => 'StaffLicenses.license_type_id',
            'field' => 'license_type_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields = array_merge($newArray, $fields->getArrayCopy());
        $fields->exchangeArray($newFields);
    }

    public function onExcelGetClassification(Event $event, Entity $entity)
    {
        if ($entity->has('classifications')) {
            $classifications = [];
            foreach ($entity->classifications as $obj) {
                $classifications[] = $obj->name;
            }
            return implode(', ', $classifications);
        } else {
            return '';
        }
    }
}
