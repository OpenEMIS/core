<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\I18n\Time;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\EventTrait;
use Cake\I18n\I18n;

// 3rd party xlsx writer library
require_once(ROOT . DS . 'vendor' . DS  . 'XLSXWriter' . DS . 'xlsxwriter.class.php');

// Events
// public function onExcelBeforeGenerate(Event $event, ArrayObject $settings) {}
// public function onExcelGenerate(Event $event, $writer, ArrayObject $settings) {}
// public function onExcelGenerateComplete(Event $event, ArrayObject $settings) {}
// public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {}
// public function onExcelStartSheet(Event $event, ArrayObject $settings, $totalCount) {}
// public function onExcelEndSheet(Event $event, ArrayObject $settings, $totalProcessed) {}
// public function onExcelGetLabel(Event $event, $column) {}

class ExcelBehavior extends Behavior {
	use EventTrait;

	private $events = [];

	protected $_defaultConfig = [
		'folder' => 'export',
		'default_excludes' => ['modified_user_id', 'modified', 'created', 'created_user_id', 'password'],
		'excludes' => [],
		'limit' => 100,
		'pages' => [],
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
		$pages = $this->config('pages');
		if ($pages !== false && empty($pages)) {
			$this->config('pages', ['index', 'view']);
		}
	}

	private function eventMap($method) {
		$exists = false;
		if (in_array($method, $this->events)) {
			$exists = true;
		} else {
			$this->events[] = $method;
		}
		return $exists;
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
		$sheets = [];
		// [
		// 	'name' => 'sheet 1',
		// 	'params' => ['id' => 1, 'institution_id' => 1, 'date' => 1]
		// ],

		$event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeStart'), 'onExcelBeforeStart', [$settings, $sheets]);
		if ($event->isStopped()) { return $event->result; }
		if (is_array($event->result)) {
			$sheets = $event->result;
		}

		if (empty($sheets)) {
			$fields = $this->getFields();
			// $header = $this->getHeader($fields);
			$footer = $this->getFooter();

			$query = $this->_table->find();
			$event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeQuery'), 'onExcelBeforeQuery', [$settings, $query]);
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
							$row[] = $this->getValue($entity, $this->_table, $attr);
						}
						$rowCount++;
						$this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeWrite'), null, [$settings, $rowCount, $percentCount]);
						$writer->writeSheetRow($sheetName, $row);
					}
				}
			} else {
				$entity = $query->first();
				foreach ($fields as $attr) {
					$row = [$attr['label']];
					$row[] = $this->getValue($entity, $this->_table, $attr);
					$writer->writeSheetRow($sheetName, $row);
				}
				$rowCount++;
			}
			$writer->writeSheetRow($sheetName, ['']);
			$writer->writeSheetRow($sheetName, $footer);
			$this->dispatchEvent($this->_table, $this->eventKey('onExcelEndSheet'), 'onExcelEndSheet', [$settings, $rowCount]);
		} else {
			foreach ($sheets as $sheet) {
				$sheetName = $sheet['sheetName'];
				$header = $sheet['sheetHeader'];
				$rows = $sheet['rows'];
				$footer = $sheet['footer'];
				$writer->writeSheetRow($sheetName, $header);
				foreach ($rows as $row) {
					$writer->writeSheetRow($sheetName, $row);
				}
				$writer->writeSheetRow($sheetName, ['']);
				$writer->writeSheetRow($sheetName, $footer);
			}
		}
	}

	private function getFields() {
		$schema = $this->_table->schema();
		$columns = $schema->columns();
		$excludes = $this->config('excludes');
		$excludes[] = $this->_table->primaryKey();
		$fields = [];

		$module = $this->_table->alias();
		$language = I18n::locale();

		$excludedTypes = ['binary'];

		$columns = array_diff($columns, $excludes);

		$this->onEvent($this->_table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel');

		foreach ($columns as $col) {
			$field = $schema->column($col);
			if (!in_array($field['type'], $excludedTypes)) {
				$label = $this->_table->aliasField($col);

				$event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGetLabel'), null, [$module, $col, $language]);
				if (strlen($event->result)) {
					$label = $event->result;
				}

				$fields[] = [
					'key' => $this->_table->aliasField($col),
					'field' => $col,
					'type' => $field['type'],
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
		$footer = [__("Report Generated") . ": "  . date("Y-m-d H:i:s")];
		return $footer;
	}

	private function getValue($entity, $table, $attr) {
		$value = '';
		$field = $attr['field'];
		$type = $attr['type'];

		if (!in_array($type, ['string', 'integer', 'decimal'])) {
			$method = 'onExcelRender' . Inflector::camelize($type);
			if (!$this->eventMap($method)) {
				$event = $this->dispatchEvent($this->_table, $this->eventKey($method), $method, [$entity, $field]);
			} else {
				$event = $this->dispatchEvent($this->_table, $this->eventKey($method), null, [$entity, $field]);
			}
			if ($event->result) {
				$value = $event->result;
			}
		} else {
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
		}
		
		// if (!is_string($value)) {
		// 	pr($value);
		// }
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
		$events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 0];
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($buttons->offsetExists('view')) {
			$export = $buttons['view'];
			$export['type'] = 'button';
			$export['label'] = '<i class="fa kd-export"></i>';
			$export['attr'] = $attr;
			$export['attr']['title'] = __('Export');

			if ($isFromModel) {
				$export['url'][0] = 'excel';
			} else {
				$export['url']['action'] = 'excel';
			}

			$pages = $this->config('pages');
			if (in_array($action, $pages)) {
				$toolbarButtons['export'] = $export;
			}
		} else if ($buttons->offsetExists('back')) {
			$export = $buttons['back'];
			$export['type'] = 'button';
			$export['label'] = '<i class="fa kd-export"></i>';
			$export['attr'] = $attr;
			$export['attr']['title'] = __('Export');

			if ($isFromModel) {
				$export['url'][0] = 'excel';
			} else {
				$export['url']['action'] = 'excel';
			}

			$pages = $this->config('pages');
			if (in_array($action, $pages)) {
				$toolbarButtons['export'] = $export;
			}
		}
	}
}
