<footer>
    <?php if (!$footerText) : ?>
    <?= __('Copyright') ?> &copy; <?= date('Y') ?>  OpenEMIS. <?= __('All rights reserved.') ?>
    <?php else: ?>
    <?= $footerText ?>
    <?php endif; ?>
    | <?= __('Version') . ' ' . $SystemVersion ?>
</footer>
