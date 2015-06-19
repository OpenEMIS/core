<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteActivitiesTable extends AppTable {
	public function initialize(array $config) {
        parent::initialize($config);

		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey'=>'institution_site_id']);
		$this->belongsTo('CreatedUser',  ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
    }

    // Used in ActivityComponent
	// public function getConditionsForActivity() {
	// 	$id = CakeSession::read('InstitutionSite.id');
	// 	$conditions = array($this->alias . '.institution_site_id' => $id);
	// 	return $conditions;
	// }

	/**
	 * Redirect to index if navigating to other actions. Not working yet.
	 */
	// public function beforeFind($event) {
	// 	if ($this->action != 'index') {
	// 		$url = ['plugin'	 => 'Institution',
 //                    'controller' => 'Institutions',
 //                    'action' 	 => 'History',
 //                    0 			 => 'index'];
	// 		$this->controller->redirect($url);
	// 	}
	// }

	public function beforeAction(Event $event) {
		$this->fields['operation']['visible'] = false;
		$this->fields['model_reference']['visible'] = false;
		$this->fields['created_user_id']['visible'] = true;
		$this->fields['created']['visible'] = true; 
	}
}
