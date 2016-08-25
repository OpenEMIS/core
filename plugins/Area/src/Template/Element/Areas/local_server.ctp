<div class="input">
    <label>Local</label>
    <div class="input-form-wrapper">
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Area Level</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (!is_null($areasTableArray)) {
                                foreach ($areasTableArray as $key => $value) {
                                    echo '<tr>
                                        <td>'
                                             . htmlentities($value['id']) .
                                        '</td>
                                        <td>'
                                             . htmlentities($value['area_level_id']) .
                                        '</td>
                                        <td>'
                                             . htmlentities($value['code']) .
                                        '</td>
                                        <td>'
                                             . htmlentities($value['name']) .
                                        '</td>
                                    <tr>'
                                    ;
                                }
                            } else {

                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
