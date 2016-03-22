<?php
echo $this->Html->css('OpenEmis.reset');

//Jquery Library
echo $this->Html->css('OpenEmis.lib/jquery/jquery-ui.min');

//Add in only Wizard and remove all component
echo $this->Html->css('OpenEmis.../plugins/font-awesome/css/font-awesome.min');
echo $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/grey');
echo $this->Html->css('OpenEmis.../plugins/fuelux/css/fuelux'); 
echo $this->Html->css('OpenEmis.../plugins/bootstrap/css/bootstrap.min');
echo $this->Html->css('OpenEmis.../plugins/scrolltabs/css/scrolltabs.css');
echo $this->Html->css('OpenEmis.../plugins/slider/css/bootstrap-slider');

echo $this->Resource->css('OpenEmis.master.min');

if (isset($theme)) {
	echo $this->Resource->css($theme);
}
?>

<!--[if gte IE 9]>
<?php
	echo $this->Resource->css('OpenEmis.ie/ie9-fixes');
?>
<![endif]-->