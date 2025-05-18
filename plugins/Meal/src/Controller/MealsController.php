<?php
namespace Meal\Controller;

use ArrayObject;
use Exception;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use Cake\I18n\Date;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use App\Model\Traits\OptionsTrait;
use Meal\Controller\AppController;
use ControllerAction\Model\Traits\UtilityTrait;
use Cake\Datasource\ConnectionManager;



class MealsController extends AppController
{

    public function initialize(): void{
        parent::initialize();
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {

		$header = 'Meal Programmes';
        $this->Navigation->addCrumb($header, ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $this->request->getParam('action')]);


        //Customize header because model name created was different and POCOR-5692 requirement was modified.
        if($this->request->getParam('action') == 'Programme'){
            $header = __('Meal Programmes') . ' - ' . __('Programmes');
            $this->Navigation->addCrumb('Programme');
        }

        if($this->request->getParam('action') == 'MealProgramme'){
            $header = __('Meal Programmes') . ' - ' . __('MealProgramme');
            $this->Navigation->addCrumb('MealProgramme');
        }

        $this->set('contentHeader', $header);
    }



    public function programme(){
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Meal.MealProgrammes']);
    }

    public function beforeRender(Event|\Cake\Event\EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }

    public function beforeFilter(Event|\Cake\Event\EventInterface $event)
    {
        if ($this->getPlugin() == 'Meal') {
            $this->Security->setConfig('validatePost', false);
        }
        parent::beforeFilter($event);
    }

}
