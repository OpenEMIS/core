<?php
echo $this->Html->css('/Datawarehouse/css/style', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Datawarehouse/js/datawarehouse', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');

if(!empty($this->data[$model]['id'])){
	echo $this->Html->link(__('Back'), array('action' => 'indicatorView', $this->data[$model]['id']), array('class' => 'divider', 'id'=>'back'));
}else{
	echo $this->Html->link(__('Back'), array('action' => 'indicator'), array('class' => 'divider', 'id'=>'back'));
}

$this->end();
$this->start('contentBody');
?>
<div class="navbar">
	<div class="navbar-inner">
		<div class="container-wizard">
			<ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
				<li class="active"><a href="#tab-indicator" id="lnk-indicator" data-toggle="tab">Indicator</a></li>
				<li class="disabled"><a href="#tab-numerator" id="lnk-numerator" class="void"  data-toggle="tab">Numerator</a></li>
				<li class="disabled"><a href="#tab-denominator" id="lnk-denominator" class="void" data-toggle="tab">Denominator</a></li>
				<li class="disabled"><a href="#tab-review" id="lnk-review" class="void" data-toggle="tab">Review</a></li>
			</ul>
		</div>
	</div>
</div>

<?php
$formOptions = $this->FormUtility->getFormOptions();
echo $this->Form->create($model, $formOptions);
?>	
	<div id='content' class="tab-content">
		<?php echo $this->element('indicator');?>
	  	<?php echo $this->element('dimension');?>
	</div> 
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>  