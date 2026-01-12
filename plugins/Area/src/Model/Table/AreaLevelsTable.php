<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Http\ServerRequest;

class AreaLevelsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->hasMany('Areas', ['className' => 'Area.Areas', 'foreign_key' => 'area_level_id']);
        $this->addBehavior('RestrictAssociatedDelete');
        $this->setDeleteStrategy('restrict');
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('level', ['before' => 'name']);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Area Levels','Administrative Boundaries');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['level']['type'] = 'hidden';
    }

    // To fix institution_area_level_id in configitem
    // public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options) {
    // 	$ConfigItemsTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
    // 	$transferedValue = $this->request->data[$this->alias()]['convert_to'];
    // 	$ConfigItemsTable->updateAll(['default_value' => $transferedValue], ['type' => 'Institution', 'code' => 'Institution_area_level_id', 'default_value' => $entity->id]);
    // 	$ConfigItemsTable->updateAll(['value' => $transferedValue], ['type' => 'Institution', 'code' => 'Institution_area_level_id', 'value' => $entity->id]);
    // }

    public function onUpdateFieldLevel(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $query = $this->find();
            $results = $query
                ->select(['level' => $query->func()->max('level')])
                ->all();

            $maxLevel = 0;
            if (!$results->isEmpty()) {
                $data = $results->first();
                $maxLevel = $data->level;
            }

            $attr['attr']['value'] = ++$maxLevel;
        }

        return $attr;
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        //check config
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $validateAreaLevel = $ConfigItems->value('institution_validate_area_level_id');
        if ($validateAreaLevel == $entity->level) {
            $extra['associatedRecords'][] = ['model' => 'System Configurations - Institution', 'count' => 1];
        }
    }

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $listeners = [
            TableRegistry::getTableLocator()->get('Configuration.ConfigItems')
        ];

        $this->dispatchEventToModels('Model.AreaLevel.afterDelete', [$entity], $this, $listeners);
    }
}
