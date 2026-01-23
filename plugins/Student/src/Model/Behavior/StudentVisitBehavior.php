<?php
namespace Student\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;

class StudentVisitBehavior extends Behavior {
    private $_tabFeatures = [
        'StudentVisitRequests' => 'Requests',
        'StudentVisits' => 'Visits',
    ];

    private $_sessionReadKeys = [
        'Students' => 'Student.Students.name',
    ];

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

        // Breadcrumbs
        $navigation = $model->Navigation;
        $oldTitle = $model->getHeader($model->getAlias());
        $newTitle = $this->_tabFeatures[$model->getAlias()];
        $newTitle = $model->getHeader($newTitle);
        $navigation->substituteCrumb($oldTitle, $newTitle);

        // Header
        //$session = $model->request->getSession();
        //$sessionKey = $this->_sessionReadKeys[$controllerName];
        $studentId = $this->_table->getQueryString('student_id');
        $username = $this->getStudentData($studentId);
        //$username = $session->read($sessionKey);
        $postfix = $newTitle;
        $header = $username . ' - ' . $postfix;
        $controller->set('contentHeader', $header);

        // Tab elements
        $tabElements = $this->getVisitTab();
        $tabElements = $controller->TabPermission->checkTabPermission($tabElements);
        $controller->set('tabElements', $tabElements);
        $controller->set('selectedAction', $model->getAlias());
    }

    public function getVisitTab()
    {
        $controller = $this->_table->controller;
        $plugin = $controller->getPlugin();
        $controllerName = $controller->getName();
        $institutionId = $this->getInstitutionID();
        $userId = $this->getUserID();
        $params = ['user_id' => $userId, 'student_id' => $userId];
        if ($institutionId != null) {
            $params['institution_id'] = $institutionId;
        }
        $model = $this->_table;

        $encodedQueryString = $model->paramsEncode($params);
        $urlBase = [
            'plugin' => $plugin,
            'controller' => $controllerName,
            '0' =>'index',
            '1' => $encodedQueryString
        ];

        $tabElements = [];
        foreach ($this->_tabFeatures as $feature => $featureName) {
            if ($controller->AccessControl->check([$controllerName, $feature, 'index'])) {

                $featureUrl = array_merge($urlBase, ['action' => $feature]);
                $tabElements[$feature] = [
                    'url' => $featureUrl,
                    'text' => __($featureName)
                ];
            }
        }
        return $tabElements;
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
        }

        return $userID;
    }

    private function getStudentData($studentId = null)
    {
        $StudentData = TableRegistry::getTableLocator()->get('User.Users')
                ->find()
                ->where([
                    'id' => $studentId,
                ])
                ->enableHydration(false)
                ->first();
        $StudentName = '';
        if(!empty($StudentData)){
            if($StudentData['middle_name'] !='' && $StudentData['third_name'] !=''){
                $StudentName = $StudentData['first_name'] . ' '. $StudentData['middle_name'] .' '. $StudentData['third_name'] . ' '. $StudentData['last_name'];
            }else if($StudentData['middle_name'] !='' && $StudentData['third_name'] ==''){
                $StudentName = $StudentData['first_name'] . ' '. $StudentData['middle_name'] . ' '. $StudentData['last_name'];
            }else if($StudentData['middle_name'] =='' && $StudentData['third_name'] !=''){
                $StudentName = $StudentData['first_name'] . ' '. $StudentData['third_name'] . ' '. $StudentData['last_name'];
            }else{
                $StudentName = $StudentData['first_name'] .' '. $StudentData['last_name'];
            }
        }

        return $StudentName;
    }

}
