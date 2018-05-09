<?php
namespace Scholarship\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

class ScholarshipsController extends AppController
{
    public function initialize()
    {
        parent::initialize();

    }

    public function Scholarships()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Scholarships']);
    }
    public function ScholarshipApplications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Applications']);
    }
    // public function General() {
    //     $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.General']);
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

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $this->Navigation->addCrumb('Scholarship',  ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'index']);
        $this->Navigation->addCrumb('Applicants', ['plugin' => 'Scholarship', 'controller' => 'ScholarshipApplications', 'action' => 'ScholarshipApplications']);


        if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4

            $alias = $model->alias();
            $excludedModel = ['ScholarshipApplications', 'Scholarships'];

            if (!in_array($alias, $excludedModel)) {
                $model->toggle('add', false);
                $model->toggle('edit', false);
                $model->toggle('remove', false);
            }
        }
        
        $header = __('Scholarships');
        $alias = 'Applicants';
        
        if (array_key_exists('queryString', $this->request->query)) {
            $ids = $this->ControllerAction->paramsDecode($this->request->query['queryString']);
            
            if(isset($ids['applicant_id'])) {
                $applicantId = $this->ControllerAction->getQueryString('applicant_id');
                $alias = ($model->alias == 'ScholarshipApplications') ? 'Overview' : $model->alias;
                $entity = $this->ScholarshipApplications->Applicants->get($applicantId);
                $header = $entity->name;
                $this->Navigation->addCrumb($header);
                $this->Navigation->addCrumb($model->getHeader($alias));
            }
        } 

        $header .= ' - ' . $model->getHeader($alias);
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


        // //Missing General, examination & qualification
        // $tabElements = [
        //     'ScholarshipApplications' => [
        //         'url' => ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'ScholarshipApplications', 'view', $queryString, 'queryString' => $queryString],
        //         'text' => __('Overview')
        //     ],
        //     'Identities' => [
        //         'url' => ['plugin' => 'Profile', 'controller' => 'ProfileInstitutionChoices', 'action' => 'index', 'queryString' => $queryString],
        //         'text' => __('Identities')
        //     ],

        //     'Contacts' => [
        //         'url' => ['plugin' => 'Profile', 'controller' => 'ProfileInstitutionChoices', 'action' => 'index', 'queryString' => $queryString],
        //         'text' => __('Contacts')
        //     ],
        //     'Guardians' => [
        //         'url' => ['plugin' => 'Profile', 'controller' => 'ProfileInstitutionChoices', 'action' => 'index', 'queryString' => $queryString],
        //         'text' => __('Guardians')
        //     ],
        //     'InstitutionChoices' => [
        //         'url' => ['plugin' => 'Profile', 'controller' => 'ProfileInstitutionChoices', 'action' => 'index', 'queryString' => $queryString],
        //         'text' => __('Institution Choices')
        //     ],




        //     'InstitutionChoices' => [
        //         'url' => ['plugin' => 'Profile', 'controller' => 'ProfileInstitutionChoices', 'action' => 'index', 'queryString' => $queryString],
        //         'text' => __('Institution Choices')
        //     ],
        //     'Attachments' => [
        //         'url' => ['plugin' => 'Profile', 'controller' => 'ProfileApplicationAttachments', 'action' => 'index', 'queryString' => $queryString],
        //         'text' => __('Attachments')
        //     ]
        // ];


