<?php
namespace Institution\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class StaffPositionGradesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_position_grades');
        parent::initialize($config);
        $this->hasMany('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'staff_position_grade_id']);

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
            $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
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
}
