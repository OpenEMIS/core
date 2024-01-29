<?php
namespace Education\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;

class EducationProgrammeOrientationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Education.Setup');
        $this->hasMany('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies', 'cascadeCallbacks' => true]);
        $this->setDeleteStrategy('restrict');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return __('Name');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'visible') {
            return __('Visible');
        } elseif ($field == 'education_programme_orientation_id') {
            return __('Education Programme Orientation');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
