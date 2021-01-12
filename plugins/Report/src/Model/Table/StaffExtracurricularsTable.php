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
	       
		$this->addBehavior('Excel', ['excludes' => ['id', 'comment', 'start_year', 'end_year', 'institution_programme_id']]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);

         $query
            ->select([
                'name' =>  $this->aliasfield('name'),
				'hours' =>  $this->aliasfield('hours'),
				'points' =>  $this->aliasfield('points'),
				'location' =>  $this->aliasfield('location'),
				'comment' =>  $this->aliasfield('comment'),
				'start_date' =>  $this->aliasfield('start_date'),
                'end_date' =>  $this->aliasfield('end_date'),
             ]);
    }
	
	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions('Institutions');
		return $attr;
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
