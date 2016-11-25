<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;

use App\Model\Table\ControllerActionTable;

class StudentBehaviourCategoriesClassificationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('student_behaviour_categories_classifications');
        parent::initialize($config);

        $this->belongsTo('StudentBehaviourClassifications', ['className' => 'Student.StudentBehaviourClassifications']);
        $this->belongsTo('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories']);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }
    }

    public function getUsedBehaviourCategories($id)
    {
        $usedBehaviourCategoriesData = $this->find()
            ->where([$this->aliasField('student_behaviour_classification_id') . ' <> ' => $id])
            ->toArray();
        $usedBehaviourCategoriesList = [];

        if (!empty($usedBehaviourCategoriesData)) {
            foreach ($usedBehaviourCategoriesData as $key => $obj) {
                $usedBehaviourCategoriesList[$obj->student_behaviour_category_id] = $obj->student_behaviour_classification_id;
            }
        }

        return $usedBehaviourCategoriesList;
    }
}
