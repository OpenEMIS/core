<?php
namespace Report\Model\Table;

use DateTime;
use DateInterval;
use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class ReportProgressTable extends AppTable  {
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

		$expiryDate = new DateTime();
		$expiryDate->add(new DateInterval('P3D')); // should take from config item

		$obj['file_path'] = NULL;
		$obj['expiry_date'] = $expiryDate->format('Y-m-d H:i:s');
		$obj['current_records'] = 0;
		$obj['total_records'] = 0;
		$obj['status'] = 1;

		$result = $this->save($this->newEntity($obj));
		return $result->id;
	}

	public function generate($id) {
		$params = array('Report', 'run', $id);
		$cmd = sprintf("%sConsole/cake.php -app %s %s", APP, APP, implode(' ', $params));
		$nohup = 'nohup %s >> %stmp/logs/reports.log & echo $!';
		$shellCmd = sprintf($nohup, $cmd, APP);
		$this->log($shellCmd, 'debug');
		//pr($shellCmd);
		$pid = exec($shellCmd);
		$this->id = $id;
		$this->saveField('pid', $pid);
	}

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
