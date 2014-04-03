<?php
echo $this->Html->script('report/index', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

echo $this->Html->css('report/report_manager');
echo $this->Html->css('report/smart_wizard');
echo $this->Html->script(array('report/jquery-ui-1.8.18.min'));
echo $this->Html->script(array('report/jquery.smartWizard-2.0', 'report/default'));
?>
<?php echo $this->Form->create('Report',array('id' => 'ReportWizardForm', 'target'=>'blank'));?>
<?php echo $this->element('breadcrumb'); ?>

<div id="report" class="content_wrapper">
	<h1>
		<span><?php echo __('Custom Reports'); ?></span>
		<?php
		echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
		?>
	</h1>
	
	<div id="wizard" class="swMain">
		<ul>
			<li>
				<a href="#step-1">
					<label class="stepNumber">1</label>
					<span class="stepDesc">
						<?php echo __d('report_manager','Step 1',true); ?><br />
						<small><?php echo __d('report_manager','Select fields',true); ?></small>
					</span>
				</a>
			</li>
			<li>
				<a href="#step-2">
					<label class="stepNumber">2</label>
					<span class="stepDesc">
						<?php echo __d('report_manager','Step 2',true); ?><br />
						<small><?php echo __d('report_manager','Set the filter',true); ?></small>
					</span>
				</a>
			</li>
			<li>
				<a href="#step-3">
					<label class="stepNumber">3</label>
					<span class="stepDesc">
						<?php echo __d('report_manager','Step 3',true); ?><br />
						<small><?php echo __d('report_manager','Select order',true); ?></small>
					</span>
				</a>
			</li>
			<li>
				<a href="#step-4">
					<label class="stepNumber">4</label>
					<span class="stepDesc">
						<?php echo __d('report_manager','Step 4',true); ?><br />
						<small><?php echo __d('report_manager','Select style',true); ?></small>
					</span>
				</a>
			</li>       
		</ul>
		
		<div id="step-1">   
			<h2 class="StepTitle"><?php echo __d('report_manager','Step 1 Fields',true); ?></h2>
			<div class="reportManager index">
			<?php 
			echo $this->Element('report/fields_dnd_table_header',array(
				'plugin'=>'ReportManager',
				'title'=>__d('report_manager','Report Manager'),
				'sortableClass'=>'sortable1'
			));
			
			if ( isset($this->data[$modelClass]) ) // load from file
				$currentModelSchema = $this->data[$modelClass];
			else // new report
				$currentModelSchema = $modelSchema;
			
			echo $this->Element('report/fields_dnd',array(
				'plugin'=>'ReportManager',
				'modelClass'=>$modelClass,
				'modelSchema'=>$currentModelSchema
			));
			
			foreach ($associatedModelsSchema as $key => $value) {
				if ( $associatedModels[$key] == 'hasMany' || $associatedModels[$key] == 'hasAndBelongsToMany' )
					continue;
			
				if ( isset($this->data[$key]) ) // load from file
					$currentModelSchema = $this->data[$key];
				else // new report
					$currentModelSchema = $value;
			
				echo $this->Element('report/fields_dnd',array(
					'plugin'=>'ReportManager',
					'modelClass'=>$key,
					'modelSchema'=>$currentModelSchema
				));
			}
			
			echo $this->Element('report/fields_dnd_table_close',array('plugin'=>'ReportManager'));
			if ( $oneToManyOption != null ) {
				echo $this->Element('report/fields_dnd_table_header',array(
					'plugin'=>'ReportManager',
					'title'=>$oneToManyOption,
					'sortableClass'=>'sortable2'
				));
			
				if ( isset($this->data[$oneToManyOption]) ) // load from file
					$currentModelSchema = $this->data[$oneToManyOption];
				else // new report
					$currentModelSchema = $associatedModelsSchema[$oneToManyOption];
			
				echo $this->Element('report/fields_dnd',array(
					'plugin'=>'ReportManager',
					'modelClass'=>$oneToManyOption,
					'modelSchema'=>$currentModelSchema
				));
				echo $this->Element('report/fields_dnd_table_close',array('plugin'=>'ReportManager'));
			}
			?>
			</div>
		</div>
		
		<div id="step-2">
			<h2 class="StepTitle"><?php echo __d('report_manager','Step 2 Filter',true); ?></h2> 
			<?php      
			echo $this->Element('report/logical_operator');
			echo $this->Element('report/filter',array('plugin'=>'ReportManager','modelClass'=>$modelClass,'modelSchema'=>$modelSchema));
			foreach ($associatedModelsSchema as $key => $value) {
				if ( $associatedModels[$key] != 'hasMany' && $associatedModels[$key] != 'hasAndBelongsToMany' )            
				echo $this->Element('report/filter',array('plugin'=>'ReportManager','modelClass'=>$key,'modelSchema'=>$value));
			}
			?>
		</div>
		
		<div id="step-3">
			<h2 class="StepTitle"><?php echo __d('report_manager','Step 3 Order',true); ?></h2>   
			<?php
			echo $this->Element('report/order_direction');
			echo $this->Element('report/order',array('plugin'=>'ReportManager','modelClass'=>$modelClass,'modelSchema'=>$modelSchema));
			foreach ($associatedModelsSchema as $key => $value) {
			if ( $associatedModels[$key] != 'hasMany' && $associatedModels[$key] != 'hasAndBelongsToMany' )            
			echo $this->Element('report/order',array('plugin'=>'ReportManager','modelClass'=>$key,'modelSchema'=>$value));
			}
			?>
		</div>
		
		<div id="step-4">
			<h2 class="StepTitle"><?php echo __d('report_manager','Step 4 Style',true); ?></h2>   
			<?php
			echo $this->Element('report/report_style',array('plugin'=>'ReportManager','oneToManyOption'=>$oneToManyOption));
			?> 
		</div>
	</div>
	<?php echo $this->Element('report/one_to_many_option',array('plugin'=>'ReportManager','oneToManyOption'=>$oneToManyOption)); ?>
</div>
<?php echo $this->Form->end() ;?>