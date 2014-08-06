<?php 
$defaults = $this->FormUtility->getFormDefaults();
if ($this->action == 'add' || $this->action == 'edit') : 

?>

<div class="form-group">
	<label class="<?php echo $defaults['label']['class'] ?>"><?php echo $this->Label->get('CensusGrid.x_categories') ?></label>
	<div class="col-md-6">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('general.name') ?></th>
						<th class="cell-delete"></th>
					</tr>
				</thead>
				<tbody>
				<?php 
				if (($this->action == 'add' || $this->action == 'edit') && isset($this->data['CensusGridXCategory'])) :
					foreach ($this->data['CensusGridXCategory'] as $i => $obj) :
				?>
					<tr>
						<td>
							<?php
							if ($this->action == 'edit') {
								echo $this->Form->hidden("CensusGridXCategory.$i.id");
								echo $this->Form->hidden("CensusGridXCategory.$i.visible", array('value' => 1));
								echo $this->Form->hidden("CensusGridXCategory.$i.census_grid_id", array('value' => $this->data[$model]['id']));
							}
							echo $this->Form->hidden("CensusGridXCategory.$i.order");
							echo $this->Form->input("CensusGridXCategory.$i.name", array('label' => false, 'div' => false, 'between' => false, 'after' => false));
							?>
						</td>
						<td><span class="icon_delete" title="<?php echo $this->Label->get('general.delete') ?>" onclick="jsTable.doRemove(this)"></span></td>
					</tr>
				<?php
					endforeach;
				endif;
				?>
				</tbody>
			</table>
			<a class="void icon_plus" onclick="$('#CensusGridXCategory').click()"><?php echo $this->Label->get('general.add') ?></a>
		</div>
	</div>
</div>

<?php else : ?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.name') ?></th>
			</tr>
		</thead>
		<tbody>
		<?php 
		if (!empty($data['CensusGridXCategory'])) :
			foreach ($data['CensusGridXCategory'] as $i => $obj) :
		?>
			<tr>
				<td><?php echo $obj['name'] ?></td>
			</tr>	
		<?php
			endforeach;
		endif;
		?>
		</tbody>
	</table>
</div>

<?php endif ?>
