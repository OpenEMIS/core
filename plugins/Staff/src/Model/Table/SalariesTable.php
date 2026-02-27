<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;

class SalariesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('staff_salaries');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->hasMany('SalaryAdditions', ['className' => 'Staff.StaffSalaryTransactions', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SalaryDeductions', ['className' => 'Staff.StaffSalaryTransactions', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SalaryTransactions', ['className' => 'Staff.StaffSalaryTransactions', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportSalaries']);

        $this->addBehavior('Excel', [
            'pages' => ['index']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Salaries'=>['id']]
        ]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Salaries'=>['id']]
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        if (!empty($this->staffId)) {
            $query->contain(['Users'])
                ->where([$this->aliasField('staff_id') => $this->staffId])
                ->select(['openemis_no' => 'Users.openemis_no']);
        }
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
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

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        //$session = $this->Session;
        //if ($session->check('Staff.Staff.id')) {
        //    $this->staffId = $session->read('Staff.Staff.id');
        //}
        $this->staffId = $this->getStaffID();
        $this->fields['gross_salary']['attr'] = array('data-compute-variable' => 'true', 'data-compute-operand' => 'plus', 'maxlength' => 9);
        $this->fields['net_salary']['attr'] = array('data-compute-target' => 'true', 'readonly' => true);

        if($this->request->getAttribute('params')['controller'] == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Personal','Salaries','Staff - Finance');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        }elseif($this->request->getAttribute('params')['controller'] == 'Directories'){
            $is_manual_exist = $this->getManualUrl('Directory','Salary List','Staff - Finance');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }
        $queryString = $this->getQueryString();
        $data['staff_id'] = $queryString['staff_id'];
        $this->field('staff_id', ['type' => 'hidden', 'value' => $data['staff_id']]);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $totalAddition = 0;
        $totalDeduction = 0;

        //POCOR-9584: Log salary additions data
        if ($entity->has('salary_additions')) {
            \Cake\Log\Log::debug('[POCOR-9584] beforeSave - salary_additions: ' . json_encode($entity->salary_additions));
        } else {
            \Cake\Log\Log::debug('[POCOR-9584] beforeSave - No salary_additions property');
        }

        $SalaryAdditions = TableRegistry::getTableLocator()->get('Staff.SalaryAdditions');
        $present = [];
        if ($entity->has('salary_additions')) {
            foreach ($entity->salary_additions as $key => $value) {
                if ($value->has('amount')) {
                    $totalAddition += $value->amount;
                }
                if ($value->has($SalaryAdditions->getPrimaryKey())) {
                    $present[] = $value->{$SalaryAdditions->getPrimaryKey()};
                }
            }
        }
        if(!empty($entity->id)){
            $deleteOptions = [
                'staff_salary_id' => $entity->id,
            ];
        }
        if (!empty($present)) {
            $deleteOptions[$SalaryAdditions->getPrimaryKey().' NOT IN'] = $present;
        }
        //POCOR-9584: Log deletion of additions
        \Cake\Log\Log::debug('[POCOR-9584] beforeSave - Deleting old salary_additions with options: ' . json_encode($deleteOptions ?? []));
        $SalaryAdditions->deleteAll($deleteOptions ?? []);

        //POCOR-9584: Log salary deductions data
        if ($entity->has('salary_deductions')) {
            \Cake\Log\Log::debug('[POCOR-9584] beforeSave - salary_deductions: ' . json_encode($entity->salary_deductions));
        } else {
            \Cake\Log\Log::debug('[POCOR-9584] beforeSave - No salary_deductions property');
        }

        $SalaryDeductions = TableRegistry::getTableLocator()->get('Staff.SalaryDeductions');
        $present = [];
        if ($entity->has('salary_deductions')) {
            foreach ($entity->salary_deductions as $key => $value) {
                if ($value->has('amount')) {
                    $totalDeduction += $value->amount;
                }
                if ($value->has($SalaryDeductions->getPrimaryKey())) {
                    $present[] = $value->{$SalaryDeductions->getPrimaryKey()};
                }
            }
        }
        if(!empty($entity->id)){
            $deleteOptions = [
                'staff_salary_id' => $entity->id,
            ];
        }
        if (!empty($present)) {
            $deleteOptions[$SalaryDeductions->getPrimaryKey().' NOT IN'] = $present;
        }
        //POCOR-9584: Log deletion of deductions
        \Cake\Log\Log::debug('[POCOR-9584] beforeSave - Deleting old salary_deductions with options: ' . json_encode($deleteOptions ?? []));
        $SalaryDeductions->deleteAll($deleteOptions ?? []);

        //POCOR-9584: Log totals being saved
        \Cake\Log\Log::debug('[POCOR-9584] beforeSave - Total Addition: ' . $totalAddition . ', Total Deduction: ' . $totalDeduction);

        $data = ['additions' => $totalAddition, 'deductions' => $totalDeduction];

        $entity = $this->patchEntity($entity, $data);
    }

    public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        if (array_key_exists($this->getAlias(), (array) $data)) { //POCOR-9300[START] added array in second param
            if (!array_key_exists('salary_additions', $data[$this->getAlias()])) {
                $data[$this->getAlias()]['salary_additions'] = [];
            }
            if (!array_key_exists('salary_deductions', $data[$this->getAlias()])) {
                $data[$this->getAlias()]['salary_deductions'] = [];
            }
        }
    }


    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['gross_salary']['type'] = 'float';
        $this->fields['net_salary']['type'] = 'float';
        $this->fields['additions']['type'] = 'float';
        $this->fields['deductions']['type'] = 'float';
        $this->fields['comment']['visible'] = false;
        $this->setFieldOrder(['salary_date', 'gross_salary', 'additions', 'deductions', 'net_salary']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->order($this->aliasField('salary_date DESC'));
    }

    public function editBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        //$paramsPass = $this->paramsDecode($this->ControllerAction->getParam('Pass')[1]);cakephp 3
        $paramsPass = $this->paramsDecode($this->request->getParam('pass.1'));
        $SalaryTransactions = TableRegistry::getTableLocator()->get('Staff.StaffSalaryTransactions');
        $findData = $SalaryTransactions->find()
                    ->select([
                      $SalaryTransactions->aliasField('salary_addition_type_id'),
                      $SalaryTransactions->aliasField('salary_deduction_type_id')
                    ])
                    ->where([$SalaryTransactions->aliasField('staff_salary_id') => $paramsPass['id']])->toArray();

        $addition  = $deduction = [];
        if (!empty($findData)) {
            foreach ($findData as $key => $value) {
               $addition[] = $value->salary_addition_type_id;
               $deduction[] = $value->salary_deduction_type_id;
            }
            //POCOR-9584: Filter out null values and check if arrays have any non-empty elements
            $addition = array_filter($addition);
            $deduction = array_filter($deduction);

            //POCOR-9584: Log the data being loaded
            \Cake\Log\Log::debug('[POCOR-9584] editBeforeQuery - Salary ID: ' . $paramsPass['id']);
            \Cake\Log\Log::debug('[POCOR-9584] editBeforeQuery - Addition IDs: ' . json_encode($addition));
            \Cake\Log\Log::debug('[POCOR-9584] editBeforeQuery - Deduction IDs: ' . json_encode($deduction));

            if (!empty($addition) && empty($deduction)) {
                \Cake\Log\Log::debug('[POCOR-9584] editBeforeQuery - Loading only SalaryAdditions');
                $query->contain([
                    'SalaryAdditions'
                ]);
            } elseif (empty($addition) && !empty($deduction)) {
                \Cake\Log\Log::debug('[POCOR-9584] editBeforeQuery - Loading only SalaryDeductions');
                $query->contain([
                    'SalaryDeductions'
                ]);
            } else {
                \Cake\Log\Log::debug('[POCOR-9584] editBeforeQuery - Loading both SalaryAdditions and SalaryDeductions');
                $query->contain([
                    'SalaryAdditions',
                    'SalaryDeductions'
                ]);
            }
        } else {
            \Cake\Log\Log::debug('[POCOR-9584] editBeforeQuery - No salary transactions found for ID: ' . $paramsPass['id']);
        }
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['additions']['visible'] = false;
        $this->fields['deductions']['visible'] = false;

        $this->fields['gross_salary']['type'] = 'string';
        $this->fields['net_salary']['type'] = 'string';

        //$this->fields['gross_salary']['attr']['step'] = 0.00;
        //$this->fields['gross_salary']['attr']['min'] = 0.00;
        $this->fields['gross_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';

        //$this->fields['net_salary']['attr']['step'] = 0.00;
        //$this->fields['net_salary']['attr']['min'] = 0.00;
        $this->fields['net_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';

        $SalaryAdditionType = TableRegistry::getTableLocator()->get('Staff.SalaryAdditionTypes')->getList();
        $SalaryDeductionType = TableRegistry::getTableLocator()->get('Staff.SalaryDeductionTypes')->getList();

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

    public function addEditOnAddRow(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $data[$this->getAlias()]['salary_additions'][] = ['amount' => '0.00'];
        $options['associated'] = [
            'SalaryAdditions' => ['validate' => false],
            //'SalaryDeductions' => ['validate' => false]
        ];
        //echo "<pre>";print_r( $options);die();
    }

    public function addEditOnDeductRow(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $data[$this->getAlias()]['salary_deductions'][] = ['amount' => '0.00'];
        $options['associated'] = [
            //'SalaryAdditions' => ['validate' => false],
            'SalaryDeductions' => ['validate' => false]
        ];
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->add('gross_salary', 'ruleMoney', [
                'rule' => ['money']
            ])
            ->add('net_salary', 'ruleMoney', [
                'rule' => ['money']
            ])
        ;
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['gross_salary']['type'] = 'float';
        $this->fields['net_salary']['type'] = 'float';
        $this->fields['additions']['type'] = 'float';
        $this->fields['deductions']['type'] = 'float';
    }

    private function setupTabElements()
    {
        $nonSchoolController = ['Directories', 'Profiles'];
        if (in_array($this->controller->getName(), $nonSchoolController)) {
            $options = [
                'type' => 'staff'
            ];
            $tabElements = $this->controller->getStaffFinanceTabElements($options);
        } else {
            $tabElements = $this->controller->getFinanceTabElements();
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function afterAction(EventInterface $event)
    {
        $this->setupTabElements();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        $LabelTable = TableRegistry::getTableLocator()->get('Labels'); // POCOR-9525

        if ($field == 'salary_date') {
            return __('Salary Date');
        } elseif ($field == 'gross_salary') { // POCOR-9525 start
            $label = $LabelTable->find()->where(['module' => 'InstitutionStaffFinanceSalaries', 'field' => 'gross_salary'])->first();
            if (!empty($label) && $label->name) {
                return $label->name;
            } else {
            return __('Gross Salary');
            }
        } elseif ($field == 'net_salary') {
            $label = $LabelTable->find()->where(['module' => 'InstitutionStaffFinanceSalaries', 'field' => 'net_salary'])->first();
            if (!empty($label) && $label->name) {
                return $label->name;
            } else {
                return __('Net Salary');
            }
            // POCOR-9525 end
        } elseif ($field == 'comment') {
            return __('Comment');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}
