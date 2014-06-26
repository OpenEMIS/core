<?php if(strpos($_SERVER['SERVER_NAME'], 'dev') !== false
	  || strpos($_SERVER['SERVER_NAME'], 'tst') !== false
	  || strpos($_SERVER['SERVER_NAME'], 'localhost') !== false) { ?>

<style type="text/css">
.sql-dump { margin-top: 30px; }
.sql-dump table { border: 1px solid #CCC; margin: 10px; }
.sql-dump th { padding: 3px; }
.sql-dump td { border-top: 1px solid #CCC; padding: 5px 0; }
.sql-dump .query { width: 900px; }
</style>

<div class="sql-dump" align="center">
	<?php echo $sql_dump = $this->element('sql_dump'); ?>
</div>

<?php } ?>