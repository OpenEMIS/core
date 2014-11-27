<?php 
$alertTypes = array();
$alertTypes['ok'] = 'alert-success';
$alertTypes['error'] = 'alert-danger';
$alertTypes['info'] = 'alert-info';
$alertTypes['warn'] = 'alert-warning';

if($this->Session->check('_alert')) {
	$_alert = $this->Session->read('_alert');
	$class = 'alert ' . $alertTypes[$_alert['type']];

	if ($_alert['dismissOnClick']) {
		$class = 'alert-dismissible ' . $class;
	}
	unset($_SESSION['_alert']);
?>

	<div class="<?php echo $class ?>"><?php echo __($_alert['message']) ?></div>

<?php } ?>
