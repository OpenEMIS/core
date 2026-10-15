<?php
/**
 * POCOR-9801: apply product Colour to Angular datepicker/buttons.
 * Must load AFTER angular STYLE_GUIDE CSS (themes.scss paints .btn black
 * for custom hex values that are not in the fixed openemis-* palette).
 */
$productColour = isset($productColour) ? ltrim((string)$productColour, '#') : '6699CC';
if (!preg_match('/^[0-9A-Fa-f]{6}$/', $productColour)) {
    $productColour = '6699CC';
}
$productColour = strtoupper($productColour);

// Match AppController::darkenColour($rgb, 2)
$parts = str_split($productColour, 2);
$secondaryColour = '';
foreach ($parts as $part) {
    $secondaryColour .= sprintf('%02X', (int)floor(hexdec($part) / 2));
}
?>
<style id="pocor-9801-angular-theme-colour">
/* Angular kd-datepicker calendar buttons + related themed buttons */
app-root .btn,
app-root .btn-color,
app-root .btn-input,
app-root .kdx-datepicker .btn,
app-root .kdx-datepicker .btn-input,
app-root .kdx-datepicker-wrapper .btn,
app-root .kdx-datepicker-wrapper .btn-input,
app-root .dropdown-menu .btn {
    background-color: #<?= h($productColour) ?> !important;
    border-color: #<?= h($productColour) ?> !important;
    color: #FFF !important;
}
app-root .btn:hover,
app-root .btn:focus,
app-root .btn-input:hover,
app-root .btn-input:focus,
app-root .kdx-datepicker .btn:hover,
app-root .kdx-datepicker .btn-input:hover {
    background-color: #<?= h($secondaryColour) ?> !important;
    border-color: #<?= h($secondaryColour) ?> !important;
    color: #FFF !important;
}
/* POCOR-9858: page header toolbar buttons (back / info / download). The styleguide's
   .kdx-toolbar .btn {background-color:#f9f9f9 !important} is more specific than app-root .btn,
   so the buttons stayed light grey while the icon was forced white above (invisible).
   Match the other OpenEMIS toolbar buttons: product colour with a white icon. */
/* POCOR-9851: toolbar.scss forces a grey background on page header toolbar buttons (e.g. Export) */
app-root .kdx-toolbar .btn,
app-root .kdx-toolbar .kdx-toolbar-left .btn,
app-root .kdx-toolbar .kdx-toolbar-left .btn:first-child {
    background-color: #<?= h($productColour) ?> !important;
    border-color: #<?= h($productColour) ?> !important;
    color: #FFF !important;
}
app-root .kdx-toolbar .btn:hover,
app-root .kdx-toolbar .btn:focus,
app-root .kdx-toolbar .kdx-toolbar-left .btn:hover,
app-root .kdx-toolbar .kdx-toolbar-left .btn:focus {
    background-color: #<?= h($secondaryColour) ?> !important;
    border-color: #<?= h($secondaryColour) ?> !important;
    color: #FFF !important;
}
</style>
