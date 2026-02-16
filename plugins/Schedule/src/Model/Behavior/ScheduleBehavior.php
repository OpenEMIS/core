<?php
namespace Schedule\Model\Behavior;

use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;

class ScheduleBehavior extends Behavior
{
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 100];
        return $events;
    }

    public function beforeAction(EventInterface $event)
    {
        $model = $this->_table;
        $controller = $this->_table->controller;
        $controllerName = $controller->getName();
        $modelAlias = $model->getAlias();
        $institutionId = $this->getInstitutionID();
        $userId = $this->getUserID();

        if ($modelAlias == 'ScheduleTimetables') {
            $modelAlias = 'ScheduleTimetableOverview';
        }

        // Breadcrumbs
        $navigation = $model->Navigation;
        $oldTitle = $model->getHeader($modelAlias);
        $newTitle = $model->getHeader(str_replace('Schedule ', '', $oldTitle));
        $newTitle = $model->getHeader(str_replace(' Overview', '', $newTitle)); // For timetable page only

        $navigation->substituteCrumb($oldTitle, $newTitle);

        // Header
        $session = $model->request->getSession();
       // $institutionNameParam = $model->request->getParam('pass')[1];
        //$paramsDecode = $model->paramsDecode($institutionNameParam);
        $institutionId = $this->getInstitutionID();
//        echo "<pre>"; print_r($institutionId); die('gjhghg');

        $institutionTable =  TableRegistry::getTableLocator()->get('Institution.Institutions');
        $activeInstitution = $institutionTable->find()->where(['id' => $institutionId])->first();
        $institutionName = $activeInstitution->name;
        $postfix = $newTitle;
        $header = $institutionName . ' - ' . $postfix;
        $controller->set('contentHeader', $header);
    }

    private function getInstitutionID()
    {
        $model = $this->_table;
        $institutionID = $model->getQueryString('institution_id');
        return $institutionID;
    }

    private function getUserID()
    {
        $model = $this->_table;
        $userID = $model->getQueryString('security_user_id');
        if (!$userID) {
            $userID = $model->getQueryString('user_id');
        }
        if(!$userID){
            $userID = $model->getQueryString();
            //die('userID<pre>' . print_r($userID, true) . '</pre>');
        }

        return $userID;
    }
}
