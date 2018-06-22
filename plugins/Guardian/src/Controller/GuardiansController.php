<?php
namespace Guardian\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class GuardiansController extends AppController
{
    public function initialize()
    {
		parent::initialize();
		$this->ControllerAction->models = [
			'Accounts' => ['className' => 'Directory.Accounts', 'actions' => ['view', 'edit']],
		];

		$this->set('contentHeader', 'Guardians');
    }

    // CAv4
	public function Directories()
	{
		$this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Directory.Directories']);
	}

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

    public function Languages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserLanguages']);
    }

    public function SpecialNeeds()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.SpecialNeeds']);
    }

    public function Attachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Attachments']);
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Navigation->addCrumb('Guardians', ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'Guardians']);
        $this->Navigation->addCrumb('Directory', ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories']);

        $action = $this->request->params['action'];        
		$query = $this->request->query;

		$userId = $query['guardian_id'];
		$Directories = TableRegistry::get('Directory.Directories');
		$entity = $Directories->get($userId);

		$this->Navigation->addCrumb($entity->name, ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'view', $this->ControllerAction->paramsEncode(['id' => $userId])]);
        
        $header = __('Directory');
        $header=$entity->name. ' - ' .$header;

        $this->set('contentHeader', $header);        
    } 

  	public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
		$query = $this->request->query;
		$userId = $query['guardian_id'];
		$Directories = TableRegistry::get('Directory.Directories');
		$entity = $Directories->get($userId);    	
        $alias = $model->alias;
        $this->Navigation->addCrumb($model->getHeader($alias));
		$header = $entity->name .' - '.$alias;
		$guardianId = $this->request->query('guardian_id');
            $this->set('contentHeader', $header);      	
    }      

    public function getUserTabElements($options = [])
    {
		$query = $this->request->query;
		$userId = $query['guardian_id'];
		$Directories = TableRegistry::get('Directory.Directories');		
		$entity = $Directories->get($userId);  		 	
    	$id=$entity->id;
		//$id = (array_key_exists('id', $options))? $options['id']: $this->request->session()->read('Guardian.Guardians.id');

		$tabElements = [
            'Overview' => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' => ['text' => __('Nationalities')], //UserNationalities is following the filename(alias) to maintain "selectedAction" select tab accordingly.
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'SpecialNeeds' => ['text' => __('Special Needs')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')]
        ];

		foreach ($tabElements as $key => $value) {
			if ($key == 'Overview') {
		    	$tabElements[$key]['url']['action'] = 'Directories';
				$tabElements[$key]['url'][] = 'view';
				$tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $id]);
			}elseif ($key == 'Accounts') {
				$tabElements[$key]['url']['action'] = 'Accounts';
				$tabElements[$key]['url'][0] = 'view';
				$tabElements[$key]['url'][1] = $this->ControllerAction->paramsEncode(['id' => $id]);
			} elseif ($key == 'Comments') {
				$url = [
					'plugin' => 'Directory',
					'controller' => 'DirectoryComments',
					'action' => 'index'
				];
				$tabElements[$key]['url'] = $this->ControllerAction->setQueryString($url, ['security_user_id' => $id]);
			} else {
				$actionURL = $key;
				if ($key == 'UserNationalities') {
					$actionURL = 'Nationalities';
				}
				$url = [
					'plugin' => $this->plugin,
					'controller' => $this->name,
					'action' => $actionURL,
					'index'
				];
				$tabElements[$key]['url'] = $this->ControllerAction->setQueryString($url, ['security_user_id' => $id]);
			}

		}
        return $this->TabPermission->checkTabPermission($tabElements);                
    }
}
