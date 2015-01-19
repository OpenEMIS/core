<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Reports'));

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th>Name</th>
				<th>From</th>
				<th>To</th>
				<th>Generated</th>
				<th>Expires</th>
				<th>Status</th>
			</tr>
		</thead>
		
		<tbody>
			<tr>
				<td>Overview</td>
				<td>2015-01-01</td>
				<td>2015-12-31</td>
				<td>2015-01-13 16:39:23</td>
				<td>2015-01-16 16:39:23</td>
				<td><a href="" style="font-size: 11px; padding: 3px 8px;">Download</button></td>
				
			</tr>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
