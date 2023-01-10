<?php
namespace App\Controller\Component;

use Exception;
use ArrayObject;
use Cake\I18n\Time;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class WorkBenchComponent extends Component {
	private $controller;
	private $action;
	private $Session;

	public $components = ['Auth', 'AccessControl', 'Workflow'];

	public function initialize(array $config) {
	}
}
