<?php
if (!isset($products)) {
	$products = [];
}
?>

<div class="btn-group">
	<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
		<i class="fa kd-grid"></i>
	</a>

	<div aria-labelledby="dropdownMenu" role="menu" class="dropdown-menu product-lists col-xs-12">
		<div class="dropdown-arrow">
			<i class="fa fa-caret-up"></i>
		</div>

		<div class="product-wrapper">
		<?php foreach ($products as $name => $item) : ?>
			<div class="product-menu col-xs-4">
				<?php

				$link = '';

				if (isset($item['file_name']) && !empty($item['file_name'])) {
					$fileName = $item['file_name'];
					$link .= $this->Html->image('product_list_logo/'.$fileName, [
							'style' => 'height:35px; width: 35px'
						]);
				} else {
					$link .= '<i class="' . $item['icon'] . '"></i>';
				}

				$link .= '<span>' . $item['name'] . '</span>';
				echo $this->Html->link($link, $item['url'], array('escape' => false));
				?>
			</div>
		<?php endforeach ?>
		</div>
	</div>
</div>
