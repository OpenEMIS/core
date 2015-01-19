<?php
	$options = array('escape' => false, 'class' => 'void action', 'onclick' => 'Reorder.move(this)');
	if($index!=$size) {
		$class = $index>1 ? 'void action' : 'void action action-last';
		$options['move'] = 'last';
		$options['class'] = $class;
		echo '<span class="icon_last" move="last" onclick="Reorder.move(this)"></span>';
	}
	if($index!=$size) {
		$options['move'] = 'down';
		echo '<span class="icon_down" move="down" onclick="Reorder.move(this)"></span>';
	}
	if($index>1) {
		$options['move'] = 'up';
		echo '<span class="icon_up" move="up" onclick="Reorder.move(this)"></span>';
	}
	if($index>1) {
		$class = $index!=$size ? 'void action' : 'void action action-last';
		$options['move'] = 'first';
		$options['class'] = $class;
		echo '<span class="icon_first" move="first" onclick="Reorder.move(this)"></span>';
	}
?>
