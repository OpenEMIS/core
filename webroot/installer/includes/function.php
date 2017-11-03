<?php
  ini_set('memory_limit', '5120M');
  set_time_limit ( 0 );

  function filter($data) {
    $data = trim(htmlentities(strip_tags($data)));
    if (get_magic_quotes_gpc()) {
      $data = stripslashes($data);
    } else {
      $data = mysql_real_escape_string($data);
    }
    return $data;
  }

	function getBaseUrl() {
		echo "http://" . $_SERVER['HTTP_HOST'] . "/";
	}

	function getHostName() {
		echo $_SERVER['SERVER_NAME'];
	}

	function getHostPort() {
	 	echo $_SERVER['SERVER_PORT'];
	}

  // remove_comments will strip the sql comment lines out of an uploaded sql file specifically for mssql and postgres type files in the install....
  function remove_comments(&$output) {
		$lines = explode("\n", $output);
		$output = "";

		// try to keep mem. use down
		$linecount = count($lines);

		$in_comment = false;
		for($i = 0; $i < $linecount; $i++) {
			if( preg_match("/^\/\*/", preg_quote($lines[$i])) ) {
				$in_comment = true;
			}

			if( !$in_comment ) {
				$output .= $lines[$i] . "\n";
			}

			if( preg_match("/\*\/$/", preg_quote($lines[$i])) ) {
				$in_comment = false;
			}
		}
		unset($lines);
		return $output;
	}

	// remove_remarks will strip the sql comment lines out of an uploaded sql file
	function remove_remarks($sql) {
		$lines = explode("\n", $sql);

		// try to keep mem. use down
		$sql = "";

		$linecount = count($lines);
		$output = "";

		for ($i = 0; $i < $linecount; $i++) {
			if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0)) {
				if (isset($lines[$i][0]) && $lines[$i][0] != "#") {
					$output .= $lines[$i] . "\n";
				} else {
					$output .= "\n";
				}
				
				// Trading a bit of speed for lower mem. use here.
				$lines[$i] = "";
			}
		}
		return $output;
	}

  // split_sql_file will split an uploaded sql file into single sql statements. Note: expects trim() to have already been run on $sql.
	function split_sql_file($sql, $delimiter) {
		// Split up our string into "possible" SQL statements.
		$tokens = explode($delimiter, $sql);

    // try to save mem.
    $sql = "";
    $output = array();

    // we don't actually care about the matches preg gives us.
    $matches = array();

    // this is faster than calling count($oktens) every time thru the loop.
    $token_count = count($tokens);
    for ($i = 0; $i < $token_count; $i++) {
      // Don't wanna add an empty string as the last thing in the array.
      if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0))) {
        // This is the total number of single quotes in the token.
        $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
            
        // Counts single quotes that are preceded by an odd number of backslashes, which means they're escaped quotes.
        $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

        $unescaped_quotes = $total_quotes - $escaped_quotes;

        // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
        if (($unescaped_quotes % 2) == 0) {
          // It's a complete sql statement.
          $output[] = $tokens[$i];
               
          // save memory.
          $tokens[$i] = "";
        } else {
          // incomplete sql statement. keep adding tokens until we have a complete one. $temp will hold what we have so far.
          $temp = $tokens[$i] . $delimiter;
               
          // save memory..
          $tokens[$i] = "";

          // Do we have a complete statement yet?
          $complete_stmt = false;

          for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++) {
            // This is the total number of single quotes in the token.
            $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
               
            // Counts single quotes that are preceded by an odd number of backslashes, which means they're escaped quotes.
            $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

            $unescaped_quotes = $total_quotes - $escaped_quotes;

            if (($unescaped_quotes % 2) == 1) {
              // odd number of unescaped quotes. In combination with the previous incomplete statement(s), we now have a complete statement. (2 odds always make an even)
              $output[] = $temp . $tokens[$j];

              // save memory.
              $tokens[$j] = "";
              $temp = "";

              // exit the loop.
              $complete_stmt = true;
              // make sure the outer loop continues at the right point.
              $i = $j;
            } else {
              // even number of unescaped quotes. We still don't have a complete statement. (1 odd and 1 even always make an odd)
              $temp .= $tokens[$j] . $delimiter;
                     
              // save memory.
              $tokens[$j] = "";
            }
          } // for..
        } // else
      }
    }
    return $output;
  }

  function settingEnvironment() {
    session_start();

    foreach($_REQUEST as $key => $value) {
      $data[$key] = filter($value);
    }

    $dbHost = $data['hostname'];
    $dbPort = $data['port'];
    $dbName = str_replace(' ', '_', $data['databaseName']);
    $dbUsername = $data['username'];
    $dbPassword = $data['password'];

    $_SESSION['DB_HOST'] = $dbHost;
    $_SESSION['DB_USER'] = $dbUsername;
    $_SESSION['DB_PASS'] = $dbPassword;
    $_SESSION['DB_NAME'] = str_replace(' ', '_', $data['databaseName']);

    $content = "
    <?php
      class DATABASE_CONFIG {
        public $default = array(
          'datasource' => 'Database/Mysql',
          'persistent' => false,
          'host' => '$dbHost',
          'login' => '$dbUsername',
          'password' => '$dbPassword',
          'database' => '$dbName',
          'prefix' => '',
          'encoding' => 'utf8',
        );

        public $test = array(
          'datasource' => 'Database/Mysql',
          'persistent' => false,
          'host' => 'localhost',
          'login' => 'user',
          'password' => 'password',
          'database' => 'test_database_name',
          'prefix' => '',
          'encoding' => 'utf8',
        );
      }
    ?>
    ";
    $fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/installer/app/Config/database.php","wb");
    fwrite($fp,$content);
    fclose($fp);

    //$dbms_schema = $_SERVER['DOCUMENT_ROOT'] . '/test/installer/app/Sql/OpenEmis_School.sql';
   
    //$sql_query = @fread(@fopen($dbms_schema, 'r'), @filesize($dbms_schema)) or die('problem ');
    //$sql_query = remove_remarks($sql_query);
    //$sql_query = split_sql_file($sql_query, ';');

    //$host = 'localhost';
    //$user = 'alantakashi';
    //$pass = 'alan790304';
    //$db = 'test';
    //

    //mysql_connect($_SESSION['DB_HOST'], $_SESSION['DB_USER'], $_SESSION['DB_PASS']) or die("Couldn't make connection.");
    //mysql_select_db($_SESSION['DB_NAME']) or die('error database selection');

    //$i=1;
    //foreach($sql_query as $sql){
    //  echo $i++;
    //  echo "";
    //  mysql_query($sql) or die('error in query');
    //}

    header("Location: DatabaseSetup");

	}

  function databaseSetup() {
    //$link = mysql_connect($_SESSION['DB_HOST'], $_SESSION['DB_USER'], $_SESSION['DB_PASS']) or die("Couldn't make connection.");
    //$sql="CREATE DATABASE $dbName";
    //if (mysqli_query($link,$sql)) {
    //    echo "Database my_db created successfully";
    //} else {
    //    echo "Error creating database: " . mysqli_error($link);
    //}
	
	$tmpdb = $_POST['tmpdb'];
		$tmphost = $_POST['tmphost'];
		$tmpusername = $_POST['tmpusername'];
		$tmppassword = $_POST['tmppassword'];
		
		$content = "
		<?php
		  class DATABASE_CONFIG {
			public $default = array(
			  'datasource' => 'Database/Mysql',
			  'persistent' => false,
			  'host' => '" . $tmphost . "',
			  'login' => '" . $tmpusername . "',
			  'password' => '" . $tmppassword . "',
			  'database' => '" . str_replace(' ', '_', $tmpdb) . "',
			  'prefix' => '',
			  'encoding' => 'utf8',
			);

			public $test = array(
			  'datasource' => 'Database/Mysql',
			  'persistent' => false,
			  'host' => 'localhost',
			  'login' => 'user',
			  'password' => 'password',
			  'database' => 'test_database_name',
			  'prefix' => '',
			  'encoding' => 'utf8',
			);
		  }
		?>
		";
		$fp = fopen("app/Config/database.php","wb");
		fwrite($fp,$content);
		fclose($fp);
		
		$dbms_schema = 'app/Sql/OpenEmis_School.sql';
   
		$sql_query = fread(fopen($dbms_schema, 'r'), filesize($dbms_schema)) or die(mysql_error());
		$sql_query = remove_remarks($sql_query);
		$sql_query = split_sql_file($sql_query, ';');

		$link = mysql_connect($tmphost, $tmpusername, $tmppassword) or die("Connection failed.");
		mysql_query("CREATE DATABASE " . $tmpdb, $link) or die ("Failed to create database.");
		mysql_select_db($tmpdb, $link) or die("Failed to select database.");
		//mysql_connect($dbHost, $dbUsername, $dbPassword) or die(mysql_error());

		$i=1;
		foreach($sql_query as $sql){
		//  echo $i++;
		//  echo "";
			mysql_query($sql) or die(mysql_error());
		}

		header("Location: InstallationComplete");
		exit();
  }
?>