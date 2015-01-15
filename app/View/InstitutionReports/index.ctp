<?php
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/progressbar/bootstrap-progressbar.min', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
?>

<style type="text/css">
.list-group .btn { font-size: 11px; }
.list-group .list-group-item { padding: 8px 10px; }
.list-group .list-group-item > span { line-height: 28px; }
.list-group .list-group-item .btn-group { float: right; }

.dropdown-menu .progress { float: right; width: 75px; margin: 4px 15px 0 0; height: 15px; }

.list-group .dropdown-menu > li > a { font-size: 11px; }
</style>

<ul class="list-group">

	<li class="list-group-item">
		<span>Overview</span>
		
		<div class="btn-group">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				Generate
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li><a href="#">CSV</a></li>
				<li><a href="#">Excel</a></li>
			</ul>
		</div>
	</li>

	<li class="list-group-item">
		<span>Students</span>
		
		<div class="btn-group">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				Generate
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li><a href="#">CSV</a></li>
				<li><a href="#">Excel</a></li>
			</ul>
		</div>

		<div class="btn-group" style="margin-right: 10px;">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				Download
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li>
					<a href="#" style="float: left;">CSV</a>
					<div class="progress">
						<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 30%">
							<span class="sr-only">20% Complete</span>
						</div>
					</div>
				</li>
				<li>
					<a href="#" style="float: left;">Excel</a>
					<div class="progress">
						<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 70%">
							<span class="sr-only">20% Complete</span>
						</div>
					</div>
				</li>
			</ul>
		</div>
	</li>

	<li class="list-group-item">
		<span>Classes</span>
		
		<div class="btn-group">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				Generate
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li><a href="#">CSV</a></li>
				<li><a href="#">Excel</a></li>
			</ul>
		</div>

		<div class="btn-group" style="margin-right: 10px;">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				Download
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li>
					<a href="#" style="float: left;">CSV</a>
					<div class="progress">
						<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 90%">
							<span class="sr-only">20% Complete</span>
						</div>
					</div>
				</li>
			</ul>
		</div>
	</li>
</ul>

<script type="text/javascript">
$(document).ready(function() {
	$('#popover').popover();
});
</script>

<?php $this->end() ?>



<?php if (count($data) > 0) : ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo __('Name'); ?></th>
					<th style="width:100px"><?php echo __('Types'); ?></th> 
				</tr>
			</thead> 
			<tbody>
				<?php foreach ($data as $i => $obj) : ?>
					<tr>
						<td><?php echo __($obj['name']); ?></td>
						<td class="" style="width:100px;">
							<?php foreach ($obj['formats'] as $name => $action) {
								$url = array('action' => 'generate', $obj['model'], $name);
								if(isset($obj['params']) && isset($obj['params'][$name])) {
									$url = array_merge($url, $obj['params'][$name]);
								}
								echo $this->Html->link(strtoupper($name), $url);
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif ?>