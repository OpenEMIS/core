<div name="UserDetailsIdentity">
    <div class="row section-header header-space-lg"><?= __('Search By Identity') ?></div>
    <div ng-class="nationality_class" class="input select">
        <label><?= __('Nationality') ?></label>
        <div class="input-select-wrapper">
            <select name="User[nationality_id]" id="user-nationality_id"
                    ng-options="option.id as option.name for option in nationalitiesOptions"
                    ng-model="selectedUserData.nationality_id"
                    ng-change="changeNationality();">
                <option value="">-- <?= __('Select') ?> --</option>
            </select>
        </div>
        <div ng-if="postResponse.error.nationalities[0].nationality_id" class="error-message">
            <p ng-repeat="error in postResponse.error.nationalities[0].nationality_id">{{ error }}</p>
        </div>
        <div ng-if="error.nationality_id" class="error-message">
            <p>{{ error.nationality_id }}</p>
        </div>
    </div>
    <div ng-class="identity_type_class" class="input select">
        <label><?= __('Identity Type') ?></label>
        <div class="input-select-wrapper">
            <select name="User[identities_type_id]" id="user-identities_type_id"
                    ng-options="option.id as option.name for option in identityTypeOptions"
                    ng-model="selectedUserData.identity_type_id"
                    ng-change="changeIdentityType()">
                <option value="">-- <?= __('Select') ?> --</option>
            </select>
        </div>
        <div ng-if="postResponse.error.identities[0].identity_type_id" class="error-message">
            <p ng-repeat="error in postResponse.error.identities[0].identity_type_id">{{ error
                }}</p>
        </div>
        <div ng-if="error.identity_type_id" class="error-message">
            <p>{{ error.identity_type_id }}</p>
        </div>
    </div>
    <div ng-class="identity_class" class="input">
        <label><?= __('{{selectedUserData.identity_type_name ? selectedUserData.identity_type_name : "Identity Number"}}') ?></label>
        <input ng-model="selectedUserData.identity_number"
               ng-change="changeIdentityNumber()"
               type="string">
        <div ng-if="error.identity_number" class="error-message">
            <p>{{ error.identity_number }}</p>
        </div>
    </div>
</div>
