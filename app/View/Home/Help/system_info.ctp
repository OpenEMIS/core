<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subTitle));

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-bordered">
		<tbody>
			<tr>
				<td><?php echo __('Database'); ?></td>
				<td><?php echo $dbStore . '/' . $dbVersion ?></td>
			</tr>
			<tr>
				<td><?php echo __('PHP Version'); ?></td>
				<td><?php echo phpversion(); ?></td>
			</tr>
			<tr>
				<td><?php echo __('Web Server'); ?></td>
				<td><?php echo $_SERVER['SERVER_SOFTWARE'];?></td>
			</tr>
			<tr>
				<td><?php echo __('Operating System'); ?></td>
				<td><?php echo php_uname("s") . '/' . php_uname("r"); ?></td>
			</tr>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
