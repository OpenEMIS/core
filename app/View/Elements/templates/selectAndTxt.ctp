<div class="form-group required">
	<label class="col-md-3 control-label"><?php echo $label; ?></label>
	<div class="col-md-4">
		<table>
			<tr>
				<div style="margin-bottom: 7px">
					<?php 
						echo $this->Form->input($selectId, 
							array(
								'label' => false,
								'div' => false,
								'options' => $selectOptions,
								'class' => 'form-control',
								'before' => false,
								'between' => false
								)
							);
							?>
				</div>
			</tr>
			<tr>
				<?php 
					echo $this->Form->input($txtId,
						array(
							'placeholder' => $txtPlaceHolder,
							'label' => false,
							'div' => false,
							'before' => false,
							'between' => false
						)
					);  
				?>
			</tr>
			</table>
		</div>
		<div class="col-md-4">
		</div>
	</div>
