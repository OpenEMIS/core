<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffEmploymentTable extends AppTable {
    public function initialize(array $config)
    {
        $this->table('staff_employments');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('EmploymentTypes', ['className' => 'FieldOption.EmploymentTypes']);

        $this->addBehavior('Excel', [
            'excludes' => ['staff_id', 'employment_type_id']
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $query
            ->select(['openemis_no' => 'Users.openemis_no'])
            ->contain(['Users']);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newArray = [];

        $newArray[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newArray[] = [
            'key' => 'StaffEmployment.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newArray[] = [
            'key' => 'StaffEmployment.employment_type_id',
            'field' => 'employment_type_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields = array_merge($newArray, $fields->getArrayCopy());
        $fields->exchangeArray($newFields);
    }
}
