<footer>
    <?php if (!$footerText) : ?>
    <?= __('Copyright') ?> &copy; 2015 - <?= date('Y') ?>  <?=$footerBrand ?>. <?= __('All rights reserved.') ?>
    <?php else: ?>
    <?= str_replace('{{currentYear}}', date('Y'), $footerText) ?>
    <?php endif; ?>
    | <?= __('Version') . ' ' . $SystemVersion ?>
</footer>
