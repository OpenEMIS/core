<?php if (isset($toolbars)) : ?>
	<?php
		foreach ($toolbars as $name => $toolbar) {
			if (!isset($toolbar['type']) || $toolbar['type'] == 'button') {
				echo $this->Html->link($toolbar['label'], $toolbar['url'], $toolbar['attr']);
			} else if ($toolbar['type'] == 'element') {
				echo $this->element($toolbar['element'], $toolbar['data'], $toolbar['options']);
			}
		}
	?>
<?php endif ?>
