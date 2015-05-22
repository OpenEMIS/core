<?php
namespace Institution\Controller;

// use Institution\Controller\AppController;
use App\Controller\AppController;// as BaseController;

class InstitutionsController extends AppController
{

	public function initialize() {
		parent::initialize();

		// $this->ControllerAction->model('Institution.InstitutionSites');
		$this->ControllerAction->model('InstitutionSites');
		// $this->ControllerAction->models = ['InstitutionSites'];
		if ($this->request->param('action') == 'attachments') {
			$this->request->params['action'] = 'InstitutionSiteAttachments';
			$this->loadModel('InstitutionSiteAttachments');
			$this->ControllerAction->model('InstitutionSiteAttachments');
			$this->ControllerAction->models[] = 'InstitutionSiteAttachments';
			// die('yeah');
		// } elseif ($this->request->params['action'] == 'InstitutionSiteAttachments') {
		// 	$this->loadModel('InstitutionSiteAttachments');
		// 	$this->ControllerAction->model('InstitutionSiteAttachments');
		// 	$this->ControllerAction->models[] = 'InstitutionSiteAttachments';
		// 	// die('yeah');
		// }else{
		}
		$this->loadComponent('Paginator');

		// $this->modules( ['attachments' => 'InstitutionSiteAttachment'] );
		$this->set('contentHeader', 'Institutions');

    }

}
