<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffSalariesTable extends AppTable {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->hasMany('SalaryAdditions', ['className' => 'Staff.SalaryAdditions', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SalaryDeductions', ['className' => 'Staff.SalaryDeductions', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Excel', [
            'excludes' => ['staff_id', 'comment']
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('AcademicPeriod.Period');
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;

        $query
            ->select(['openemis_no' => 'Users.openemis_no'])
            ->contain(['Users'])
            ->order([$this->aliasField('salary_date')]);

        if (!empty($academicPeriodId)) {
            $query->find('inPeriod', ['field' => 'salary_date', 'academic_period_id' => $academicPeriodId]);
        }
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
            'key' => 'StaffSalaries.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields = array_merge($newArray, $fields->getArrayCopy());

        $newArray = [];
        $newArray[] = [
            'key' => 'StaffSalaries.comment',
            'field' => 'comment',
            'type' => 'string',
            'label' => ''
        ];

        $newFields = array_merge($newFields, $newArray);
        $fields->exchangeArray($newFields);
    }
}
