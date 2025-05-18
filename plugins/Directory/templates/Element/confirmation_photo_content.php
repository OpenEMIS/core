<div class="input string row-content">
    <label><?= __('Photo Content') ?></label>
    <div class="fileinput fileinput-new fileinput-preview">
        <div class="table-thumb mb-16">
                <!-- POCOR-8917 Show the image only if photo_base_64 exists -->
                <img ng-if="selectedUserData.photo_base_64"
                     ng-src="data:image/png;base64,{{ selectedUserData.photo_base_64 }}"
                     class="profile-photo"
                     alt="User Photo">

                <!-- Show the icon only if there is no photo -->
            <div class="profile-image-thumbnail" ng-if="!selectedUserData.photo_base_64">
                <i class="kd-staff" ></i>
            </div>

        </div>
        <div class="file-input-buttons">
            <p>* <?= __('Advisable photo dimension 90 by 115') ?><br/>
                * <?= __('Format Supported: .jpg, .jpeg, .png, .gif') ?></p>
            <span class="btn btn-default btn-file" style="font-size: 12px !important;">
                <span class="fileinput-new">
                    <i class="fa fa-folder"></i>
                    <span style="font-size: 12px;"><?= __('Select File') ?></span>
                </span>
                <input id="image-file" class="file-input" type="file" onchange="savePhoto(this)">
            </span>
        </div>
    </div>
</div>

