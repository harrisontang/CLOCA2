<?php
    include ('tools.php');
    session_start();

    $query = array();
?>

<!DOCTYPE html>
<html>
<head>
	<title>Music Database</title>
</head>
<body>
	<h1>MUSIC DATABASE QUERY PAGE</h1>
	<h3>Query the music database</h3>
    <br>
    <p>Enter a title, artist, or combination of any three to find like entries in the database.<p>
    
    <p>Press the SUBSCRIBE button to add them to your subscriptions!<p>
    <a href='main.php'>Go back to main</a><br>
    <br>

    <?php
        function queryButton() {
            $count = 0;

            $query['title'] = $_POST['title'];
            $query['artist'] = $_POST['artist'];
            $query['year'] = $_POST['year'];

            foreach ($query as $element) {
                if (!empty($element)) {
                    $count++;
                }
            }

            // Get items
            $result = queryMusic($count, $query);
            // echo '<pre>From queryMusic():<br>';
            // print_r($result);
            // echo '</pre>'; 

            if (gettype($result) == 'string') {
                echo $result;
            } 

            if (count($result) < 1) {
                echo "No result retrieved. Please query again.";
            } else {
                printResults($result);
            }  
        }

        function scanButton() {
            $result = scanMusic();
            printResults($result);
        }

        function subButton() {
            $title = $_POST['title'];
            $artist = $_POST['artist'];
            $year = $_POST['year'];
            $index = $_POST['year'];

            $song = [
                'title' => $title,
                'artist' => $artist,
                'year' => $year
            ];

            // get user song info -> returns TUPLE[bool, array]
            //      if returns true -> "item already exists"
            //      if returns false -> updateUserinfo();
            // update dynamodb table
            $subbed = checkSub($title, $artist);
            
            if ($subbed) {
                echo "$title by $artist has already been subscribed to.";
            } else {
                updateUserSongs($song, $index, "update");
                echo "$title by $artist has been added to your subscriptions!";
            }
        }

        function itemModule($item, $index) {
            $title = $item['title'];
            $artist = $item['artist'];
            $year = $item['year'];

            echo '<form method="post">';
            echo '<p>Title: ' . $title . '</p>';
            echo '<p>Artist: ' . $artist . '</p>';
            echo '<p>Year: ' . $year . '</p>';
            echo "<input name='title' value='{$title}' hidden>";
            echo "<input name='artist' value='{$artist}' hidden>";
            echo "<input name='year' value='{$year}' hidden>";
            echo "<input name='index' value='{$index}' hidden>";
            echo '<input type="submit" name="subscribe" value="Subscribe">';
            echo '</form>';
            echo '<br><br><br><br>';
        }
        function printResults($result) {
            global $marshaler;
            foreach ($result as $index => $item) {
                $item = $marshaler->unmarshalItem($item);
                $fileName = $item['title'] . "-" . $item['artist'];
                getImage($fileName);
                itemModule($item, $index);
            }
        }

    ?>

    <form method="post" action="">
        <h2>ATTRIBUTES</h2>
		<label for="title">Title:</label>
		<input type="text" name="title"><br><br>
        <label for="artist">Artist: </label>
		<input type="text" name="artist"><br><br>
        <label for="artist">Year: </label>
		<input type="text" name="year" pattern='^[1-2][0-9]{3}'><br><br>
		<input type="submit" name="query" value="QUERY">
        <input type="submit" name="scan" value="SCAN">
	</form>
    <br><br>

    <?php
        $count = 0;
        if (array_key_exists('query', $_POST)) {
            queryButton();
        } else if (array_key_exists('scan', $_POST)) {
            scanButton();
        } else if (array_key_exists('subscribe', $_POST)) {
            subButton();
        }    
    ?>
</body>
</html>
