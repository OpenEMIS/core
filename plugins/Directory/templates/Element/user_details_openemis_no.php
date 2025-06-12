<div name="UserDetailsOpenEmisNo">
    <?php $dynamicOpenemisNoHeader = (isset($dynamicOpenemisNoHeader) && !empty($dynamicOpenemisNoHeader)) ? h($dynamicOpenemisNoHeader) : __('OpenEMIS ID'); ?>
    <div class="row section-header header-space-lg"><?= __('Search By ') . $dynamicOpenemisNoHeader ?></div>
    <div class="input string">
        <label><?= $dynamicOpenemisNoHeader ?></label> <!-- POCOR-8646 -->
        <input ng-init="dynamicOpenemisNoHeader = '<?= $dynamicOpenemisNoHeader ?>';"
            ng-model="selectedUserData.openemis_no"
               ng-change="unsetAllErrors()"
               type="string">
    </div>
</div>
