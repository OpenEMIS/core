<?php
echo $this->Html->script('Page.../plugins/scrolltabs/js/jquery.mousewheel', ['block' => true]);
echo $this->Html->script('Page.../plugins/scrolltabs/js/jquery.scrolltabs', ['block' => true]);
?>

<?php if (isset($tabs)) : ?>
    <div id="tabs" class="nav nav-tabs horizontal-tabs">
        <?php foreach($tabs as $tab): ?>
            <span role="presentation" class="<?= $tab['active'] == true ? 'tab-active' : '' ?>"><?= $this->Html->link(__($tab['title']), $tab['url']) ?></span>
        <?php endforeach; ?>
    </div>
<?php endif ?>
