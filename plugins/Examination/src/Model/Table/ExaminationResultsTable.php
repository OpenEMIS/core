<?php
namespace Examination\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;

class ExaminationResultsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('examination_centres_examinations');
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);

        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportResults']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = [
                'plugin' => $this->controller->getPlugin(),
                'controller' => $this->controller->getName(),
                'action' => 'Results',
                'academic_period_id' => $entity->academic_period_id,
                'examination_id' => $entity->examination_id,
                'examination_centre_id' => $entity->examination_centre_id
            ];
        }

        return $buttons;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('examination_id', ['type' => 'string', 'sort' => false]);
        $this->field('name', ['sort' => ['field' => 'ExaminationCentres.name']]);
        $this->setFieldOrder(['name', 'academic_period_id', 'examination_id', 'total_registered']);


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Results','Examinations');
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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $serverRequest = $this->request;
        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];

        $where = [];
        // Academic Period
        // End

        // Examination
        $examinationOptions = $this->getExaminationOptions();
        $examinationOptions = ['-1' => __('All Examinations')] + $examinationOptions;
        $selectedExamination = !is_null($serverRequest->getQuery('examination_id')) ? $serverRequest->getQuery('examination_id') : -1;

        $this->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
           $where[$this->aliasField('examination_id')] = $selectedExamination;
        }
        // End

        $extra['auto_contain_fields'] = ['ExaminationCentres' => ['code']];
        $query->where($where);

        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $extra['OR'] = [
                [$this->ExaminationCentres->aliasField('name').' LIKE' => '%' . $search . '%'],
                [$this->ExaminationCentres->aliasField('code').' LIKE' => '%' . $search . '%']
            ];
        }

        // sort
        $sortList = ['ExaminationCentres.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return $entity->examination_centre->code_name;
    }

    private function getExaminationOptions()
    {
        $examinationOptions = $this->Examinations
            ->find('list')
            ->toArray();

        return $examinationOptions;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return  __('Name');
        } else if ($field == 'academic_period_id') {
            return  __('Academic Period');
        } else if ($field == 'examination_id') {
            return  __('Examination');
        }else if ($field == 'total_registered') {
            return  __('Total Registered');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
