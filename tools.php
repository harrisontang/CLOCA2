<?php
   session_start();
   require 'vendor/autoload.php';

   date_default_timezone_set('UTC');
   use Aws\DynamoDb\Exception\DynamoDbException;
   use Aws\DynamoDb\Marshaler;
   use Aws\S3\S3Client;

   $sdk = new Aws\Sdk([
      'region' => 'us-east-1',
      'version' => 'latest'
   ]);

   // $s3 = new S3Client([
   //    'version' => 'us-east-1',
   //    'region' => 'latest',
   // ]);
   $bucketName = 'a2images-s3908223';

   $dynamodb = $sdk->createDynamoDb();
   $marshaler = new Marshaler();
   $dynamoLogin = 'login';
   $dynamoMusic = 'music';

   // Login Table
   // Checks login credentials against entry in login dynamodb table
   function loginTable($password, $email) {
      echo '<script>console.log("inside loginTable")</script>';
      global $dynamodb;
      global $dynamoLogin;
      global $marshaler;

      // $tableName = 'login';
      // $email = 's39082230@student.rmit.edu.au'; // passed in
      // $password = '123456';                     // passed in
  
      $key = $marshaler->marshalJson('
          {
              "password": "' . $password . '",
              "email": "' . $email . '"
          }
      ');
   
      $params = [
         'TableName' => $dynamoLogin,
         'Key' => $key
      ];

      try {
         $result = $dynamodb->getItem($params);   
         if (isset($result['Item'])) {
            return $result['Item'];
         } else {
            return "Error: email or password incorrect";
         }
      } catch (Exception $e) {
         return "Unable to get item:" . $e->getMessage() . "\n";
      }
   }

   function updateUserSongs($song, $removalIndex, $function) {
      global $dynamodb;
      global $dynamoLogin;
      global $marshaler;

      $songIndex = $song['title'] . "-" . $song['artist'];

      $key = $marshaler->marshalJson('
         {
            "password": "' . $_SESSION['user']['password']['S'] . '",
            "email": "' . $_SESSION['user']['email']['S'] . '"
         }
      ');

      // $key = $marshaler->marshalJson('
      //    {
      //       "password": "11111",
      //       "email": "hello@hotmail.com"
      //    }
      // ');

      if ($function == 'update') {
         $updateExp = 'SET songs = list_append(songs, :song)';
         $eav = $marshaler->marshalJson('
            {
               ":song": ["'. $songIndex . '"]
            }
         ');

         $params = [
            'TableName' => $dynamoLogin,
            'Key' => $key,
            'UpdateExpression' => $updateExp,
            'ExpressionAttributeValues' => $eav,
            'ReturnValues' => 'UPDATED_NEW'
         ];

         try {
            $result = $dynamodb->updateItem($params);
            echo "Updated item. ";
         } catch (Exception $e) {
            echo "Unable to update item:\n";
            echo $e->getMessage() . "\n";
         } 
      }


      if ($function == 'remove') {
         $updateExp = 'REMOVE songs[' . $removalIndex . ']';
         // $eav = $marshaler->marshalItem('
         //    {
         //       ":song": {"'. $songIndex . '"}
         //    }
         // ');

         $params = [
            'TableName' => $dynamoLogin,
            'Key' => $key,
            'UpdateExpression' => $updateExp,
            'ReturnValues' => 'UPDATED_NEW'
         ];

         try {
            $result = $dynamodb->updateItem($params);
            echo "Updated item. ";
         } catch (Exception $e) {
            echo "Unable to update item:\n";
            echo $e->getMessage() . "\n";
            echo "error line: " . $e->getLine();
         }

      }   
   }

   function register($password, $username, $email) {
      echo '<script>console.log("inside register()")</script>';
      global $dynamodb;
      global $dynamoLogin;
      global $marshaler;
  
      $item = $marshaler->marshalJson('
          {
              "password": "' . $password . '",
              "email": "' . $email . '",
              "user_name": "' . $username . '",
              "songs": [ ]
          }
      ');
   
      echo '<script>console.log("inside register-marshal()")</script>'; 

      $params = [
         'TableName' => $dynamoLogin,
         'Item' => $item
      ];

      try {
         $result = $dynamodb->putItem($params);  
         echo '<script>console.log("inside register-try()")</script>'; 
         
         header('Location: index.php');
         
      } catch (Exception $e) {
         echo "Unable to add item:" . $e->getMessage() . "\n";
      }
   }

   function scanLoginEmailCheck($emailCheck) {
      global $dynamodb;
      global $dynamoLogin;
      global $marshaler;

      $exists = null;
      $emails = null;

      $params = [
         'TableName' => $dynamoLogin,
         'ProjectionExpression' => '#em',
         'ExpressionAttributeNames'=> ['#em' => 'email']
      ];

      try {
         while (true) {
            $result = $dynamodb->scan($params);

            foreach ($result['Items'] as $i) {
               $emails[] = $marshaler->unmarshalItem($i);        
            }

            if (isset($result['LastEvaluatedKey'])) {
               $params['ExclusiveStartKey'] = $result['LastEvaluatedKey'];
            } else {
               break;
            }
         }
         
      } catch (DynamoDbException $e) {
         echo "Unable to scan:\n";
         echo $e->getMessage() . "\n";
      }

      foreach ($emails as $email) {
         if ($email['email'] == $emailCheck) {
            $exists = true;
            break;
         } else {
            $exists = false;
         }
      }
      return $exists;
   }

   // TIP: append a string that forms the expression
   function queryMusic($count, $query) {
      global $dynamodb;
      global $dynamoMusic;
      global $marshaler;

      $result = '';
      $params = [];
      $ean = [
         '#title' => 'title',
         '#artist' => 'artist',
         '#year' => 'year'
      ];

      switch ($count) {
         case 0:
            $check = "No query parameters supplied. Please query again.";
            break;
         case 1:
            $params = [
               'TableName' => $dynamoMusic,
               'KeyConditionExpression' => '#title = :val',
               'ExpressionAttributeNames' => $ean,
               'ExpressionAttributeValues' => [
                   ':val' => [
                       'S' => $query['title']
                   ]
               ],
               'ProjectionExpression' => '#title, #artist, #year'
            ];
       
            if ($query['artist'] != NULL) {
               $params['IndexName'] = 'artist-year-index';
               $params['KeyConditionExpression'] = '#artist = :val';
               $params['ExpressionAttributeValues'] = [
                   ':val' => [
                       'S' => $query['artist']
                   ]
               ];
            }
       
            if ($query['year'] != NULL) {
               $params['IndexName'] = 'year-title-index';
               $params['KeyConditionExpression'] = '#year = :val';
               $params['ExpressionAttributeValues'] = [
                   ':val' => [
                       'N' => strval($query['year'])
                   ]
               ];
            }
            break;
         case 2:
            if ($query['title'] != NULL) {
               if ($query['artist'] != NULL) {
                  $eav = $marshaler->marshalJson('
                     {
                        ":title": "' . $query['title'] . '",
                        ":artist": "' . $query['artist'] . '"
                     }
                  ');

                  $kce = '#title = :title AND #artist = :artist';

                  $params = [
                     'TableName' => $dynamoMusic,
                     'KeyConditionExpression' => $kce,
                     'ExpressionAttributeNames' => $ean,
                     'ExpressionAttributeValues' => $eav,
                     'ProjectionExpression' => '#title, #artist, #year'
                  ];
               }

               if ($query['year'] != NULL) {
                  $eav = $marshaler->marshalJson('
                     {
                        ":title": "' . $query['title'] . '",
                        ":yyyy": ' . $query['year'] . '
                     }
                  ');

                  $kce = '#year = :yyyy AND #title = :title';

                  $params = [
                     'TableName' => $dynamoMusic,
                     'IndexName' => 'year-title-index',
                     'KeyConditionExpression' => $kce,
                     'ExpressionAttributeNames' => $ean,
                     'ExpressionAttributeValues' => $eav,
                     'ProjectionExpression' => '#title, #artist, #year'
                  ];
               }

            } else {
               $eav = $marshaler->marshalJson('
                     {
                        ":artist": "' . $query['artist'] . '",
                        ":yyyy": ' . $query['year'] . '
                     }
                  ');

               $kce = '#artist = :artist AND #year = :yyyy';

               $params = [
                  'TableName' => $dynamoMusic,
                  'IndexName' => 'artist-year-index',
                  'KeyConditionExpression' => $kce,
                  'ExpressionAttributeNames' => $ean,
                  'ExpressionAttributeValues' => $eav,
                  'ProjectionExpression' => '#title, #artist, #year'
               ];
            }        

            break;
         case 3:
            $eav = $marshaler->marshalJson('
               {
                  ":title": "' . $query['title'] . '",
                  ":artist": "' . $query['artist'] . '"
               }
            ');

            $kce = '#title = :title AND #artist = :artist';
            $params = [
               'TableName' => $dynamoMusic,
               'KeyConditionExpression' => $kce,
               'ExpressionAttributeNames' => $ean,
               'ExpressionAttributeValues' => $eav,
               'ProjectionExpression' => '#title, #artist, #year'
            ];
            break;
      }
         try {
            $result = $dynamodb->query($params);
            return $result['Items'];     
         } catch (Exception $e) {
            // echo $e->getMessage();
            // echo "<br>";
            // echo $e->getLine();
         }
   }

   function scanMusic() {
      global $dynamodb;
      global $dynamoMusic;
      global $marshaler;
      $ean = [
         '#title' => 'title',
         '#artist' => 'artist',
         '#year' => 'year'
      ];

      $params = [
         'TableName' => $dynamoMusic,
         'ProjectionExpression' => '#title, #artist, #year',
         'ExpressionAttributeNames' => $ean
      ];
      try {
         $result = $dynamodb->scan($params);
         return $result['Items'];     
      } catch (Exception $e) {
         // echo $e->getMessage();
         // echo "<br>";
         // echo $e->getLine();
      }

   }
  
   function checkSub($title, $artist) {
      $confirm = null;
      global $marshaler;
      global $dynamoLogin;
      global $dynamodb;
      $userMusicKey = $title . "-" . $artist;

      // change log in
      $key = $marshaler->marshalJson('
         {
            "password": "' . $_SESSION['user']['password']['S'] . '",
            "email": "' . $_SESSION['user']['email']['S'] . '"
         }
      ');

      $params = [
         'TableName' => $dynamoLogin,
         'Key' => $key
      ];

      try {
         $result = $dynamodb->getItem($params);   
         if (isset($result['Item'])) {
            if (!isset($result['Item']['songs'])) {
               return false;
            } 

            $listOfSongs = $result['Item']['songs']['L'];
            for ($i=0;$i<count($listOfSongs);$i++) {

               if ($listOfSongs[$i]['S'] == $userMusicKey) {
                  return true;
               }
            }
            return false;
         }
      } catch (Exception $e) {
         
      }
   }

   function getSubbed() {
      $confirm = null;
      global $marshaler;
      global $dynamoLogin;
      global $dynamodb;

      $filename = null;


      $key = $marshaler->marshalJson('
         {
            "password": "' . $_SESSION['user']['password']['S'] . '",
            "email": "' . $_SESSION['user']['email']['S'] . '"
         }
      ');

      $params = [
         'TableName' => $dynamoLogin,
         'Key' => $key
      ];

      try {
         $result = $dynamodb->getItem($params);   
         if (isset($result['Item'])) {
            return $result['Item'];
         }
      } catch (Exception $e) {
         return "Unable to get item:" . $e->getMessage() . "\n";
      }
   }

   function getSong($title, $artist) {
      global $dynamodb;
      global $dynamoMusic;
      global $marshaler;

      // $tableName = 'login';
      // $email = 's39082230@student.rmit.edu.au'; // passed in
      // $password = '123456';                     // passed in
  
      $key = $marshaler->marshalJson('
          {
              "title": "' . $title . '",
              "artist": "' . $artist . '"
          }
      ');
   
      $params = [
         'TableName' => $dynamoMusic,
         'Key' => $key
      ];

      try {
         $result = $dynamodb->getItem($params);   
         if (isset($result['Item'])) {
            return $result['Item'];
         } else {
            return "Error: song not found.";
         }
      } catch (Exception $e) {
         return "Unable to get item:" . $e->getMessage() . "\n";
      }

   }

   function getImage($fileName) {
      global $bucketName;

      $s3 = new S3Client([
         'profile' => 'default',
         'region' => 'us-east-1',
         'version' => 'latest'
      ]);

      $keyName = $fileName. '.jpg';

      try {
         $result = $s3->getCommand('GetObject', [
            'Bucket' => $bucketName,
            'Key' => $keyName
         ]);
         
         $request = $s3->createPresignedRequest($result, '+20 minutes');
         $signedURL = (string) $request->getUri();
         echo '<img width=150px src="' . $signedURL . '">';
      } catch (S3Exception $e) {
         echo $e->getMessage() . PHP_EOL;
      }

   }
?>



