<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationCertificationsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
	}
}