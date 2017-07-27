<?php
echo $this->Html->css('Page.reset');

//Jquery Library
echo $this->Html->css('Page.lib/jquery/jquery-ui.min');

//Add in only Wizard and remove all component
echo $this->Html->css('Page.../plugins/font-awesome/css/font-awesome.min');
echo $this->Html->css('Page.../plugins/icheck/skins/minimal/grey');
echo $this->Html->css('Page.../plugins/fuelux/css/fuelux');
echo $this->Html->css('Page.../plugins/bootstrap/css/bootstrap.min');
echo $this->Html->css('Page.../plugins/scrolltabs/css/scrolltabs.css');
echo $this->Html->css('Page.../plugins/slider/css/bootstrap-slider');
echo $this->Html->css('Page.../plugins/ng-scrolltabs/css/angular-ui-tab-scroll');
echo $this->Html->css('Page.../plugins/toggle-switch/toggle-switch');
// echo $this->Html->css('Page.../plugins/ng-agGrid/css/ag-grid');
// echo $this->Html->css('Page.../plugins/ng-agGrid/css/theme-fresh');

echo $this->Resource->css('Page.master.min');

if (isset($theme)) {
	echo $this->Resource->css($theme);
}
?>

<!--[if gte IE 9]>
<?php
	echo $this->Resource->css('Page.ie/ie9-fixes');
?>
<![endif]-->