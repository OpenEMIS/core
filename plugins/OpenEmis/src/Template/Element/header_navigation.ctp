<?php
$session = $this->request->session();
if($session->check('Auth.User.first_name')){ //POCOR-7164
	$firstName = $session->check('Auth.User.first_name') ? $session->read('Auth.User.first_name') : 'System';
	$middleName = $session->check('Auth.User.middle_name') ? $session->read('Auth.User.middle_name') : '';
	$thirdName = $session->check('Auth.User.third_name') ? $session->read('Auth.User.third_name') : '';
	$lastName = $session->check('Auth.User.last_name') ? $session->read('Auth.User.last_name') : 'Administrator';
}
//POCOR-7164

if (!isset($headerMenu)) {
	$headerMenu = [];
}

$roles = 'User Role: Principal';
if ($session->check('System.User.roles')) {
	$roles = $session->read('System.User.roles');
}
?>
<div class="header-navigation">
	<?php if($session->check('Auth.User.first_name')){ ?> <!--POCOR-7164-->
	<div class="username">
		<span><?= sprintf('%s %s %s %s', $firstName, $middleName,$thirdName,$lastName) ?></span>
		<a class="btn" data-toggle="tooltip" data-placement="bottom" data-html="true" title="<?= $roles ?>"><i class="kd-role"></i></a>
	</div>

	<div class="btn-group">
        <a class="btn" href="<?= $this->Url->build($homeUrl) ?>">
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
				foreach ($headerMenu as $name => $attr) {
					if ($name != '_divider') {
						$target = isset($attr['target']) ? $attr['target'] : '_self';
						echo '<li>';
						echo $this->Html->link('<i class="fa ' . $attr['icon'] . '"></i><span> ' . __($name) . '</span>', $attr['url'], $attr);
						echo '</li>';
					} else {
						echo '<li class="divider"></li>';
					}
				}
				?>
			</div>
		</ul>
	</div>
	<?php  }?> <!--POCOR-7164-->
</div>
