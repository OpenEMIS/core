<?php

if ($action == 'view') : ?>


<table class="table table-striped table-hover table-bordered">
	<thead>
		<tr>
			<th><?php echo $this->Label->get('general.level') ?></th>
			<th><?php echo $this->Label->get('general.code') ?></th>
			<th><?php echo $this->Label->get('Area.name') ?></th>
		</tr>
	</thead>
	<tbody>
			<?php foreach ($data[$model]['SecurityGroupArea'] as $obj) : ?>
		<tr>
			<td><?php echo $levels[$obj['Area']['area_level_id']] ?></td>
			<td><?php echo $obj['Area']['code'] ?></td>
			<td><?php echo $obj['Area']['name'] ?></td>
		</tr>
			<?php endforeach ?>
	</tbody>
</table>

<?php else : ?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get("$model.SecurityGroupArea") ?></label>
	<div class="col-md-6">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('Area.name') ?></th>
					<th class="cell-delete"></th>
				</tr>
			</thead>
			<tbody>
				<?php 
				if ($action == 'edit') :
					foreach ($this->data[$model]['SecurityGroupArea'] as $i => $obj) :
				?>
				<tr>
					<td>
							<?php
							echo $this->Form->hidden("SecurityGroupArea.$i.area_id", array('class' => 'value-id', 'value' => $obj['SecurityGroupArea']['area_id']));
							echo $this->Form->hidden('index', array('name' => 'index', 'class' => 'index', 'value' => $i));
							echo $obj['Area']['name'];
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
		<a class="void icon_plus" url="Security/SecurityGroup/ajaxGetAccessOptionsRow/0" onclick="SecurityGroup.getAccessOptionsRow(this)">
				<?php echo $this->Label->get('general.add') . ' ' . $this->Label->get('Area.name') ?>
		</a>
	</div>
</div>
<?php endif ?>
