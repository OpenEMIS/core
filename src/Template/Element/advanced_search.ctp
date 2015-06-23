<div id="advanced-search" class="advanced-search-wrapper alert search-box hidden">

	<button id="search-toggle" class="btn btn-xs close" type="button" alt="Collapse">×</button>
	<h4>Advanced Search</h4>

	<form action="/styleguide/users/add" novalidate="novalidate" class="form-horizontal" accept-charset="utf-8" method="post">
		<div class="input select">
		  <label class="form-label">Education Programme:</label>
		  <div class="input-select-wrapper">	 
			  <select>
			    <option>Pre-Primary</option>
			    <option>Primary</option>
			    <option>Secondary 1 - 2 Expres or Normal (Academic)</option>
			    <option>Secondary 1 - 2 Normal (Technical) or equivalent</option>
			    <option>Secondary 3 - 4/5 Express or Normal (Academic)</option>
			    <option>Secondary 3 - 4 Normal (Technical) or equivalent</option>		    
			  </select>
		   </div>	  
		</div>

		<div class="input select">
		  <label class="form-label">Country:</label>
		  <div class="input-select-wrapper">
			  <select>
			    <option>Singapore</option>
			    <option>Malaysia</option>
			    <option>Indonesia</option>
			    <option>Australia</option>
			    <option>Vietnam</option>
			    <option>Thailand</option>		    
			  </select>
		  </div>
		</div>

		<div class="input text">
		  <label class="form-label">Area:</label>
		  <input type="text">
		</div>

		<div class="input text">
		  <label class="form-label">Custom Field:</label>
		  <input type="text">
		</div>
	</form>
	
	<a class="btn btn-default btn-xs" href="">Search</a>		

</div>

<script type="text/javascript">   
	var box = $('#advanced-search');
	$('button#search-toggle').on('click', function () {
	  box.toggleClass('hidden');
	});
</script>