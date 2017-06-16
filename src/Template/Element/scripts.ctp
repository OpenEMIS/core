<?php
echo $this->element('OpenEmis.scripts');

/*echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getJSConfig');*/

echo $this->element('ControllerAction.scripts');
echo $this->Html->script('doughnutchart/Chart.min');
echo $this->Html->script('doughnutchart/Chart.Doughnut');

// Slider //
echo $this->Html->script('app/shared/ngSlider/slider');

echo $this->Html->script('app/app.ctrl');
echo $this->Html->script('app/app.svc');
echo $this->Html->script('app/services/app/utils.svc');
echo $this->Html->script('Restful.kd.orm.svc');
echo $this->Html->script('Restful.kd.data.svc');
echo $this->Html->script('app/services/app/aggrid.locale.svc');
echo $this->Html->script('app/services/app/kd.session.svc');
echo $this->Html->script('app/services/app/kd.access.svc');
echo $this->Html->script('Area.tree/sg.tree.ctrl');
echo $this->Html->script('Area.tree/sg.tree.svc');

echo $this->Html->script('angular/kdModule/controllers/kd.ctrl');
echo $this->Html->script('angular/kdModule/directives/kd.drt');
echo $this->Html->script('angular/kdModule/services/kd.common.svc');
echo $this->Html->script('angular/kdModule/kd.module');

// Assessments specific controller
echo $this->Html->script('Assessment.angular/assessments/assessmentAdminModule');

//JS use in Core
echo $this->Html->script('app');
echo $this->Html->script('app.table');
echo $this->Html->script('config');
