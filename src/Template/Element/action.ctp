<table id="table-reorder" class="table table-curved table-sortable">
  <thead>
    <tr>
      <th>Icons</th>
      <th>Thumbnail</th>
      <th>Actions</th>
      <th>Reorder</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>
        <div class="table-icon">
          <i class="kd-staff fa-2x"></i>
        </div>
      </td>
      <td>
          <a class="table-thumb" href="#">
            <img alt="50x50" data-src="holder.js/50x50" data-holder-rendered="true">
        </a>
      </td>
      <td>
        <div class="dropdown">
          <button class="btn btn-default action-toggle outline-btn" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="true">Select<span class="caret-down"></span></button>
        <ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
        <div class="dropdown-arrow">
          <i class="fa fa-caret-up"></i>
        </div>
          <li role="presentation">
            <a role="menuitem" tabindex="-1" href="#"><i class="fa fa-search-plus"></i>View</a>
          </li>
          <li role="presentation">
            <a role="menuitem" tabindex="-1" href="#"><i class="fa fa-pencil"></i>Edit</a>
          </li>
          <li role="presentation">
            <a role="menuitem" tabindex="-1" href="#"><i class="fa fa-trash"></i>Remove</a>
          </li>
        </ul>
        </div>
      </td>
      <td class="sorter">  
        <div class="reorder-icon">
          <a href="#"><i class="fa fa-arrows-alt"></i></a>
        </div>
      </td>
    </tr>
    <tr>
      <td>
        <div class="table-icon">
          <i class="kd-students fa-2x"></i>
        </div>
      </td>
      <td>
          <a class="table-thumb" href="#">
            <img alt="50x50" data-src="holder.js/50x50" data-holder-rendered="true">
        </a>
      </td>
      <td>
        <div class="dropdown">
          <button class="btn btn-default action-toggle outline-btn" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="true">Select<span class="caret-down"></span></button>
        <ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
        <div class="dropdown-arrow">
          <i class="fa fa-caret-up"></i>
        </div>
          <li role="presentation">
            <a role="menuitem" tabindex="-1" href="#"><i class="fa fa-search-plus"></i>View</a>
          </li>
          <li role="presentation">
            <a role="menuitem" tabindex="-1" href="#"><i class="fa fa-pencil"></i>Edit</a>
          </li>
          <li role="presentation">
            <a role="menuitem" tabindex="-1" href="#"><i class="fa fa-trash"></i>Remove</a>
          </li>
        </ul>
        </div>
      </td>
      <td class="sorter">  
        <div class="reorder-icon">
          <i class="fa fa-arrows-alt"></i>
        </div>
      </td>
    </tr>
  </tbody>    
</table>

<script type="text/javascript">

    $("#table-reorder").rowSorter({
        handler: ".sorter",
        onDrop: function(tbody, row, new_index, old_index) {
            $("#log").html(old_index + ". row moved to " + new_index);
        }
    });

</script>