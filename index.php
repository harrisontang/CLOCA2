<?php
    include ('tools.php');
	session_start();
	global $marshaler;
?>

<!DOCTYPE html>
<html>
<head>
	<title>Music Database Login</title>
</head>
<body>
	<h1>MUSIC DATABASE</h1>
	<h3>Login</h3>

	<?php
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {

			$email = $_POST['email'];
            $password= $_POST['password'];
			unset($_POST['email']);
			unset($_POST['password']);

			$result = loginTable($password, $email);
			$_SESSION['user'] = $result;

            if (gettype($_SESSION['user']['email']) == 'array') {
				header("Location: main.php");
            } else {
                echo $result;
				unset($_SESSION);
            }
		}
	?>

	<form method="post" action="">
		<label for="email">email:</label>
		<input type="text" name="email" required><br><br>
        <label for="password">password:</label>
		<input type="password" name="password" required><br><br>
		<input type="submit" value="Submit">
	</form>
	<br>
	<a href='register.php'>Register</a>
</body>
</html>
