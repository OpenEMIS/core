<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionCommitteeAttachmentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'foreignKey' =>'institution_committee_id']);

        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]);
	}
}
