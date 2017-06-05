<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;

class CustomReportsTable extends AppTable
{
	public function initialize(array $config)
	{
		$this->table('reports');
		parent::initialize($config);

        $this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event)
	{
		$controllerName = $this->controller->name;
		$reportName = __('Custom Reports');

		$this->controller->Navigation->substituteCrumb($this->alias(), $reportName);
		$this->controller->set('contentHeader', __($controllerName).' - '.$reportName);
	}

	public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('name', ['type' => 'hidden']);
        $this->ControllerAction->field('query', ['type' => 'hidden']);
        $this->ControllerAction->field('filter', ['type' => 'hidden']);
        $this->ControllerAction->field('excel_template_name', ['type' => 'hidden']);
        $this->ControllerAction->field('excel_template', ['type' => 'hidden']);

        $this->ControllerAction->field('feature', ['type' => 'select', 'select' => false]);
        $this->ControllerAction->field('format');
    }

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
        	$reportOptions = $this->find('list')->toArray();

            $attr['options'] = $reportOptions;
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }
}
