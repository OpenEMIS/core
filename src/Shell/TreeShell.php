<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Exception;

class TreeShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

	public function main() {
		$this->out('Initialize Tree Shell ...');

		$registryAlias = $this->args[0];

		try {
			list($plugin, $modelName) = explode(".", $registryAlias, 2);
			$model = TableRegistry::get($registryAlias);
			$processInfo = $plugin . ' > ' . $model->alias();
			$this->out($processInfo . ' - Processing ...');
			$model->recover();
			$this->out($processInfo . ' - End ...');
		} catch (\Exception $e) {
			$this->out('TreeShell > catch error message:');
			$this->out($e->getMessage());
		}
	}
}
