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
		$fileFormat = json_decode($obj['params']);
		
		$obj['file_path'] = NULL;
		$obj['current_records'] = 0;
		$obj['total_records'] = 0;
		$obj['status'] = self::PENDING;
		
		$newEntity = $this->newEntity($obj);
		$result = $this->save($newEntity);

		if($fileFormat->format == 'zip'){
			$expiryDate = new Time();
			$expiryDate->addDays(5);
			$this->updateAll(
			['status' => self::COMPLETED, 'file_path' => WWW_ROOT . 'downloads' . DS . $obj['module'].'-photo' . DS, 'expiry_date' => $expiryDate, 'modified' => new Time()],
			['id' => $result->id]
		);
		}
		return $result->id;
	}

	public function generate($id, $fileFormat) {

		if($fileFormat == 'zip'){
			//$cmd = ROOT . DS . 'bin' . DS . 'cake StudentsPhotoDownload ' . $id;
		    $logs = ROOT . DS . 'logs' . DS . 'student-photo-reports.log & echo $!';
		} 

		else {
			$cmd = ROOT . DS . 'bin' . DS . 'cake Report ' . $id;
			$logs = ROOT . DS . 'logs' . DS . 'reports.log & echo $!';
		}
		
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

	public function purge($userId=null, $now=false) {
		$format = 'Y-m-d';

		$conditions = [$this->aliasField('expiry_date') . ' < ' => date($format)];

		$query = $this->find();
		
		if (!$now) {
			$query->where($conditions);
		}

		if (!is_null($userId)) {
			$query->where([$this->aliasField('created_user_id') => $userId]);
		}

		$resultSet = $query->toArray();
		
		foreach ($resultSet as $entity) {
			if (file_exists($entity->file_path)) {
				if (unlink($entity->file_path)) {
					$this->delete($entity);
				}
			} else {
				$this->delete($entity);
			}
		}
	}
}
