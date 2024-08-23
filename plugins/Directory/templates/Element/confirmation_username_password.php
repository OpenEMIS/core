<div class="input string required">
    <label><?= __('Username') ?></label>
    <input ng-model="selectedUserData.username"
           type="string"
           ng-disabled="disableFields.username">
    <div ng-if="error.username" class="error-message">
        <p>{{ error.username }}</p>
    </div>
</div>
<div class="input password required"  ng-show="!disableFields.password">
    <label>
        <?= __('Password') ?>&nbsp&nbsp;
        <i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="<?= $tooltipMessage ?>"></i>
    </label>
    <input ng-model="selectedUserData.password"
           type="string"
           ng-disabled="disableFields.password">
    <div ng-if="error.password" class="error-message">
        <p>{{ error.password }}</p>
    </div>
</div>
