<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;

class SalariesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_salaries');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->hasMany('StaffSalaryTransactions', ['className' => 'Staff.StaffSalaryTransactions', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportSalaries']);

        $this->addBehavior('Excel', [
            'pages' => ['index']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        if (!empty($this->staffId)) {
            $query->contain(['Users'])
                ->where([$this->aliasField('staff_id') => $this->staffId])
                ->select(['openemis_no' => 'Users.openemis_no']);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields[] = [
            'key' => 'Salaries.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Salaries.salary_date',
            'field' => 'salary_date',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Salaries.comment',
            'field' => 'comment',
            'type' => 'text',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Salaries.gross_salary',
            'field' => 'gross_salary',
            'type' => 'decimal',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Salaries.additions',
            'field' => 'additions',
            'type' => 'decimal',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Salaries.deductions',
            'field' => 'deductions',
            'type' => 'decimal',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Salaries.net_salary',
            'field' => 'net_salary',
            'type' => 'decimal',
            'label' => ''
        ];

        $fields->exchangeArray($newFields);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->Session;
        if ($session->check('Staff.Staff.id')) {
            $this->staffId = $session->read('Staff.Staff.id');
        }

        $this->fields['gross_salary']['attr'] = array('data-compute-variable' => 'true', 'data-compute-operand' => 'plus', 'maxlength' => 9);
        $this->fields['net_salary']['attr'] = array('data-compute-target' => 'true', 'readonly' => true);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $totalAddition = 0;
        $totalDeduction = 0;

        $SalaryAdditions = TableRegistry::get('Staff.StaffSalaryTransactions');
        $present = [];
        if ($entity->has('salary_additions')) {
            foreach ($entity->salary_additions as $key => $value) {
                if (!empty($value['amount'])) {
                    $totalAddition += $value->amount;
                }
                //echo "<pre>";print_r($value->{$SalaryAdditions->primaryKey()});die("ddd");
                if ($value['salary_addition_type_id']) {
                    $present[] = $value['salary_addition_type_id'];
                }
            }
        }
        $deleteOptions = [
            'staff_salary_id' => $entity->id,
        ];
        if (!empty($present)) {
            $deleteOptions[$SalaryAdditions->primaryKey().' NOT IN'] = $present;
        }
        $SalaryAdditions->deleteAll($deleteOptions);

        $SalaryDeductions = TableRegistry::get('Staff.StaffSalaryTransactions');
        $present = [];
        if ($entity->has('salary_deductions')) {
            foreach ($entity->salary_deductions as $key => $value) {
                if (!empty($value['amount'])) {
                    $totalDeduction += $value->amount;
                }
                if ($value['salary_deduction_type_id']) {
                    $present[] = $value['salary_deduction_type_id'];
                }
            }
        }
        $deleteOptions = [
            'staff_salary_id' => $entity->id,
        ];
        if (!empty($present)) {
            $deleteOptions[$SalaryDeductions->primaryKey().' NOT IN'] = $present;
        }
        $SalaryDeductions->deleteAll($deleteOptions);

        $data = ['additions' => $totalAddition, 'deductions' => $totalDeduction];

        $entity = $this->patchEntity($entity, $data);
    }
    //POCOR-5915
    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData) {
        if (!empty($requestData['Salaries']['salary_additions'])) {
            foreach ($requestData['Salaries']['salary_additions'] as $key => $value) {
                $StaffSalaryTransactions = TableRegistry::get('Staff.StaffSalaryTransactions');
                $data = $StaffSalaryTransactions->newEntity();
                $data->amount = $value['amount'];
                $data->salary_addition_type_id = $value['salary_addition_type_id'];
                $data->salary_deduction_type_id = 0;
                $data->staff_salary_id = $entity->id; //$requestData['Salaries']['staff_id'];
                $StaffSalaryTransactions->save($data);
            }
        }
        if (!empty($requestData['Salaries']['salary_deductions'])) {
            foreach ($requestData['Salaries']['salary_deductions'] as $key => $value) {
                $StaffSalaryTransactions = TableRegistry::get('Staff.StaffSalaryTransactions');
                $data = $StaffSalaryTransactions->newEntity();
                $data->amount = $value['amount'];
                $data->salary_addition_type_id = 0;
                $data->salary_deduction_type_id = $value['salary_deduction_type_id'];
                $data->staff_salary_id = $entity->id; //$requestData['Salaries']['staff_id'];
                $StaffSalaryTransactions->save($data);
            }
        }
    }
    //POCOR-5915
    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (!array_key_exists('salary_additions', $data[$this->alias()])) {
                $data[$this->alias()]['salary_additions'] = [];
            }
            if (!array_key_exists('salary_deductions', $data[$this->alias()])) {
                $data[$this->alias()]['salary_deductions'] = [];
            }
        }
    }


    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['gross_salary']['type'] = 'float';
        $this->fields['net_salary']['type'] = 'float';
        $this->fields['additions']['type'] = 'float';
        $this->fields['deductions']['type'] = 'float';
        $this->fields['comment']['visible'] = false;
        $this->setFieldOrder(['salary_date', 'gross_salary', 'additions', 'deductions', 'net_salary']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->order($this->aliasField('salary_date DESC'));
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'StaffSalaryTransactions'
        ]);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['additions']['visible'] = false;
        $this->fields['gross_salary']['type'] = 'string';
        $this->fields['deductions']['visible'] = false;

        $this->fields['net_salary']['type'] = 'string';

        //$this->fields['gross_salary']['attr']['step'] = 0.00;
        //$this->fields['gross_salary']['attr']['min'] = 0.00;
        $this->fields['gross_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';

        //$this->fields['net_salary']['attr']['step'] = 0.00;
        //$this->fields['net_salary']['attr']['min'] = 0.00;
        $this->fields['net_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';

        $SalaryAdditionType = TableRegistry::get('Staff.SalaryAdditionTypes')->getList();
        $SalaryDeductionType = TableRegistry::get('Staff.SalaryDeductionTypes')->getList();

        $this->field('addition_set', [
            'type' => 'element',
            'element' => 'Staff.salary_info',
            'visible' => true,
            'fieldName' => 'salary_additions',
            'operation' => 'add',
            'fieldOptions' => $SalaryAdditionType->toArray()
        ]);
        $this->field('deduction_set', [
            'type' => 'element',
            'element' => 'Staff.salary_info',
            'visible' => true,
            'fieldName' => 'salary_deductions',
            'operation' => 'deduct',
            'fieldOptions' => $SalaryDeductionType->toArray()
        ]);

        $this->setFieldOrder(['salary_date', 'gross_salary', 'net_salary', 'addition_set', 'deduction_set', 'comment']);
    }

    public function addEditOnAddRow(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $data[$this->alias()]['salary_additions'][] = ['amount' => '0.00'];
        $options['associated'] = [
            'StaffSalaryTransactions' => ['validate' => false],
            //'SalaryDeductions' => ['validate' => false]
        ];
        //echo "<pre>";print_r($options['associated']);die("1");
    }

    public function addEditOnDeductRow(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {//die("22");
        $data[$this->alias()]['salary_deductions'][] = ['amount' => '0.00'];
        $options['associated'] = [
            //'SalaryAdditions' => ['validate' => false],
            'StaffSalaryTransactions' => ['validate' => false]
        ];
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('gross_salary', 'ruleMoney', [
                'rule' => ['money']
            ])
            ->add('net_salary', 'ruleMoney', [
                'rule' => ['money']
            ])
        ;
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['gross_salary']['type'] = 'float';
        $this->fields['net_salary']['type'] = 'float';
        $this->fields['additions']['type'] = 'float';
        $this->fields['deductions']['type'] = 'float';
    }

    private function setupTabElements()
    {
        $nonSchoolController = ['Directories', 'Profiles'];
        if (in_array($this->controller->name, $nonSchoolController)) {
            $options = [
                'type' => 'staff'
            ];
            $tabElements = $this->controller->getStaffFinanceTabElements($options);
        } else {
            $tabElements = $this->controller->getFinanceTabElements();
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function afterAction(Event $event)
    {
        $this->setupTabElements();
    }
}
