
<h3>Overview</h3>
<p>This plugin provides RESTful C.R.U.D. operations for <?= $_productName ?>.<br/>This documentation provides instructions on how to communicate with <?= $_productName ?> using external application.</p>

<p>For users who have access to <?= $_productName ?>, they are able to interect with this system by using a browser after logging in.<br/>However, the operations are limited to <a href="<?= $this->Url->build(['plugin'=>'Restful','controller'=>'Doc','action'=>'listing']) ?>">getting a list of records</a> and <a href="<?= $this->Url->build(['plugin'=>'Restful','controller'=>'Doc','action'=>'viewing']) ?>">getting a single record</a>.</p>

<p>The return result will be either in <i>json</i> string or <i>xml</i> schema format depending on the url provided.</p>

<ul>
	<li><a href="<?= $this->Url->build(['plugin'=>'Restful','controller'=>'Doc','action'=>'listing']) ?>">Getting a list of records</a></li>
	<li><a href="<?= $this->Url->build(['plugin'=>'Restful','controller'=>'Doc','action'=>'viewing']) ?>">Getting a single record</a></li>
	<li><a href="<?= $this->Url->build(['plugin'=>'Restful','controller'=>'Doc','action'=>'adding']) ?>">Adding a new record</a></li>
	<li><a href="<?= $this->Url->build(['plugin'=>'Restful','controller'=>'Doc','action'=>'editing']) ?>">Editing an existing record</a></li>
	<li><a href="<?= $this->Url->build(['plugin'=>'Restful','controller'=>'Doc','action'=>'deleting']) ?>">Deleting an existing record</a></li>
	<li><a href="<?= $this->Url->build(['plugin'=>'Restful','controller'=>'Doc','action'=>'curl']) ?>">Using CURL</a></li>
</ul>
