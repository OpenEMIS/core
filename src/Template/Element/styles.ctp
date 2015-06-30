<?php
echo $this->Html->meta(array('name' => 'viewport', 'content' => 'width=320, initial-scale=1'));

echo $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/grey');

echo $this->Html->css('OpenEmis.../plugins/bootstrap/css/bootstrap.min');
echo $this->Html->css('OpenEmis.../plugins/font-awesome/css/font-awesome.min');
echo $this->Html->css('OpenEmis.../plugins/menusidebar/css/simple-sidebar');
echo $this->Html->css('OpenEmis.../plugins/scrolltabs/css/scrolltabs.css');

echo $this->Html->css('OpenEmis.kordit/kordit');
echo $this->Html->css('OpenEmis.layout');
if ($htmlLangDir == 'rtl') {
	echo $this->Html->css('OpenEmis.layout.rtl');
}

if (isset($theme)) {
	echo $this->Html->css($theme);
}
