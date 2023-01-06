<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class InstitutionAttachmentTypesTable extends ControllerActionTable {
	// public function initialize(array $config) {
	// 	parent::initialize($config);
	// 	//$this->hasMany('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'foreignKey' =>'institution_committee_type_id']);
	// 	$this->addBehavior('FieldOption.FieldOption');
	// }
	public function initialize(array $config)
    {
        $this->table('institution_attachment_types');
        parent::initialize($config);

        // $this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'foreignKey' => 'student_behaviour_category_id']);

        // $this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index']
        ]);
    }
}
