<?php
echo $this->Html->css('OpenEmis.reset');

echo $this->Html->css('OpenEmis.../plugins/fuelux/css/fuelux.min');
echo $this->Html->css('OpenEmis.../plugins/fuelux/css/fuelux');
echo $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/grey');

echo $this->Html->css('OpenEmis.../plugins/bootstrap/css/bootstrap.min');
echo $this->Html->css('OpenEmis.../plugins/font-awesome/css/font-awesome.min');
echo $this->Html->css('OpenEmis.../plugins/menusidebar/css/jqx.base');
echo $this->Html->css('OpenEmis.../plugins/scrolltabs/css/scrolltabs.css');

echo $this->Html->css('highchart-override');

echo $this->Html->css('OpenEmis.master.min');
echo $this->Html->css('OpenEmis.kordit/kordit');
if (isset($theme)) {
	echo $this->Html->css($theme);
}
?>

<!--[if gte IE 9]>
<?php
	echo $this->Html->css('OpenEmis.ie/ie9-fixes');
?>
<![endif]-->