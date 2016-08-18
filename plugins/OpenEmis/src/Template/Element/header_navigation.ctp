<?php
$session = $this->request->session();
$firstName = $session->check('Auth.User.first_name') ? $session->read('Auth.User.first_name') : 'System';
$lastName = $session->check('Auth.User.last_name') ? $session->read('Auth.User.last_name') : 'Administrator';

if (!isset($headerMenu)) {
	$headerMenu = [];
}

$roles = 'User Role: Principal';
if ($session->check('System.User.roles')) {
	$roles = $session->read('System.User.roles');
}
?>

<div class="header-navigation">
	<div class="username">
		<span><?= sprintf('%s %s', $firstName, $lastName) ?></span>
		<a class="btn" data-toggle="tooltip" data-placement="bottom" data-html="true" title="<?= $roles ?>"><i class="kd-role"></i></a>
	</div>

	<div class="btn-group">
        <a class="btn" href="<?= $this->Url->build($homeUrl) ?>">
            <i class="fa fa-home"></i>
        </a>
    </div>

    <?php
	if (isset($productListOptions) && $productListOptions) {
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
						echo '<a href="' . $this->Url->build($attr['url']) . '" target="' . $target. '">';
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
