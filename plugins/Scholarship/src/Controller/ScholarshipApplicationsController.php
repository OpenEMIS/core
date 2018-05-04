<?php
namespace Scholarship\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

class ScholarshipApplicationsController extends AppController
{
    public function initialize()
    {
        parent::initialize();

    }

    public function ScholarshipApplications() 
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.ScholarshipApplications']);
    }
    // public function Generals() { 
    //     $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Generals']); 
    // } 
    public function Identities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Identities']);
    }
    public function Nationalities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserNationalities']);
    }
    public function Contacts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Contacts']);
    }
    public function Guardians()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Guardians']);
    }
    public function StaffQualifications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Qualifications']);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
        $header = __('Scholarships');

        $alias = ($model->alias == 'ScholarshipApplications') ? 'Applicants' : $model->alias;
        $header .= ' - ' . $model->getHeader($alias);
        $this->Navigation->addCrumb('Scholarship',  ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'index']);

        $this->Navigation->addCrumb($model->getHeader($alias));

        $this->set('contentHeader', $header);

        $persona = false;
        $event = new Event('Model.Navigation.breadcrumb', $this, [$this->request, $this->Navigation, $persona]);
        $event = $model->eventManager()->dispatch($event);
    }

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        if (array_key_exists('queryString', $this->request->query)) {
            $applicantId = $this->ControllerAction->getQueryString('applicant_id');

            if ($model->hasField('security_user_id')) {
                $query->where([$model->aliasField('security_user_id') => $applicantId]);
            } else if ($model->hasField('student_id')) {
                $query->where([$model->aliasField('student_id') => $applicantId]);
            }
        }
    }

    public function getScholarshipTabElements($options = [])
    {   
        if (array_key_exists('queryString', $this->request->query)) {
            $queryString = $this->request->query('queryString');
        }
    
        $plugin = $this->plugin;
        $name = $this->name;

        $tabElements = [
            $this->name => ['text' => __('Overview')],
            // 'Generals' => ['text' => __('General')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' => ['text' => __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Guardians' => ['text' => __('Guardians')],
            // 'ExaminationResults' => ['text' => __('Examinations')],  
            // 'Qualifications' => ['text' => __('Qualifications')],
            'ScholarshipHistories' => ['text' => __('Scholarship History')], //page
            'ApplicationInstitutionChoices' => ['text' => __('Institution Choice')], //page
            'ScholarshipApplicationAttachments' => ['text' => __('Attachments')], //page
        ];

        foreach ($tabElements as $key => $value) {
            if ($key == $this->name) {
                $tabElements[$key]['url']['action'] = 'ScholarshipApplications';
                $tabElements[$key]['url'][0] = 'view';
                $tabElements[$key]['url'][1] = $queryString;
                $tabElements[$key]['url']['queryString'] = $queryString;
            } elseif (in_array($key, ['ScholarshipHistories', 'ApplicationInstitutionChoices', 'ScholarshipApplicationAttachments'])) {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $key,
                    'action' => 'index',
                    'queryString' => $queryString,
                ];
                $tabElements[$key]['url'] = $url;
            } else { 
                $actionURL = $key;
                if ($key == 'UserNationalities') {
                    $actionURL = 'Nationalities';
                }

                $url = [
                    'plugin' => $plugin,
                    'controller' => $name,
                    'action' => $actionURL,
                    'index',
                    'queryString' => $queryString,
                ];

                $tabElements[$key]['url'] = $url;
            }
        }

       return $this->TabPermission->checkTabPermission($tabElements);
    }
}
     