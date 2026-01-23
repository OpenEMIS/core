<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StudentsRiskAssessmentTable extends AppTable  {
	public function initialize(array $config): void {
		$this->setTable('institution_students');
		parent::initialize($config);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('StudentsRiskAssessmentExcel', [
            'pages' => false
        ]);

    }

    public function onExcelBeforeStart (EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->getAlias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }
}
