<?php
/* Config */
// Here you enter the database connection.
$db_name                	= ""; 			// Database name
$db_user                	= "";				// Database User
$db_password            	= "";				// Database password
$db_host                	= "";				// Host

$table_prefix					= "wp_";			// tabele prefix

mysql_connect($db_host,$db_user,$db_password) or die
("NOT ABLE TO CONNECT TO DATABASE");
mysql_select_db($db_name) or die
("THE DATABASE DOES NOT EXIST!");

$table_info				= $table_prefix.'instagram_info';
$table_bilder				= $table_prefix.'instagram_bilder';
$table_user_info		= $table_prefix.'instagram_user_info';

$ergebnis = mysql_query("SELECT text FROM $table_info WHERE id='1'");
$row = mysql_fetch_object($ergebnis);
$instagram_id = $row->text;

$ergebnis = mysql_query("SELECT text FROM $table_info WHERE id='2'");
$row = mysql_fetch_object($ergebnis);
$instagram_access = $row->text;

	########################################################################################################################
	/*
	* Get user data
	*/
	
		// URL where the data is
		$url='https://api.instagram.com/v1/users/'.$instagram_id.'/?access_token='.$instagram_access;
		
		// Get cURL resource
		$curl = curl_init();
		// Options
		curl_setopt_array($curl, array(
  		CURLOPT_RETURNTRANSFER => 1,
  		CURLOPT_URL => $url,
  		CURLOPT_TIMEOUT => 5,
		CURLOPT_SSL_VERIFYPEER => false,
 		));


		if(curl_exec($curl) === false)
		{
    		echo "cUrl-Error \n " . curl_error($curl);
		}

		$resp = curl_exec($curl);
		curl_close($curl);

		// If data are available
		if($resp)
		{
			// Readable data with json decode
			$data=json_decode($resp, true);
		
			// Save code output
			$code = $data["meta"]["code"];	
			
			// With code is 200, than string is ok
			if($code == "200")
			{
				// variable storage
				$username				= $data["data"]["username"];
				$profil_picture 	= $data["data"]["profile_picture"];
				$full_name 			= $data["data"]["full_name"];
				$media					= $data["data"]["counts"]["media"];
				$follwed				= $data["data"]["counts"]["followed_by"];
				$follows				= $data["data"]["counts"]["follows"];

				$update = mysql_query("UPDATE $table_user_info Set 
							username = '$username', 
							full_name = '$full_name', 
							media = '$media', 
							followed = '$follwed', 
							follows = '$follows', 
							profil_picture = '$profil_picture' 
							WHERE id = '1'");
			}
		}
		
	########################################################################################################################
				
	########################################################################################################################
	/*
	* Get user picture
	*/
	
		// connection
		$url='https://api.instagram.com/v1/users/'.$instagram_id.'/media/recent?access_token='.$instagram_access;

		// Get cURL resource
		$curl = curl_init();
		// Options
		curl_setopt_array($curl, array(
  		CURLOPT_RETURNTRANSFER => 1,
  		CURLOPT_URL => $url,
  		CURLOPT_TIMEOUT => 5,
		CURLOPT_SSL_VERIFYPEER => false,
 		));

		$resp = curl_exec($curl);
		curl_close($curl);


		// If data are available
		if($resp){
			// Json with decoden now, so we can read it in the array
			$data=json_decode($resp, true);
			
			// Since Instagram rausrueckt only 20 pictures per query, but we want more, we save the first next_url so that we can continue to read later.
			$url = $data["pagination"]["next_url"];
	
			// Storage error in the variable
			$error = $data["meta"]["code"];	
			
			// When Error is 200, then everything is ok!
			if($error == "200")
			{
				// Now we can count the array so that we can read it more easily
				$size = sizeof($data["data"]);
				
				// The for loop goes until the end is reached while
				for ($i = 0; $i < $size; $i++)
            			{
            				// Save image information in variables
					$bild_low			= $data["data"]["$i"]['images']['low_resolution']['url'];
					$bild_thumb			= $data["data"]["$i"]['images']['thumbnail']['url'];
					$bild_standard			= $data["data"]["$i"]['images']['standard_resolution']['url'];
					$bild_like			= $data["data"]["$i"]["likes"]["count"];
					$bild_comments			= $data["data"]["$i"]["comments"]["count"];
					$bild_link			= $data["data"]["$i"]['link'];
					$bild_id			= $data["data"]["$i"]["id"];
					$bild_text			= $data["data"]["$i"]["caption"]["text"];
					
					$bild_text = mysql_real_escape_string($bild_text);
				
					// The image ID contains the user ID. This but we do not have. So we delete the (Plus the "_" in front of the user ID)
					$id_ersetzung = "_$instagram_id";				
					$id = str_replace($id_ersetzung, "", $bild_id);
				
					// Query whether image is already available
					$result = mysql_query("SELECT COUNT(*) FROM $table_bilder WHERE id = $id") or die (mysql_error()); 
					$count = mysql_result($result,0);
					
					if($count == "0")
					{
						$eintragen = mysql_query("INSERT INTO $table_bilder (id, link, text, thumbnail, low_resolution, standard_resolution, pic_like, pic_comment) 
						VALUES ('$id', '$bild_link', '$bild_text', '$bild_thumb', '$bild_low', '$bild_standard', '$bild_like', '$bild_comments')");
					}
					else {
						$update = mysql_query("UPDATE $table_bilder Set pic_like = '$bild_like', pic_comment='$bild_comments' WHERE id = '$id'");
					}
				
				}

				// Now follows the while loop. This is done until no next_url Instagram delivers us and we have so read ALL images
				while($url != "") 
				{
					// Again, the curl connection
					$curl = curl_init();
					// Options
					curl_setopt_array($curl, array(
  					CURLOPT_RETURNTRANSFER => 1,
  					CURLOPT_URL => $url,
  					CURLOPT_TIMEOUT => 5,
					CURLOPT_SSL_VERIFYPEER => false,
 					));

					$resp = curl_exec($curl);
					curl_close($curl);

					// Code description see Raised
					if($resp)
					{
						$data=json_decode($resp, true);
					
						$size = sizeof($data["data"]);
			
						$url = $data["pagination"]["next_url"];
					
						for ($i = 0; $i < $size; $i++)
            					{
							$bild_low			= $data["data"]["$i"]['images']['low_resolution']['url'];
							$bild_thumb			= $data["data"]["$i"]['images']['thumbnail']['url'];
							$bild_standard			= $data["data"]["$i"]['images']['standard_resolution']['url'];
							$bild_like			= $data["data"]["$i"]["likes"]["count"];
							$bild_comments			= $data["data"]["$i"]["comments"]["count"];
							$bild_link			= $data["data"]["$i"]['link'];
							$bild_id			= $data["data"]["$i"]["id"];
							$bild_text			= $data["data"]["$i"]["caption"]["text"];
							
							$bild_text = mysql_real_escape_string($bild_text);
				
							$id_ersetzung = "_$instagram_id";				
							$id = str_replace($id_ersetzung, "", $bild_id);
				
							// Query whether image is already available
							$result = mysql_query("SELECT COUNT(*) FROM $table_bilder WHERE id = $id") or die (mysql_error()); 
							$count = mysql_result($result,0);
					
							if($count == "0")
							{
								$eintragen = mysql_query("INSERT INTO $table_bilder (id, link, text, thumbnail, low_resolution, standard_resolution, pic_like, pic_comment) 
								VALUES ('$id', '$bild_link', '$bild_text', '$bild_thumb', '$bild_low', '$bild_standard', '$bild_like', '$bild_comments')");
							}
							else {
								$update = mysql_query("UPDATE $table_bilder Set pic_like = '$bild_like', pic_comment='$bild_comments' WHERE id = '$id'");
							}
				
						}
					}
				}
				 
    			echo 'Pictures were updated.';
    			
    		}
    		// Instagram ID and access token does not match
    		else { echo 'Problem of Authentication.'; }

		}

?>
