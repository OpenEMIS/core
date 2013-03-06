<?php 
$alertTypes = array();
$alertTypes['ok'] = 'alert_ok';
$alertTypes['error'] = 'alert_error';
$alertTypes['info'] = 'alert_info';
$alertTypes['warn'] = 'alert_warn';

if($this->Session->check('_alert')) {
	$_alert = $this->Session->read('_alert');
	$class = 'alert alert_view ' . $alertTypes[$_alert['type']];
	$dismiss = $_alert['dismissOnClick'] ? sprintf('title="%s"', __('Click to dismiss')) : '';
	unset($_SESSION['_alert']);
?>

	<div class="<?php echo $class; ?>" <?php echo $dismiss; ?>>
		<div class="alert_icon"></div>
		<div class="alert_content"><?php echo $_alert['message']; ?></div>
	</div>

<?php } ?>