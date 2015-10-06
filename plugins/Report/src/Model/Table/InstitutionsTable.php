<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class InstitutionsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_sites');
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteLocalities', 		['className' => 'Institution.Localities']);
		$this->belongsTo('InstitutionSiteTypes', 			['className' => 'Institution.Types']);
		$this->belongsTo('InstitutionSiteOwnerships', 		['className' => 'Institution.Ownerships']);
		$this->belongsTo('InstitutionSiteStatuses', 		['className' => 'Institution.Statuses']);
		$this->belongsTo('InstitutionSiteSectors', 			['className' => 'Institution.Sectors']);
		$this->belongsTo('InstitutionSiteProviders', 		['className' => 'Institution.Providers']);
		$this->belongsTo('InstitutionSiteGenders', 			['className' => 'Institution.Genders']);
		$this->belongsTo('Areas', 							['className' => 'Area.Areas']);
		$this->belongsTo('AreaAdministratives', 			['className' => 'Area.AreaAdministratives']);
		
		$this->addBehavior('Excel', ['excludes' => ['security_group_id', 'institution_site_type_id'], 'pages' => false]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.CustomFieldList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}

	public function onGetReportName(Event $event, ArrayObject $data) {
		return __('Overview');
	}

	public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$filter = $this->getFilter('Institution.Institutions');
		$institutionSiteTypes = $this->getInstitutionType($filter);
		$InstitutionCustomFormFiltersTable = TableRegistry::get('InstitutionCustomField.InstitutionCustomFormsFilters');
		$filterKey = $this->getFilterKey($filter);
		// Get the custom fields columns
		foreach ($institutionSiteTypes as $key => $name) {

			// Getting the header
			$fields = $this->getCustomFields($InstitutionCustomFormFiltersTable, $key);
			$header = $fields['header'];
			$customField = $fields['customField'];

			// Getting the custom field values
			$customFieldValueTable = TableRegistry::get('InstitutionCustomField.InstitutionCustomFieldValues');
			$query = $this->find()->where([$this->aliasField($filterKey) => $key]);
			$data = $this->getCustomFieldValues($this, $customFieldValueTable, $customField, $filterKey, $key);

			// The excel spreadsheets
			$sheets[] = [
	    		'name' => __($name),
				'table' => $this,
				'query' => $this->find()->where([$this->aliasField($filterKey) => $key]),
				'additionalHeader' => $header,
				'additionalData' => $data,
	    	];
		}
	}

	/**
	 *	Function to get the institution site type base on the custom field filter specified
	 *
	 *	@param string $filter custom field filter
	 *	@return array The list of institution site types
	 */
	public function getInstitutionType($filter) {
		// Getting the institution type as the sheet name
		$types = TableRegistry::get($filter)->getList()->toArray();
		return $types;
	}
}
