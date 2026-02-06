<?php
namespace Institution\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;

class StaffPositionGradesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('staff_position_grades');
        parent::initialize($config);
        // $this->hasMany('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'staff_position_grade_id']);

		$this->belongsToMany('StaffPositionTitles', [
			'className' => 'Institution.StaffPositionTitles',
			'joinTable' => 'staff_position_titles_grades',
			'foreignKey' => 'staff_position_grade_id',  
			'targetForeignKey' => 'staff_position_title_id', 
			'through' => 'Institution.StaffPositionTitlesGrades',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function getAvailablePositionGrades($positionTitleId = 0)
    {
        $list = [];

        if (!is_null($positionTitleId)) {
            $StaffPositionTitles = TableRegistry::getTableLocator()->get('Institution.StaffPositionTitles');
            $positionTitleEntity = $StaffPositionTitles->get($positionTitleId);
            $isSelectAll = $StaffPositionTitles->checkIsSelectAll($positionTitleEntity);

            if ($isSelectAll) {
                $list = $this->find('list')->toArray();
            } else {
                $list = $this->find('list')
                    ->matching('StaffPositionTitles', function ($q) use ($positionTitleId) {
                        return $q->where(['StaffPositionTitles.id' => $positionTitleId]);
                    })
                    ->toArray();
            }
        }

        return $list;
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
