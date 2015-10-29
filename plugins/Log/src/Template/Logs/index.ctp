<?php
foreach ($files as $file) {
	echo $this->Html->link($file, ['plugin' => 'Log', 'controller' => 'Logs', 'action' => 'download', $file]);
	echo '<br>';
}
?>
