<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteAttachmentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->addBehavior('ControllerAction.FileUpload');

		$this->belongsTo('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
	}

	public function validationDefault(Validator $validator) {
		$validator->add('name', 'notBlank', [
			'rule' => 'notBlank'
		]);
		return $validator;
	}

	public function beforeAction() {
		$visibility = ['view' => true, 'edit' => true];
		
		$this->fields['file_name']['visible'] = false;
	}
}
