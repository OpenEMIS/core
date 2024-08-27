<div name="UserDetailsOpenEmisNo">
    <div class="row section-header header-space-lg"><?= __('Search By OpenEMIS ID') ?></div>
    <div class="input string">
        <label><?= __('OpenEMIS ID') ?></label>
        <input ng-model="selectedUserData.openemis_no"
               ng-change="unsetAllErrors()"
               type="string">
    </div>
</div>
