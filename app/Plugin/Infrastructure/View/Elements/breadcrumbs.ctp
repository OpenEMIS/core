<?php if (!empty($paths)): ?>
	<ul class="breadcrumb">
		<?php
		foreach ($paths as $i => $item) {
			if ($i == count($paths) - 1) {
				echo '<li class="active">' . $item['name'] . '</li>';
			} else {
				echo '<li>' . $this->Html->link($item['name'], $item['url']) . '</li>';
			}
		}
		?>
	</ul>
<?php endif; ?>