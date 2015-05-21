<div id="advanced-search" class="search-wrapper alert search-box hidden">

	<button id="search-toggle" class="btn btn-default btn-xs close" type="button" alt="Collapse">×</button>
	<h4>Advanced Search</h4>

	<form role="form">
	<div class="form-group">
	  <label class="form-label" for="sel1">Education Programme:</label>
	  <div class="form-field">
		  <select class="form-control" id="sel1">
		    <option>Pre-Primary</option>
		    <option>Primary</option>
		    <option>Secondary 1 - 2 Expres or Normal (Academic)</option>
		    <option>Secondary 1 - 2 Normal (Technical) or equivalent</option>
		    <option>Secondary 3 - 4/5 Express or Normal (Academic)</option>
		    <option>Secondary 3 - 4 Normal (Technical) or equivalent</option>		    
		  </select>
	  </div>
	</div>

	<div class="form-group">
	  <label class="form-label" for="sel1">Area:</label>
	  <div class="form-field">
		<input type="text" class="form-control" id="usr">
	  </div>
	</div>

	<div class="form-group">
	  <label class="form-label" for="sel1">Location:</label>
	  <div class="form-field">
		<input type="text" class="form-control" id="usr">
	  </div>
	</div>

	<div class="form-group">
	  <label class="form-label" for="sel1">School:</label>
	  <div class="form-field">
		<input type="text" class="form-control" id="usr">
	  </div>
	</div>

	<div class="form-group">
	  <label class="form-label" for="sel1">Type:</label>
	  <div class="form-field">
		<input type="text" class="form-control" id="usr">
	  </div>
	</div>

	<div class="form-group">
	  <label class="form-label" for="sel1">Custom Field:</label>
	  <div class="form-field">
		<input type="text" class="form-control" id="usr">
	  </div>
	</div>

	<div class="form-group">
	  <label class="form-label" for="sel1">Country:</label>
	  <div class="form-field">
		  <select class="form-control" id="sel1">
		    <option>Singapore</option>
		    <option>Malaysia</option>
		    <option>Vietnam</option>
		    <option>Thailand</option>
		    <option>Australia</option>
		    <option>Korea</option>		    
		  </select>
	  </div>
	</div>  

	<div class="form-group">
	  <label class="form-label" for="sel1">Identity:</label>
	  <div class="form-field">
		  <select class="form-control" id="sel1">
		    <option>National ID</option>
		    <option>School</option>
		    <option>Passport</option>	    
		  </select>
	  </div>
	</div>  
	</form>

	<div class="panel-button">
		<a class="btn btn-xs" href="">Search</a>		
	</div>

</div>

<script type="text/javascript">   
	var box = $('#advanced-search');
	$('button#search-toggle').on('click', function () {
	  box.toggleClass('hidden');
	});
</script>