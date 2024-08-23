<div name="user_details_user_type_id">
    <div class="input select required error">
        <label><?= __('User Type') ?></label>
        <div class="input-select-wrapper">
            <select name="User[user_type_id]" id="user-user_type_id"
                    ng-options="option.id as option.name for option in userTypeOptions"
                    ng-model="selectedUserData.user_type_id"
                    ng-change="changeUserType()">
                <option value="">-- <?= __('Select') ?> --</option>
            </select>
        </div>
        <div ng-if="error.user_type_id" class="error-message">
            <p>{{ error.user_type_id }}</p>
        </div>
    </div>
</div>
