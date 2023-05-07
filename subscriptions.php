<?php
include ('tools.php');
session_start();

//debug tool:
//echo <pre>;
//print_r();
//echo </pre>; 
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Music Database Subscriptions</title>
    </head>
    <body>
        <h1>MUSIC DATABASE SUBSCRIPTIONS</h1>
        <h3>Query the music database</h3>
        <br>
        <p>Find your subscribed music here.<p>
        
        <p>Press the remove button to remove the song from your subscriptions.<p>
        <a href='main.php'>Go back to main</a><br>
        <br>

        <?php
            $item = getSubbed();
            $item = $marshaler->unmarshalItem($item);
           
            if (!isset($item['songs'][0])) {
                echo "No subscriptions found!";
            } else {
                $songList = $item['songs'];
                for ($i=0;$i<count($songList);$i++) {
                    $filename = $songList[$i];
                    getImage($filename);
                    
                    $filename = explode('-',$filename);
                    $title = $filename[0];
                    $artist = $filename[1];
                    $song = $marshaler->unmarshalItem(getSong($title, $artist));
                    itemModule($song, $i);             
                }
            }

            if (array_key_exists('remove', $_POST)) {
                removeButton();
            }

            function removeButton() {
                $title = $_POST['title'];
                $artist = $_POST['artist'];
                $year = $_POST['year'];
                $index = $_POST['index'];
                unset($_POST);
    
                $song = [
                    'title' => $title,
                    'artist' => $artist,
                    'year' => $year
                ];

                // check index
                echo "title: " . $index . "<br>";
                echo "artist: " . $index . "<br>";
                echo "year: " . $index . "<br>";
                echo "index: " . $index . "<br>";

                updateUserSongs($song, $index, 'remove');
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
                echo '<input type="submit" name="remove" value="Remove">';
                echo '</form>';
                echo '<br><br><br><br>';
            }
        ?>
    </body>
</html>