<?php
    include ('tools.php');
?>

<!DOCTYPE html>
<html>
<head>
	<title>Music Database Register Page</title>
</head>
<body>
	<h1>MUSIC DATABASE</h1>
	<h3>Register</h3>

	<?php
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {

			$email = $_POST['email'];
            $password = $_POST['password'];
            $username = $_POST['username'];
			unset($_POST['email']);
			unset($_POST['password']);
            unset($_POST['username']);

            $exists = scanLoginEmailCheck($email);
			if ($exists == true) {
				echo 'email already exists: ' . $email;
			} else {
				register($password, $username, $email);
			}
		}
	?>

	<form method="post" action="">
		<label for="email">email:</label>
		<input type="text" name="email" required><br><br>
        <label for="username">username:</label>
		<input type="text" name="username" required><br><br>
        <label for="password">password:</label>
		<input type="password" name="password" required><br><br>
		<input type="submit" value="Submit">
	</form>
	<br>
	<a href='index.php'>Login Page</a>
</body>
</html>