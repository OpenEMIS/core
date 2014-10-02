<div class="tab-pane active" id="tab-indicator">
	<?php 
	$labelClass = 'col-md-3 control-label';
	if(!empty($this->data[$model]['id'])) { 
		echo $this->Form->input('id', array('type' => 'hidden'));
	}
	if(!empty($this->data['DatawarehouseIndicatorCondition']['id'])) {
		echo $this->Form->input('DatawarehouseIndicatorCondition.id', array('type' => 'hidden'));
	}
	if(!empty($this->data['Denominator']['id'])) {
		echo $this->Form->input('Denominator.id', array('type' => 'hidden'));
	}
	echo $this->Form->input($model.'.name', array('label' => array('text' => $this->Label->get('Datawarehouse.indicator'), 'class' => $labelClass)));
	echo $this->Form->input($model.'.description', array('type' => 'textarea'));
	echo $this->Form->input($model.'.code');
	//echo $this->Form->input($model.'.classification'); ?>



<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Classification');?></label>
	<div class="col-md-4">
		<div class="input-append">
		    <div class="btn-group">
	   		 	<?php
				echo $this->Form->input($model.'.classification', array('id'=>'searchClassification','class'=>'form-control classification', 'url'=>'Datawarehouse/ajax_find_classification/', 'placeholder' =>$this->Label->get('Datawarehouse.classification'), 'div'=>false, 'label'=>false, 'between'=>false, 'after'=>false)); 
					?>
                 <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                <ul id="drpClassification" class="dropdown-menu scrollable-menu" role="menu">
                	<?php foreach($datawarehouseClassificationOptions as $options){ ?>
                    <li><a href="javascript: void(0)"><?php echo $options; ?></a></li>
                    <?php } ?>
                </ul>
            </div>
		</div>
	</div>
</div>

	<?php
	echo $this->Form->input($model.'.datawarehouse_unit_id', array(
		'options' => $datawarehouseUnitOptions,
		'onchange' => 'objDatawarehouse.getUnitType(this)',
		'label' => array(
			'text' => $this->Label->get('Datawarehouse.unit'),
			'class' => $labelClass
		)
	));
	?>

	<div class="form-group">
		<div class="col-md-offset-4">
			<input type="submit" value="<?php echo __('Next'); ?>" name='nextStep' class="btn_save btn_right" >
			<a href="indicator" class="btn_cancel btn_left"><?php echo __('Cancel'); ?></a>
		</div>
	</div>
</div>
