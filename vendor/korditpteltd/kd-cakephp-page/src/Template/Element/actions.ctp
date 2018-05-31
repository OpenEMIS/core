<?php
$actionItem = '<li role="presentation"><a href="%s" role="menuitem" tabindex="-1"><i class="%s"></i>%s</a></li>';
$rowActions = !is_array($data) ? $data->rowActions : $data['rowActions'];
?>

<div class="dropdown">
    <button class="btn btn-dropdown action-toggle" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="true">
        <?= __('Select') ?><span class="caret-down"></span>
    </button>

    <ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
        <div class="dropdown-arrow"><i class="fa fa-caret-up"></i></div>

        <?php
        foreach ($rowActions as $action) {
            $action['href'] = array_key_exists('url', $action) ? $this->Page->getUrl($action['url']) : '';
            echo sprintf($actionItem, $action['href'], $action['icon'], $action['title']);
        }
        ?>
    </ul>
</div>
