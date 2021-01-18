<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;

class StaffExtracurricularsTable extends AppTable  {
	use OptionsTrait;

	public function initialize(array $config) {
		$this->table('staff_extracurriculars');
		parent::initialize($config);
	    $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('ExtracurricularTypes', ['className' => 'FieldOption.ExtracurricularTypes']);   
		$this->addBehavior('Excel');
		$this->addBehavior('Report.ReportList');
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
		
		$Staff = TableRegistry::get('Security.Users');
		
         $query
            ->select([
                'name' =>  $this->aliasfield('name'),
				'hours' =>  $this->aliasfield('hours'),
				'points' =>  $this->aliasfield('points'),
				'location' =>  $this->aliasfield('location'),
				'comment' =>  $this->aliasfield('comment'),
				'start_date' =>  $this->aliasfield('start_date'),
                'end_date' =>  $this->aliasfield('end_date'),
				'openemis_no' => $Staff->aliasfield('openemis_no'),
				'staff_name' => $Staff->find()->func()->concat([
                    $Staff->aliasfield('first_name') => 'literal',
                    " - ",
                    $Staff->aliasfield('last_name') => 'literal']),
            ])
			->contain([
				'ExtracurricularTypes' => [
                    'fields' => [
                        'extracurricular_type' => 'ExtracurricularTypes.name'
                    ]
                ],
				'AcademicPeriods' => [
                    'fields' => [
                        'academic_period' => 'AcademicPeriods.name'
                    ]
                ]
			])
			->leftJoin(
				[$Staff->alias() => $Staff->table()],
				[
					$Staff->aliasField('id = ') . $this->aliasField('staff_id')
				]
			)
			;
			 
    }
	
	public function onExcelRenderStartDate(Event $event, Entity $entity, $attr)
    {
        $start_date = $entity->date_from->format('Y-m-d');
        $entity->start_date = $start_date;
        return $entity->start_date;
    }

    public function onExcelRenderEndDate(Event $event, Entity $entity, $attr)
    {
        $end_date = $entity->end_date->format('Y-m-d');
        $entity->end_date = $end_date;
        return $entity->end_date;
    }
	
	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
    {   
        $cloneFields = $fields->getArrayCopy();

        $extraFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
		
        $extraFields[] = [
            'key' => 'staff_name',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Staff Name')
        ];
		
        $extraFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];
		
        $extraFields[] = [
            'key' => 'ExtracurricularTypes.name',
            'field' => 'extracurricular_type',
            'type' => 'string',
            'label' => __('Extracurricular Type')
        ];
		
        $extraFields[] = [
            'key' => '',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];
		
		$extraFields[] = [
            'key' => '',
            'field' => 'start_date',
            'type' => 'date',
            'label' => __('Start Date')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'end_date',
            'type' => 'date',
            'label' => __('End Date')
        ];
		
        $extraFields[] = [
            'key' => '',
            'field' => 'hours',
            'type' => 'string',
            'label' => __('Hours')
        ];		
		
        $extraFields[] = [
            'key' => '',
            'field' => 'points',
            'type' => 'string',
            'label' => __('Points')
        ];	
		
        $extraFields[] = [
            'key' => '',
            'field' => 'location',
            'type' => 'string',
            'label' => __('Location')
        ];
		
		$extraFields[] = [
            'key' => '',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
		
       $newFields = $extraFields;
       $fields->exchangeArray($newFields);
       
   }
	
}
