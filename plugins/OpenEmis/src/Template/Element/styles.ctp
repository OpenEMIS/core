<?php
echo $this->Html->css('OpenEmis.reset');

//Add in only Wizard and remove all component
echo $this->Html->css('OpenEmis.../plugins/font-awesome/css/font-awesome.min');
echo $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/grey');
echo $this->Html->css('OpenEmis.../plugins/fuelux/css/fuelux'); 
echo $this->Html->css('OpenEmis.../plugins/bootstrap/css/bootstrap.min');
echo $this->Html->css('OpenEmis.../plugins/scrolltabs/css/scrolltabs.css');
echo $this->Html->css('OpenEmis.../plugins/slider/css/bootstrap-slider');

echo $this->Html->css('OpenEmis.master.min');
echo $this->Html->css('master-override');

if (isset($theme)) {
	echo $this->Html->css($theme);
}
?>

<!--[if gte IE 9]>
<?php
	echo $this->Html->css('OpenEmis.ie/ie9-fixes');
?>
<![endif]-->