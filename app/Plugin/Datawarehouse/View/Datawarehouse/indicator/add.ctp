<?php
echo $this->Html->css('/Datawarehouse/css/style', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Datawarehouse/js/datawarehouse', false);
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
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
<style>
.input-append .btn.dropdown-toggle {
    float: none;
}

.btn-group {
	display: block;
	height: 20px;
}

.input-append > .btn-group > .btn-primary {
height: 25px;
}

.input-append > .btn-group > .form-control {
	width: 82%;
	float: left;
}

.dropdown-menu > li {
	font-size: 11px;
	/*padding: 0px;*/
}

.scrollable-menu {
    height: auto;
    max-height: 115px;
    overflow-x: hidden;
}

</style>
<div class="navbar">
	<div class="navbar-inner">
		<div class="container-wizard">
			<ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
				<?php 
				$i = 0;
				foreach($tabStep as $step){ 
					$activeStep = false;

					if($currentStep>=$i){
						$activeStep	= true;
					}

					if(isset($this->request->data['DatawarehouseIndicator']['datawarehouse_unit_id']) && $this->request->data['DatawarehouseIndicator']['datawarehouse_unit_id']=='1'){
						if($step=='denominator'){
							$activeStep = false;
						}
					}
				?>
					<li class="<?php echo ($currentStep==$i) ? 'active' : ($activeStep ? '' :'disabled');?>"><a href="#tab-<?php echo $step;?>" id="lnk-<?php echo $step;?>" data-toggle="tab" class="<?php echo ($activeStep) ? '' : 'void';?>"><?php echo ucwords($step);?></a></li>
				<?php 
				$i++;
				} ?>
			</ul>
		</div>
	</div>
</div>

<?php
$formOptions = $this->FormUtility->getFormOptions();
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('type', array('type'=> 'hidden', 'value'=>$currentTab));
?>	
	<div id='content' class="tab-content">
		<?php echo $this->element('indicator');?>
	  	<?php echo $this->element('dimension');?>
	  	<?php echo $this->element('review');?>
	</div> 
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>  