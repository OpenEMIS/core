<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class IncomeTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_incomes');
		parent::initialize($config);

		$this->addBehavior('Excel', [
            'autoFields' => false
        ]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;


        $startDate = (!empty($requestData->from_date))? date('Y-m-d',strtotime($requestData->from_date)): null;
        $endDate = (!empty($requestData->to_date))? date('Y-m-d',strtotime($requestData->to_date)): null;
      
        
        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId)) {
            $conditions['Institutions.id'] = $institutionId;
        }

        $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'academic_period' => 'AcademicPeriods.name',
                $this->aliasField('date'),
                $this->aliasField('amount'),
                'source' => 'IncomeSources.name',
                'income_type' => 'IncomeTypes.name',
            ])
            ->innerJoin(['Institutions' => 'institutions'], [
                'Institutions.id =' . $this->aliasField('institution_id')
            ])
            ->innerJoin(['IncomeTypes' => 'income_types'], [
                'IncomeTypes.id =' . $this->aliasField('income_type_id')
            ])
            ->innerJoin(['IncomeSources' => 'income_sources'], [
                'IncomeSources.id =' . $this->aliasField('income_source_id')
            ])
			->innerJoin(['AcademicPeriods' => 'academic_periods'], [
                'AcademicPeriods.id =' . $this->aliasField('academic_period_id')
            ])
            ->where($conditions)

            ->andWhere([$this->aliasField('date').' >= ' => $startDate, $this->aliasField('date').' <= ' => $endDate]);  
               
    }

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'integer',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
		
		$newFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $newFields[] = [
            'key' => 'InstitutionIncomes.date',
            'field' => 'date',
            'type' => 'date',
            'label' => __('Date')
        ];
		
        $newFields[] = [
            'key' => 'IncomeSources.name',
            'field' => 'source',
            'type' => 'string',
            'label' => __('Source')
        ];
		
        $newFields[] = [
            'key' => 'IncomeTypes.name',
            'field' => 'income_type',
            'type' => 'string',
            'label' => __('Type')
        ];
		
        $newFields[] = [
            'key' => 'InstitutionIncomes.amount',
            'field' => 'amount',
            'type' => 'string',
            'label' => __('Amount')
        ];

        $fields->exchangeArray($newFields);
    }
	
}
