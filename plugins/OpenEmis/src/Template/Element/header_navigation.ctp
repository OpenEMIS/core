<?php
$session = $this->request->session();
$firstName = $session->check('Auth.User.first_name') ? $session->read('Auth.User.first_name') : 'System';
$lastName = $session->check('Auth.User.last_name') ? $session->read('Auth.User.last_name') : 'Administrator';
$userId = $session->check('Auth.User.id') ? $session->read('Auth.User.id') : '';
$homeUrl = $session->check('System.home') ? $session->read('System.home') : [];

$dropdown = [
	'About' => [
		'url' => ['plugin' => false, 'controller' => 'About', 'action' => 'index'], 
		'icon' => 'fa-info-circle'
	],
	'Preferences' => [
		'url' => ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index'], 
		'icon' => 'fa-cog'
	],
	'_divider',
	'Logout' => [
		'url' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'logout'],
		'icon' => 'fa-power-off'
	]
];
?>

<div class="header-navigation">
	<div class="username">
		<span><?php echo sprintf('%s %s', $firstName, $lastName) ?></span>
		<a class="btn" data-toggle="tooltip" data-placement="bottom" title="User Role: Principal"><i class="kd-role"></i></a>
	</div>

	<div class="btn-group">
		<a class="btn" href="<?php echo $this->Url->build($homeUrl) ?>">
			<i class="fa fa-home"></i>
		</a>
	</div>
	
	<?php 
	if (isset($showProductList) && $showProductList) {
		echo $this->element('OpenEmis.product_list');
	}
	?>

	<div class="btn-group">
		<button class="btn dropdown-toggle" data-toggle="dropdown" href="#">
			<i class="fa kd-ellipsis"></i>
		</button>

		<ul aria-labelledby="dropdownMenu" role="menu" class="dropdown-menu more-menu">
			<div class="dropdown-arrow"><i class="fa fa-caret-up"></i></div>
			<div class="more-menu-item">
				<?php
				foreach ($dropdown as $name => $attr) {
					if ($name != '_divider') {
						echo '<li>';
						echo '<a href="' . $this->Url->build($attr['url']) . '">';
						echo '<i class="fa ' . $attr['icon'] . '"></i>';
						echo '<span> ' . __($name) . '</span>';
						echo '</a>';
						echo '</li>';
					} else {
						echo '<li class="divider"></li>';
					}
				}
				?>
			</div>			
		</ul>
	</div>
</div>
