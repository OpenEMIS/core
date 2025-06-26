<div class="row section-header header-space-lg"><?= __('Location') ?></div>
<div class="input string">
    <label><?= __('Address') ?></label>
    <textarea ng-model="selectedUserData.address" type="string"></textarea>
</div>
<div class="input string">
    <label><?= __('Postal Code') ?></label>
    <input ng-model="selectedUserData.postalCode" type="string">
</div>
<!-- Address area start -->
<div class="row section-header header-space-lg"><?= __('Address Area') ?></div>
<div class="input string" id="addressArea_textbox" style="visibility:hidden">
    <label><?= __('Address Area') ?></label>
    <input ng-model="selectedUserData.addressArea.name" type="string" ng-disabled="true">
</div>
<div class="input string" id="addressArea_dropdown">
    <label><?= __('Address Area') ?></label>
    <div
        class="tree-form"
        id="address_area_id"
        ng-controller="SgTreeCtrl as SgTree"
        ng-init="SgTree.model='Area.AreaAdministratives'; SgTree.outputValue=addressAreaId; SgTree.userId=2; SgTree.displayCountry=0; SgTree.triggerOnChange=false;">
        <kd-tree-dropdown-ng id="address_area_id-tree"
                             expand-parent="SgTree.triggerLoad(refreshList)"
                             output-model="addressAreaOutputModelText" model-type="single"
                             text-config="textConfig"></kd-tree-dropdown-ng>
    </div>
</div>
<!-- Address area end -->
<!-- Address area start -->
<div class="row section-header header-space-lg"><?= __('Birthplace Area') ?></div>
<div class="input string" id="birthplaceArea_textbox" style="visibility:hidden">
    <label><?= __('Birthplace Area') ?></label>
    <input ng-model="selectedUserData.birthplaceArea.name" type="string" ng-disabled="true">
</div>
<div class="input string" id="birthplaceArea_dropdown">
    <label><?= __('Birthplace Area') ?></label>
    <div
        class="tree-form"
        id="birthplace_area"
        ng-controller="SgTreeCtrl as SgTree"
        ng-init="SgTree.model='Area.AreaAdministratives'; SgTree.outputValue=birthplaceAreaId; SgTree.userId=2; SgTree.displayCountry=0; SgTree.triggerOnChange=false; ">
        <kd-tree-dropdown-ng id="birthplace_area-tree"
                             expand-parent="SgTree.triggerLoad(refreshList)"
                             output-model="birthplaceAreaOutputModelText" model-type="single"
                             text-config="textConfig"></kd-tree-dropdown-ng>
    </div>
</div>
<!-- Address area end -->
