<?php
$server = ($_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . DS;
$env = basename($this->webroot);
$products = array(
	'Visualizer' => array('logo' => 'visualizer_logo.png', 'name' => 'visualizer')
);
?>

<div class="btn-group product-list">
	<a class="btn dropdown-toggle" data-toggle="dropdown" href="#" style="color: #FFFFFF">
		<i class="fa fa-list fa-lg"></i>
	</a>

	<ul aria-labelledby="dropdownMenu" role="menu" class="dropdown-menu">
	<?php foreach ($products as $name => $item) : ?>
		<li>
			<a href="<?php echo $server . $item['name'] . (in_array($env, array('tst')) ? DS.$env : '') . ('/?lang=' . $lang) ?>" target="_blank">
				<?php echo $this->Html->image($item['logo'], array('height' => 20)) ?>
				<span style="margin-left: 5px;"><?php echo $name ?></span>
			</a>
		</li>
	<?php endforeach ?>
	</ul>
</div>
