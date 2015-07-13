<?php
namespace Localization\Controller;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
//use Cake\I18n\I18n;

class TranslationsController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->ControllerAction->model('Localization.Translations');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		$header = "Translations";

		// Setting a bread crumb
		$this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);

		// Setting a header
		$this->set('contentHeader', __($header));
	}

	// public function beforeAction(Event $event){
	// 	//$currentLocale = I18n.locale();
	// 	$this->ControllerAction->field("eng");
	// }

	// public function indexBeforeAction(Event $event){
	// 	// // Getting the elements for the toolbar
	// 	// // need to make a controls.ctp
	// 	// $toolbarElements = [
	// 	// 	['name' => 'Translation.controls', 'data' => [], 'options' => []]
	// 	// ];

	// 	// $this->ControllerAction->setFieldOrder([
	// 	// 	// DefaultLocale and Selected option from the toolbarElements
	// 	// ]);
	// }

	// // public function index(){

	// // }

	// public function onInitialize(Event $event, $model) {
		
	// }
}
?>