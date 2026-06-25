<?php
namespace Education\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use ArrayObject;

class EducationStagesTable extends ControllerActionTable
{
	public function initialize(array $config): void
    {
		parent::initialize($config);
		$this->addBehavior('Education.Setup');
		$this->hasMany('EducationGrades', ['className' => 'Education.EducationGrades', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->setDeleteStrategy('restrict');
	}

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('code', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ]);
        return $validator;
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true) {
        if ($field == 'name') {
            return __('Name');
        } elseif ($field == 'education_level_id') {
            return __('Education Level');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'code') {
            return __('Code');
        }elseif ($field == 'visible') {
            return __('Visible');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}