<?php

if ($action == 'view') : ?>

<table class="table table-striped table-hover table-bordered">
	<thead>
		<tr>
			<th><?php echo $this->Label->get('general.code') ?></th>
			<th><?php echo $this->Label->get('InstitutionSite.name') ?></th>
		</tr>
	</thead>
	<tbody>
			<?php foreach ($data['GroupInstitutionSite'] as $key=>$obj) : ?>
		<tr>
			<td><?php echo $obj['code'] ?></td>
			<td><?php echo $obj['name'] ?></td>
		</tr>
			<?php endforeach ?>
	</tbody>
</table>

<?php else : ?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get("$model.GroupInstitutionSite") ?></label>
	<div class="col-md-6">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('Institution.name') ?></th>
					<th class="cell-delete"></th>
				</tr>
			</thead>
			<tbody>
				<?php 
				if ($action == 'edit') :
					foreach ($this->data['GroupInstitutionSite'] as $i => $obj) :
				?>
				<tr>
					<td>
							<?php
							echo $this->Form->hidden("GroupInstitutionSite.$i.institution_site_id", array('class' => 'value-id', 'value' => $obj['id']));
							echo $this->Form->hidden('index', array('name' => 'index', 'class' => 'index', 'value' => $i));
							echo $obj['code'].' - '.$obj['name'];
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
		<a class="void icon_plus" url="Security/SecurityGroup/ajaxGetAccessOptionsRow/1" onclick="SecurityGroup.getAccessOptionsRow(this)">
				<?php echo $this->Label->get('general.add') . ' ' . $this->Label->get('Institution.name') ?>
		</a>
	</div>
</div>
<?php endif ?>
