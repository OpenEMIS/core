<?php

class QualityController extends QualityAppController {

    public $uses = array();
    public $components = array('Paginator', 'FileUploader');
    public $paginate = array(
        'limit' => 25,
    );
    public $helpers = array('Quality.RubricsView');
    public $modules = array(
        'status' => 'Quality.QualityStatus',
        'rubricsTemplatesCriteria' => 'Quality.RubricsTemplateColumnInfo',
        'rubricsTemplatesHeader' => 'Quality.RubricsTemplateHeader',
        'rubricsTemplatesSubheader' => 'Quality.RubricsTemplateSubheader',
        'rubricsTemplatesDetail' => 'Quality.RubricsTemplateDetail',
        'rubricsTemplates' => 'Quality.RubricsTemplate',
        'qualityVisit' => 'Quality.QualityInstitutionVisit',
        'qualityRubricAnswer' => 'Quality.QualityInstitutionRubricsAnswer',
        'qualityRubricHeader' => 'Quality.QualityInstitutionRubricHeader',
        'qualityRubric' => 'Quality.QualityInstitutionRubric',
    );
    
    private $excludePages = array(
        'qualityVisit',
        'qualityVisitView',
        'qualityVisitEdit',
        'qualityVisitAdd',
        'qualityVisitDelete',
        'qualityRubric',
        'qualityRubricView',
        'qualityRubricEdit',
        'qualityRubricAdd',
        'qualityRubricDelete',
        'qualityRubricHeader',
        'qualityRubricAnswerView'
     );
    public function beforeFilter() {
        parent::beforeFilter();

        if (in_array($this->action, $this->excludePages)) {
            $this->Auth->allow('receive');
            $this->Auth->allow('viewMap', 'siteProfile');

            if ($this->Session->check('InstitutionId')) {
                $institutionId = $this->Session->read('InstitutionId');
                $Institution = ClassRegistry::init('Institution');
                $institutionName = $Institution->field('name', array('Institution.id' => $institutionId));
                $this->Navigation->addCrumb('Institutions', array('controller' => 'Institutions', 'action' => 'index', 'plugin' => false));
                $this->Navigation->addCrumb($institutionName, array('controller' => 'Institutions', 'action' => 'view', 'plugin' => false));

                if ($this->action === 'index' || $this->action === 'add') {
                    $this->bodyTitle = $institutionName;
                } else {
                    if ($this->Session->check('InstitutionSiteId')) {
                        $this->institutionSiteId = $this->Session->read('InstitutionSiteId');
                        $this->institutionSiteObj = $this->Session->read('InstitutionSiteObj');
                        $InstitutionSite = ClassRegistry::init('InstitutionSite');
                        $institutionSiteName = $InstitutionSite->field('name', array('InstitutionSite.id' => $this->institutionSiteId));
                        $this->bodyTitle = $institutionName . ' - ' . $institutionSiteName;
                        $this->Navigation->addCrumb($institutionSiteName, array('controller' => 'InstitutionSites', 'action' => 'view', 'plugin' => false));
                        //$this->Navigation->addCrumb('Quality', array('controller' => 'Quality', 'action' => 'qualityRubric', 'plugin'=> 'Quality'));
                        
                    } else {
                        $this->redirect(array('controller' => 'Institutions', 'action' => 'listSites','plugin' => false));
                    }
                }
            } else {
                if ($this->action == 'siteProfile' || $this->action == 'viewMap') {
                    $this->layout = 'profile';
                } else {
                  //  pr('here - Quality Controller');die;
                    $this->redirect(array('controller' => 'Institutions', 'action' => 'index', 'plugin' => false));
                }
            }
            
            
        } else {
            $this->bodyTitle = 'Administration';
            $this->Navigation->addCrumb('Administration', array('controller' => '../Setup', 'action' => 'index'));
            $this->Navigation->addCrumb('Quality', array('controller' => 'Quality', 'action' => 'rubricsTemplates', 'plugin'=> 'Quality'));
        }

    }

    public function index() {
     //   echo 'index page';
    }

}

?>