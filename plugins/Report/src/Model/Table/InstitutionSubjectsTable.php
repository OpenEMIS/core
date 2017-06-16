<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionSubjectsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_subjects');
		parent::initialize($config);

		$this->belongsTo('AcademicPeriods', 			['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', 				['className' => 'Institution.Institutions']);
		$this->belongsTo('EducationSubjects', 			['className' => 'Education.EducationSubjects']);
		
		$this->addBehavior('Excel');
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
        $query
            ->contain(['Institutions.Areas', 'Institutions.AreaAdministratives'])
            ->select(['institution_code' => 'Institutions.code', 'area_code' => 'Areas.code', 'area_name' => 'Areas.name', 'area_administrative_code' => 'AreaAdministratives.code', 'area_administrative_name' => 'AreaAdministratives.name']);
    }

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions('Institutions');
		return $attr;
	}

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
    {
        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        foreach ($cloneFields as $key => $value) {
            $newFields[] = $value;
            if ($value['field'] == 'institution_id') {
                $newFields[] = [
                    'key' => 'Institutions.code',
                    'field' => 'institution_code',
                    'type' => 'string',
                    'label' => ''
                ];

                $newFields[] = [
                    'key' => 'Institutions.area_code',
                    'field' => 'area_code',
                    'type' => 'string',
                    'label' => __('Area Education Code')
                ];

                $newFields[] = [
                    'key' => 'Institutions.area',
                    'field' => 'area_name',
                    'type' => 'string',
                    'label' => __('Area Education')
                ];

                $newFields[] = [
                    'key' => 'AreaAdministratives.code',
                    'field' => 'area_administrative_code',
                    'type' => 'string',
                    'label' => __('Area Administrative Code')
                ];

                $newFields[] = [
                    'key' => 'AreaAdministratives.name',
                    'field' => 'area_administrative_name',
                    'type' => 'string',
                    'label' => __('Area Administrative')
                ];
            }
        }
        $fields->exchangeArray($newFields);
    }
}
