<?php 
namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class SetupTabBehavior extends Behavior 
{
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.afterAction'] = 'afterAction';
        return $events;
    }

    private function setupTabElements()
    {
        if ($this->_table->controller->name == 'Scholarships') {
            $tabElements = $this->_table->controller->ScholarshipTabs->getScholarshipApplicationTabs();
        } else {
            $options = [
                'userRole' => '',
            ];

            switch ($this->_table->controller->name) {
                case 'Students':
                    $options['userRole'] = 'Students';
                    break;
                case 'Staff':
                    $options['userRole'] = 'Staff';
                    break;
            }
            $session = $this->_table->request->session();
            $guardianId = $session->read('Guardian.Guardians.id');
            $studentId = $session->read('Student.Students.id');
            $isStudent = $session->read('Directory.Directories.is_student');
            $isGuardian = $session->read('Directory.Directories.is_guardian');
            $studentToGuardian = $session->read('Directory.Directories.studentToGuardian');
            $guardianToStudent = $session->read('Directory.Directories.guardianToStudent');

            if ($this->_table->controller->name == 'Directories') {
                if (!empty($isGuardian) && !empty($studentId) && !empty($guardianToStudent)) {
                    $tabElements = $this->_table->controller->getUserTabElements(['id' => $studentId, 'userRole' => 'Students']);
                } elseif (!empty($isStudent) && !empty($guardianId) && !empty($studentToGuardian)) {
                    $tabElements = $this->_table->controller->getUserTabElements(['id' => $guardianId, 'userRole' => 'Guardians']);
                } else {
                    $tabElements = $this->_table->controller->getUserTabElements();
                }
            } elseif ($this->_table->controller->name == 'Guardians') {
                $tabElements = $this->_table->controller->getGuardianTabElements();
            } else {
                $tabElements = $this->_table->controller->getUserTabElements($options);
            }
        }

        $this->_table->controller->set('tabElements', $tabElements);
        if ($this->_table->alias() == 'UserLanguages') {
            $this->_table->controller->set('selectedAction', 'Languages');
        }else {
            $this->_table->controller->set('selectedAction', $this->_table->alias());
        }

    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }
}
