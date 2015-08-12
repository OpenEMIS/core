<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\Datasource\Exception\RecordNotFoundException;
use App\Model\Table\AppTable;

class ReportProgressTable extends AppTable  {
	const ERROR = -1;
	const COMPLETED = 0;
	const PENDING = 1;

	public function initialize(array $config) {
		$this->table('report_progress');
		parent::initialize($config);
	}

	public function addReport($obj) {
		if (isset($obj['params'])) {
			$obj['params'] = json_encode($obj['params']);
		}

		/* Currently disable the logic to prevent user to create duplicate reports
		$found = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'ReportProgress.name' => $obj['name'],
				'ReportProgress.created_user_id' => $userId
			)
		));
		if ($found) {
			$obj['id'] = $found[$this->alias]['id'];
			$obj['created'] = date('Y-m-d H:i:s');
			if ($found[$this->alias]['status'] == 1) {
				return false;
			}
		}
		*/

		$expiryDate = new Time();
		$expiryDate->addDays(3);

		$obj['file_path'] = NULL;
		$obj['expiry_date'] = $expiryDate;
		$obj['current_records'] = 0;
		$obj['total_records'] = 0;
		$obj['status'] = self::PENDING;

		$newEntity = $this->newEntity($obj);
		$result = $this->save($newEntity);
		return $result->id;
	}

	public function generate($id) {
		$cmd = ROOT . DS . 'bin' . DS . 'cake Report ' . $id;
		$logs = ROOT . DS . 'logs' . DS . 'reports.log & echo $!';
		$shellCmd = $cmd . ' >> ' . $logs;

		try {
			$entity = $this->get($id);
			$pid = exec($shellCmd);
			Log::write('debug', $shellCmd);
			$entity->pid = $pid;
			$this->save($entity);
		} catch(RecordNotFoundException $ex) {
			Log::write('error', __METHOD__ . ' Record Id (' . $id. ' ) not found');
		}
	}

	// needs modification to cake v3
	public function purge($userId) {
		$format = 'Y-m-d';
		$data = $this->find('list', array(
			'fields' => array('ReportProgress.id', 'ReportProgress.file_path'),
			'conditions' => array('ReportProgress.expiry_date < ' => date($format))
		));

		foreach ($data as $id => $path) {
			if (file_exists($path)) {
				if (unlink($path)) {
					$this->delete($id);
				} else {
					$this->log('ReportProgress.purge - Unable to delete file id:' . $id, 'debug');
				}
			} else {
				$this->delete($id);
			}
		}
	}
}
