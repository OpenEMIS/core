<?php
namespace Report\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Report\Model\Table\ReportProgressTable as Process;
use Cake\I18n\I18n;

class ReportListBehavior extends Behavior {
	public $ReportProgress;

	public function initialize(array $config) {
		$this->ReportProgress = TableRegistry::get('Report.ReportProgress');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.add.beforeSave'] = 'addBeforeSave';
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$events['ControllerAction.Model.afterAction'] = 'afterAction';
		return $events;
	}

	public function afterAction(Event $event, $config) {
		if ($this->_table->action == 'index') {
			$this->_table->controller->set('ControllerAction', $config);
			return $this->_table->controller->render('Report.index');
		}
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$userId = $this->_table->Auth->user('id');
		// $this->ReportProgress->purge($userId, true);

		$settings['pagination'] = false;
		$fields = $this->_table->ControllerAction->getFields($this->ReportProgress);

		$fields['current_records']['visible'] = false;
		$fields['total_records']['visible'] = false;
		$fields['error_message']['visible'] = false;
		$fields['file_path']['visible'] = false;
		$fields['module']['visible'] = false;
		$fields['params']['visible'] = false;
		$fields['pid']['visible'] = false;
		$fields['created']['visible'] = true;
		$fields['modified']['visible'] = true;

		$this->_table->fields = $fields;

		$this->_table->ControllerAction->setFieldOrder(['name', 'created', 'modified', 'expiry_date', 'status']);

		// To remove expired reports
		$clonedQuery = $this->ReportProgress->find();
		$expiredReports = $clonedQuery
			->where([
				$this->ReportProgress->aliasField('module') => $this->_table->alias(), 
				$this->ReportProgress->aliasField('expiry_date').' < ' => date('Y-m-d')])
			->toArray();

		foreach($expiredReports as $report) {
			if (file_exists($report['file_path'])) {
				if (unlink($report['file_path'])) {
					$this->ReportProgress->delete($report);
				}
			} else {
				$this->ReportProgress->delete($report);
			}
		}

		$query = $this->ReportProgress->find()
			->where([$this->ReportProgress->aliasField('module') => $this->_table->alias()])
			->order([$this->ReportProgress->aliasField('expiry_date') => 'DESC']);

		return $query;
	}

	public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = ['xlsx' => 'Excel'];
		return $attr;
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$data[$this->_table->alias()]['locale'] = I18n::locale();
		$process = function($model, $entity) use ($data) {
			$this->_generate($data);
			return true;
		};
		return $process;
	}

	public function onExcelGenerate(Event $event, $writer, $settings) {
		$requestData = json_decode($settings['process']['params']);
		$locale = $requestData->locale;
		I18n::locale($locale);
	}

	public function onExcelStartSheet(Event $event, ArrayObject $settings, $totalCount) {
		$process = $settings['process'];
		$this->ReportProgress->updateAll(
			['total_records' => $totalCount],
			['id' => $process->id]
		);
	}

	public function onExcelBeforeWrite(Event $event, ArrayObject $settings, $rowProcessed, $percentCount) {
		$process = $settings['process'];
		if (($percentCount > 0 && $rowProcessed % $percentCount == 0) || $percentCount == 0)  {
			$this->ReportProgress->updateAll(
				['current_records' => $rowProcessed],
				['id' => $process->id]
			);
		}
	}

	public function onExcelEndSheet(Event $event, ArrayObject $settings, $totalProcessed) {
		$process = $settings['process'];
		$this->ReportProgress->updateAll(
			['current_records' => $totalProcessed],
			['id' => $process->id]
		);
	}

	public function onExcelGenerateComplete(Event $event, ArrayObject $settings) {
		$process = $settings['process'];
		$this->ReportProgress->updateAll(
			['status' => Process::COMPLETED, 'file_path' => $settings['file_path']],
			['id' => $process->id]
		);
	}

	protected function _generate($data) {
		$alias = $this->_table->alias();
		$featureList = $this->_table->fields['feature']['options'];
		$feature = $data[$alias]['feature'];
		$postFix = '';
		if (isset($data[$alias]['postfix'])) {
			$postFix = $data[$alias]['postfix'];
		}
		$table = TableRegistry::get($feature);

		// Event: 
		// $eventKey = 'Model.Report.onGetName';
		// $event = new Event($eventKey, $this, [$data]);
		// $event = $table->eventManager()->dispatch($event);
		// $name = $event->result;
		// End Event

		$name = $featureList[$feature];
		if (!empty($postFix)) {
			$name .= ' - '.$postFix;
		}
		$params = $data[$alias];

		$ReportProgress = TableRegistry::get('Report.ReportProgress');
		$obj = ['name' => $name, 'module' => $alias, 'params' => $params];

		$id = $ReportProgress->addReport($obj);
		if ($id !== false) {
			$ReportProgress->generate($id);
		}
	}

	public function download($id) {
		$this->_table->controller->autoRender = false;

		$entity = $this->ReportProgress->get($id);
		$path = $entity->file_path;
		if (!empty($path)) {
			$filename = basename($path);
			header("Pragma: public", true);
			header("Expires: 0"); // set expiration time
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			header("Content-Disposition: attachment; filename=".$filename);
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".filesize($path));
			echo file_get_contents($path);
		}
	}
}
