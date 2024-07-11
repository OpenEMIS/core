<?php
$server_ip = $_SERVER['SERVER_ADDR'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Server IP Address</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      margin-top: 50px;
    }
  </style>
</head>
<body>
  <h1>Server IP Address</h1>
  <p id="server-ip"><?php echo $server_ip; ?></p>
</body>
</html>
