<?php
    include ('tools.php');
    session_start();
?>

<!DOCTYPE html>
<html>
<head>
	<title>Music Database</title>
</head>
<body>
	<h1>MUSIC DATABASE</h1>
	<h3>Welcome back <?php echo $_SESSION['user']['user_name']['S'] ?>.</h3>
    <a href='query.php'>Query page</a><br>
    <a href='subscriptions.php'>Subscriptions</a>
    <br><br>
    <a href='index.php'><button>Log out</button></a>
</body>
</html>







