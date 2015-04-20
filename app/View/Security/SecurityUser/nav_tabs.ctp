<?php
if ($action != 'view') {
	$data = $this->request->data;
}
if (array_key_exists($model, $data)) {
	if (array_key_exists('id', $data[$model])) {
		$id = $data[$model]['id'];
	}
}
$options = array(
	'SecurityUser' => array(
		'url' => array('controller' => 'Security', 'action' => 'SecurityUser', 'view', $id),
		'text' => __('General')
	),
	'SecurityUserLogin' => array(
		'url' => array('controller' => 'Security', 'action' => 'SecurityUserLogin', 'edit', $id),
		'text' => __('Login')
	)
);

$selectedAction = isset($selectedAction) ? $selectedAction : 'SecurityUser';
?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $arr): 
?>
		<li role="presentation" class="<?php echo ($option == $selectedAction) ? 'active' : ''; ?>"><?php echo $this->Html->link($arr['text'], $arr['url']); ?></li>
	<?php endforeach; ?>
</ul>