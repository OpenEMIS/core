<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		
		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
		
		$this->addBehavior('Excel', [
			'excludes' => ['is_student', 'is_staff', 'is_guardian', 'photo_name', 'super_admin', 'status'],
			'pages' => false
		]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.CustomFieldList', [
			'model' => 'Staff.Staff',
			'formFilterClass' => null,
			'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
		]);
	}

	public function beforeAction(Event $event) 
	{
		$this->fields = [];
		$this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('system_usage', ['type' => 'hidden']);
        $this->ControllerAction->field('status', ['type' => 'hidden']);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
		$this->ControllerAction->field('format');
	}
	
	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
        $attr['onChangeReload'] = true;
		return $attr;
	}

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffSalaries'])) {
                $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();

                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;

                if (empty($request->data[$this->alias()]['academic_period_id'])) {
                    reset($academicPeriodOptions);
                    $request->data[$this->alias()]['academic_period_id'] = key($academicPeriodOptions);
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldSystemUsage(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffSystemUsage'])) {
                $options = [
                    '1' => __('No previous login'),
                    '2' => __('Logged in within the last 7 days')
                ];
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
                return $attr;
            }
        }
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];

                if (in_array($feature, ['Report.StaffLicenses'])) {
                    $licenseStatuses = $this->Workflow->getWorkflowStatuses('Staff.Licenses');
                    $licenseStatuses = ['-1' => __('All Statuses')] + $licenseStatuses;

                    $attr['type'] = 'select';
                    $attr['select'] = false;
                    $attr['options'] = $licenseStatuses;
                    return $attr;
                }
            }
        }
    }

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$query->where([$this->aliasField('is_staff') => 1]);
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();
        
        foreach ($fields as $key => $field) { 
        	//get the value from the table, but change the label to become default identity type.
            if ($field['field'] == 'identity_number') { 
                $fields[$key] = [
                    'key' => 'Staff.identity_number',
                    'field' => 'identity_number',
                    'type' => 'string',
                    'label' => __($identity->name)
                ];
                break;
            }
        }
	}
}
