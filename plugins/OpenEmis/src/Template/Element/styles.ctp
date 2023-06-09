<?php
echo $this->Html->css('OpenEmis.reset');

//Jquery Library
echo $this->Html->css('OpenEmis.lib/jquery/jquery-ui.min');

//Add in only Wizard and remove all component
echo $this->Html->css('OpenEmis.../plugins/font-awesome/css/font-awesome.min');
echo $this->Html->css('OpenEmis.../plugins/fuelux/css/fuelux');
echo $this->Html->css('OpenEmis.../plugins/bootstrap/css/bootstrap.min');
echo $this->Html->css('OpenEmis.../plugins/scrolltabs/css/scrolltabs.css');
echo $this->Html->css('OpenEmis.../plugins/slider/css/bootstrap-slider');
echo $this->Html->css('OpenEmis.../plugins/ng-scrolltabs/css/angular-ui-tab-scroll');
echo $this->Html->css('OpenEmis.../plugins/toggle-switch/toggle-switch');
// echo $this->Html->css('OpenEmis.../plugins/ng-agGrid/css/ag-grid');
// echo $this->Html->css('OpenEmis.../plugins/ng-agGrid/css/theme-fresh');

//Fix Method App\View\Helper\ResourceHelper::css does not exist
echo $this->Html->css('OpenEmis.master.min');

if (isset($theme)) {
	echo $this->Html->css($theme);
}
?>
<link rel="stylesheet" href="<?= $this->Url->css('themes/layout.min') ?>?timestamp=<?=$lastModified?>" >
<!--[if gte IE 9]>
<?php
	echo $this->Html->css('OpenEmis.ie/ie9-fixes');
?>
<![endif]-->