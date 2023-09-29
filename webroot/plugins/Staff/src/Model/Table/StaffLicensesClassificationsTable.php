<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;

class StaffLicensesClassificationsTable extends AppTable
{
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('StaffLicenses', ['className' => 'Staff.Licenses']);
		$this->belongsTo('LicenseClassifications', ['className' => 'FieldOption.LicenseClassifications']);

		$this->addBehavior('CompositeKey');
	}
}
