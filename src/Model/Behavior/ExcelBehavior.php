<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\EventTrait;

// 3rd party xlsx writer library
require_once(ROOT . DS . 'vendor' . DS  . 'XLSXWriter' . DS . 'xlsxwriter.class.php');

// Events
// public function onExcelBeforeGenerate(ArrayObject $settings) {}
// public function onExcelGenerate($writer, ArrayObject $settings) {}
// public function onExcelGenerateComplete(ArrayObject $settings) {}
// public function onExcelBeforeQuery(ArrayObject $settings, Query $query) {}
// public function onExcelStartSheet(ArrayObject $settings, $totalCount) {}
// public function onExcelEndSheet(ArrayObject $settings, $totalProcessed) {}
// public function onExcelGetLabel($column) {}

class ExcelBehavior extends Behavior {
	use EventTrait;

	protected $_defaultConfig = [
		'folder' => 'export',
		'default_excludes' => ['modified_user_id', 'modified', 'created', 'created_user_id'],
		'excludes' => [],
		'limit' => 100,
		'orientation' => 'landscape' // or portrait
	];

	public function initialize(array $config) {
		$this->config('excludes', array_merge($this->config('default_excludes'), $this->config('excludes')));
		if (!array_key_exists('filename', $config)) {
			$this->config('filename', $this->_table->alias());
		}
		$folder = WWW_ROOT . $this->config('folder');

		if (!file_exists($folder)) {
			umask(0);
			mkdir($folder, 0777);
		} else {
			// $delete = true;
			// if (array_key_exists('delete', $settings) &&  $settings['delete'] == false) {
			// 	$delete = false;
			// }
			// if ($delete) {
			// 	$this->deleteOldFiles($folder, $format);
			// }
		}
		// pr(WWW_ROOT);
	}

	public function excel($id=0) {
		$this->generateXLXS(['id' => $id]);
	}

	private function eventKey($key) {
		return 'Model.excel.' . $key;
	}

	public function generateXLXS($settings=[]) {
		$_settings = [
			'file' => $this->config('filename') . '_' . date('Ymd') . 'T' . date('His') . '.xlsx',
			'path' => WWW_ROOT . $this->config('folder') . DS,
			'download' => true
		];
		$_settings = new ArrayObject(array_merge($_settings, $settings));

		$this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeGenerate'), 'onExcelBeforeGenerate', [$_settings]);

		$writer = new \XLSXWriter();
		$excel = $this;

		$generate = function($writer, $settings) {
			$this->generate($writer, $settings);
		};

		$event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGenerate'), 'onExcelGenerate', [$writer, $_settings]);
		if ($event->isStopped()) { return $event->result; }
		if (is_callable($event->result)) {
			$generate = $event->result;
		}
		
		$generate($writer, $_settings);

		$filepath = $_settings['path'] . $_settings['file'];
		$_settings['file_path'] = $filepath;
		$this->dispatchEvent($this->_table, $this->eventKey('onExcelGenerateComplete'), 'onExcelGenerateComplete', [$_settings]);
		$writer->writeToFile($_settings['file_path']);

		if ($_settings['download']) {
			$this->download($filepath);
		}
	}

	public function generate($writer, $settings=[]) {
		$fields = $this->getFields();
		// $header = $this->getHeader($fields);
		$footer = $this->getFooter();

		$query = $this->_table->find();
		$this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeQuery'), 'onExcelBeforeQuery', [$settings, $query]);
		$sheetName = $this->_table->alias();

		// if the primary key of the record is given, only generate that record
		if (array_key_exists('id', $settings)) {
			$id = $settings['id'];
			if ($id != 0) {
				$primaryKey = $this->_table->primaryKey();
				$query->where([$this->_table->aliasField($primaryKey) => $id]);
			}
		}

		$this->contain($query, $fields);

		$count = $query->count();
		$rowCount = 0;
		$percentCount = intval($count / 100);
		$pages = ceil($count / $this->config('limit'));

		if ($count == 1) {
			$this->config('orientation', 'portrait');
		}

		$this->dispatchEvent($this->_table, $this->eventKey('onExcelStartSheet'), 'onExcelStartSheet', [$settings, $count]);
		$this->onEvent($this->_table, $this->eventKey('onExcelBeforeWrite'), 'onExcelBeforeWrite');
		if ($this->config('orientation') == 'landscape') {
			$row = [];
			foreach ($fields as $attr) {
				$row[] = $attr['label'];
			}
			$writer->writeSheetRow($sheetName, $row);

			// process every page based on the limit
			for ($pageNo=0; $pageNo<$pages; $pageNo++) {
				$resultSet = $query
				->limit($this->config('limit'))
				->page($pageNo+1)
				->all();

				// process each row based on the result set
				foreach ($resultSet as $entity) {
					$row = [];
					foreach ($fields as $attr) {
						$field = $attr['field'];
						$row[] = $this->getValue($entity, $this->_table, $field);
					}
					$rowCount++;
					$this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeWrite'), null, [$settings, $rowCount, $percentCount]);
					$writer->writeSheetRow($sheetName, $row);
				}
			}
		} else {
			$entity = $query->first();
			foreach ($fields as $attr) {
				$field = $attr['field'];
				$row = [$attr['label']];
				$row[] = $this->getValue($entity, $this->_table, $field);
				$writer->writeSheetRow($sheetName, $row);
			}
			$rowCount++;
		}
		$this->dispatchEvent($this->_table, $this->eventKey('onExcelEndSheet'), 'onExcelEndSheet', [$settings, $rowCount]);
	}

	private function getFields() {
		$schema = $this->_table->schema();
		$columns = $schema->columns();
		$excludes = $this->config('excludes');
		$excludes[] = $this->_table->primaryKey();
		$fields = [];

		$columns = array_diff($columns, $excludes);

		$this->onEvent($this->_table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel');

		foreach ($columns as $col) {
			$field = $schema->column($col);
			if ($field['type'] != 'binary') {
				$label = $this->_table->aliasField($col);

				$event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGetLabel'), null, [$col]);
				if (strlen($event->result)) {
					$label = $event->result;
				}

				$fields[] = [
					'key' => $this->_table->aliasField($col),
					'field' => $col, 
					'label' => $label
				];
			}
		}
		return $fields;
	}

	private function getHeader($fields) {
		return $fields;
	}

	private function getFooter() {
		return 'footer';
	}

	private function getValue($entity, $table, $field) {
		$value = '';

		$method = 'onExcelGet' . Inflector::camelize($field);
		$event = $this->dispatchEvent($this->_table, $this->eventKey($method), $method, [$entity]);
		if ($event->result) {
			$value = $event->result;
		} else if ($entity->has($field)) {
			if ($this->isForeignKey($table, $field)) {
				$associatedField = $this->getAssociatedKey($table, $field);
				if ($entity->has($associatedField)) {
					$value = $entity->$associatedField->name;
				}
			} else {
				$value = $entity->$field;
			}
		}
		return $value;
	}

	private function isForeignKey($table, $field) {
		foreach ($table->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					return true;
				}
			}
		}
		return false;
	}

	public function getAssociatedTable($table, $field) {
		$relatedModel = null;

		foreach ($table->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					$relatedModel = $assoc;
					break;
				}
			}
		}
		return $relatedModel;
	}

	public function getAssociatedKey($table, $field) {
		$tableObj = $this->getAssociatedTable($table, $field);
		$key = null;
		if (is_object($tableObj)) {
			$key = Inflector::underscore(Inflector::singularize($tableObj->alias()));
		}
		return $key;
	}

	private function contain(Query $query, $fields) {
		$contain = [];
		foreach ($fields as $attr) {
			$field = $attr['field'];
			if ($this->isForeignKey($this->_table, $field)) {
				$contain[] = $this->getAssociatedTable($this->_table, $field)->alias();
			}
		}
		$query->contain($contain);
	}

	private function download($path) {
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

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			if ($buttons->offsetExists('edit')) {
				$toolbarButtons['export'] = $buttons['view'];
				if ($isFromModel) {
					$toolbarButtons['export']['url'][0] = 'excel';
				} else {
					$toolbarButtons['export']['url']['action'] = 'excel';
				}
				$toolbarButtons['export']['type'] = 'button';
				$toolbarButtons['export']['label'] = '<i class="fa kd-export"></i>';
				$toolbarButtons['export']['attr'] = $attr;
				$toolbarButtons['export']['attr']['title'] = __('Export');
			}
		} else if ($action == 'index') {
			// $toolbarButtons['export'] = $buttons['index'];
			// if ($isFromModel) {
			// 	$toolbarButtons['export']['url'][0] = 'excel';
			// } else {
			// 	$toolbarButtons['export']['url']['action'] = 'excel';
			// }
			// $toolbarButtons['export']['label'] = '<i class="fa kd-export"></i>';
			// $toolbarButtons['export']['type'] = 'button';
			// $toolbarButtons['export']['attr'] = $attr;
			// $toolbarButtons['export']['attr']['title'] = __('Export');
		}
	}
}
