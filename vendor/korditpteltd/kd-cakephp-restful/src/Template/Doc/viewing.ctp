
<h3 id="top">Viewing a single record</h3>
	<p>To extract a single record, users have to provide the record model, record id, and append it on the url to use for querying.
	<br/>Record model could be in the form of single word like <b>notices</b> or dasherized words like <b>institution-institutions</b>.
	<br/>Record model in the form of single word belongs to the root application while dasherized models belongs to a plugin.
	<br/>So basically, dasherized models includes the name of its plugin as a prefix with a dash.
	<br/>The default format will be in <b>json</b> string whether the url consists of <b>.json</b> or not.
	<br/>To return an <b>xml</b> format, change <b>.json</b> to <b>.xml</b> on the url.</p>
<hr/>

<h3>Quick Links</h3>
	<ul>
		<li><a href="#basic-query">Basic query</a></li>
		<li><a href="#query-with-selected-fields">Query with selected fields</a></li>
		<li><a href="#query-with-containments">Query with containments</a></li>
		<li><a href="#query-with-containments-selected-fields">Query with containments and selected fields</a></li>
		<li><a href="#limitations">Limitations</a></li>
	</ul>
<hr/>

<h3 id="basic-query">Basic query</h3>
	<p>For example to extract a single <b>Institution</b> record and return it as a <b>json</b> string:</p>
	<pre>
		<?= $this->Url->build([
			'plugin'=>'Restful',
			'controller'=>'Restful',
			'action'=>'view',
			'institution-institutions',
			1,
			'_ext'=>'json', 
			'_method'=>'get'
			], true) ?>
	</pre>

	<p>The return result will be:</p>
	<pre>
	{
	    "data": {
	        "id": 1,
	        "name": "Atemkit Pre-School",
	        "alternative_name": "",
	        "code": "55A48",
	        "address": "4018 W Clearwater Ave",
	        "postal_code": "18976",
	        "contact_person": "Dr Sedore",
	        "telephone": "98765432",
	        "fax": "98765433",
	        "email": "joey@sedore.com",
	        "website": "http:\/\/www.education.gov\/",
	        "date_opened": "1980-01-01T00:00:00+0000",
	        "year_opened": 1980,
	        "date_closed": null,
	        "year_closed": 2016,
	        "longitude": "103.711",
	        "latitude": "1.32",
	        "area_id": 1,
	        "area_administrative_id": 1,
	        "institution_locality_id": 79,
	        "institution_type_id": 65,
	        "institution_ownership_id": 72,
	        "institution_status_id": 86,
	        "institution_sector_id": 2,
	        "institution_provider_id": 1,
	        "institution_gender_id": 8,
	        "institution_network_connectivity_id": 1,
	        "security_group_id": 2,
	        "modified_user_id": 1,
	        "modified": "2016-04-01T18:09:35+0000",
	        "created_user_id": 2,
	        "created": null,
	        "code_name": "55A48 - Atemkit Pre-School"
	    }
	}
	</pre>
	<a href="#top">Back to top</a>
<hr/>

<h3 id="query-with-selected-fields">Query with selected fields</h3>
	<p>If you would like to extact only selected fields rather than all of them, you can append <b>_fields=field1,field2</b> as a get parameter or name-value pair string to the url where the values are the field name. Separate multiple fields with a <b>comma</b> or a url encoded representation of a comma.</p>
	<p>For example to extract a single <b>Institution</b> record with only its <b>name, code and area_administrative_id</b>:</p>
	<pre>
		<?= $this->Url->build([
			'plugin'=>'Restful',
			'controller'=>'Restful',
			'action'=>'view',
			'institution-institutions',
			1,
			'_fields'=>'code,name,area_administrative_id',
			'_ext'=>'json', 
			'_method'=>'get'
			], true) ?>
	</pre>

	<p>The return result will be:</p>
	<pre>
	{
	    "data": {
	        "code": "55A48",
	        "name": "Atemkit Pre-School",
	        "area_administrative_id": 1,
	        "code_name": "55A48 - Atemkit Pre-School"
	    }
	}
	</pre>
	<a href="#top">Back to top</a>
<hr/>

<h3 id="query-with-containments">Query with containments</h3>
	<p>If you would like to include related information such as the information of foreign keys such as <b>area_administrative_id</b> in this case, append <b>_contain=model</b> as a get parameter or name-value pair string to the url. <b>model</b> would be the name of the foreign key model.</p>
	<p>For example to extract a single <b>Institution</b> record with its <b>area_administrative_id</b> information and return it as a <b>json</b> string:</p>
	<pre>
		<?= $this->Url->build([
			'plugin'=>'Restful',
			'controller'=>'Restful',
			'action'=>'view',
			'institution-institutions',
			1,
			'_contain' => 'AreaAdministratives',
			'_ext'=>'json', 
			'_method'=>'get'
			], true) ?>
	</pre>

	<p>The return result will be:</p>
	<pre>
	{
	    "data": {
	        "id": 1,
	        "name": "Atemkit Pre-School",
	        "alternative_name": "",
	        "code": "55A48",
	        "address": "4018 W Clearwater Ave",
	        "postal_code": "18976",
	        "contact_person": "Dr Sedore",
	        "telephone": "98765432",
	        "fax": "98765433",
	        "email": "joey@sedore.com",
	        "website": "http:\/\/www.education.gov\/",
	        "date_opened": "1980-01-01T00:00:00+0000",
	        "year_opened": 1980,
	        "date_closed": null,
	        "year_closed": 2016,
	        "longitude": "103.711",
	        "latitude": "1.32",
	        "area_id": 1,
	        "area_administrative_id": 1,
	        "institution_locality_id": 79,
	        "institution_type_id": 65,
	        "institution_ownership_id": 72,
	        "institution_status_id": 86,
	        "institution_sector_id": 2,
	        "institution_provider_id": 1,
	        "institution_gender_id": 8,
	        "institution_network_connectivity_id": 1,
	        "security_group_id": 2,
	        "modified_user_id": 1,
	        "modified": "2016-04-01T18:09:35+0000",
	        "created_user_id": 2,
	        "created": null,
	        "area_administrative": {
	            "id": 1,
	            "code": "1",
	            "name": "Singapore",
	            "is_main_country": 1,
	            "parent_id": 13,
	            "lft": 2,
	            "rght": 25,
	            "area_administrative_level_id": 1,
	            "order": 1,
	            "visible": 1,
	            "modified_user_id": null,
	            "modified": "2015-02-07T20:59:08+0000",
	            "created_user_id": 0,
	            "created": null
	        },
	        "code_name": "55A48 - Atemkit Pre-School"
	    }
	}
	</pre>

	<p>If you want to include more thn one related information, separate the model names by a <b>comma</b> or url encoded representation of a comma.</p>
	<p>For example to extract a single <b>Institution</b> record with its <b>area_administrative_id</b> and <b>institution_locality_id</b> information and return it as a <b>json</b> string:</p>
	<pre>
		<?= $this->Url->build([
			'plugin'=>'Restful',
			'controller'=>'Restful',
			'action'=>'view',
			'institution-institutions',
			1,
			'_contain' => 'AreaAdministratives,Localities',
			'_ext'=>'json', 
			'_method'=>'get'
			], true) ?>
	</pre>

	<p>The return result will be:</p>
	<pre>
	{
	    "data": {
	        "id": 1,
	        "name": "Atemkit Pre-School",
	        "alternative_name": "",
	        "code": "55A48",
	        "address": "4018 W Clearwater Ave",
	        "postal_code": "18976",
	        "contact_person": "Dr Sedore",
	        "telephone": "98765432",
	        "fax": "98765433",
	        "email": "joey@sedore.com",
	        "website": "http:\/\/www.education.gov\/",
	        "date_opened": "1980-01-01T00:00:00+0000",
	        "year_opened": 1980,
	        "date_closed": null,
	        "year_closed": 2016,
	        "longitude": "103.711",
	        "latitude": "1.32",
	        "area_id": 1,
	        "area_administrative_id": 1,
	        "institution_locality_id": 79,
	        "institution_type_id": 65,
	        "institution_ownership_id": 72,
	        "institution_status_id": 86,
	        "institution_sector_id": 2,
	        "institution_provider_id": 1,
	        "institution_gender_id": 8,
	        "institution_network_connectivity_id": 1,
	        "security_group_id": 2,
	        "modified_user_id": 1,
	        "modified": "2016-04-01T18:09:35+0000",
	        "created_user_id": 2,
	        "created": null,
	        "locality": {
	            "id": 79,
	            "name": "Urban",
	            "order": 2,
	            "visible": 1,
	            "editable": 1,
	            "default": 0,
	            "international_code": "",
	            "national_code": "",
	            "modified_user_id": 1,
	            "modified": "2013-12-10T09:03:31+0000",
	            "created_user_id": 0,
	            "created": null
	        },
	        "area_administrative": {
	            "id": 1,
	            "code": "1",
	            "name": "Singapore",
	            "is_main_country": 1,
	            "parent_id": 13,
	            "lft": 2,
	            "rght": 25,
	            "area_administrative_level_id": 1,
	            "order": 1,
	            "visible": 1,
	            "modified_user_id": null,
	            "modified": "2015-02-07T20:59:08+0000",
	            "created_user_id": 0,
	            "created": null
	        },
	        "code_name": "55A48 - Atemkit Pre-School"
	    }
	}
	</pre>
	<a href="#top">Back to top</a>
<hr/>

<h3 id="query-with-containments-selected-fields">Query with containments and selected fields</h3>
	<p>If you would like to extact only selected fields rather than all of them, you can append <b>_fields=field1,field2</b> as a get parameter or name-value pair string to the url where the values are the field name. Separate multiple fields with a <b>comma</b> or a url encoded representation of a comma.</p>
	<p>For example to extract a single <b>Institution</b> record with only its <b>name, code and area_administrative_id</b>:</p>
	<pre>
		<?= $this->Url->build([
			'plugin'=>'Restful',
			'controller'=>'Restful',
			'action'=>'view',
			'institution-institutions',
			1,
			'_fields'=>'code,name,area_administrative_id',
			'_contain'=>'AreaAdministratives',
			'_ext'=>'json', 
			'_method'=>'get'
			], true) ?>
	</pre>

	<p>The return result will be:</p>
	<pre>
	{
	    "data": {
	        "code": "55A48",
	        "name": "Atemkit Pre-School",
	        "area_administrative_id": 1,
	        "area_administrative": {
	            "id": 1,
	            "code": "1",
	            "name": "Singapore",
	            "is_main_country": 1,
	            "parent_id": 13,
	            "lft": 2,
	            "rght": 25,
	            "area_administrative_level_id": 1,
	            "order": 1,
	            "visible": 1,
	            "modified_user_id": null,
	            "modified": "2015-02-07T20:59:08+0000",
	            "created_user_id": 0,
	            "created": null
	        },
	        "code_name": "55A48 - Atemkit Pre-School"
	    }
	}
	</pre>
	<a href="#top">Back to top</a>
<hr/>

<h3 id="limitations">Limitations</h3>
	<p>While users can include information for the main model's related models in their url query, other models directly related to those related models could not be included as well.</p>
	<p>While users can select which fields from the main model to be included in the result, currently they are not able to select only some of the related models' fields.</p>
	<a href="#top">Back to top</a>
<hr/>
