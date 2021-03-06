<?php
	/**
	 * filename: proces_2.php
	 * Copyright (C) 2014 Agung Andika
	 *
	 * Process input jpeg which taken by camera mobile phones (currently Android JB 4.1.x and up) 
	 * 
	 * version 0.1 - 28 December 2014
	 * - 1st release
	 * 
	 * includes example from:
	 * - IPTC.php | Copyright (C) 2004, 2005  Martin Geisler. | Code licensed under GPL v2
	 */

    include 'Log/Log.php';
    include 'qqwry-php/qqwry.php';
    include 'config.php';

    function getIP() /*获取客户端IP*/  
    {  
		if (@$_SERVER["HTTP_X_FORWARDED_FOR"])  
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];  
        else if (@$_SERVER["HTTP_CLIENT_IP"])  
    	   	$ip = $_SERVER["HTTP_CLIENT_IP"];  
        else if (@$_SERVER["REMOTE_ADDR"])  
    		$ip = $_SERVER["REMOTE_ADDR"];  
        else if (@getenv("HTTP_X_FORWARDED_FOR"))  
    		$ip = getenv("HTTP_X_FORWARDED_FOR");  
        else if (@getenv("HTTP_CLIENT_IP"))  
    		$ip = getenv("HTTP_CLIENT_IP");  
        else if (@getenv("REMOTE_ADDR"))  
    		$ip = getenv("REMOTE_ADDR");  
        else  
    		$ip = "Unknown";  
    	
    	return $ip;  
	}

	function getLocation($ip)
	{
		//echo QQWRY;
		$lo = new qqwry(QQWRY);
		return $lo->query($ip);
	}

	$target_dir = "uploads/";
	$sex_dir = "uploads_sex/";
	$md5_name = md5_file( $_FILES["takePictureFieldBefore"]["tmp_name"] );
	$file_name_before = basename($_FILES["takePictureFieldBefore"]["name"]);
	$imageType = strtolower( pathinfo($_FILES["takePictureFieldBefore"]["name"], PATHINFO_EXTENSION));

	$target_file_before = $target_dir . $md5_name.".".$imageType ;
	$target_file_sex = $sex_dir . $md5_name.".".$imageType ;

	$uploadOk = 1;

	//
	function sexRecognition($file)
	{
	    $nsfw = NSFW;//nsfw path
	    $tag_prob = "probability:";
		$tag_time = "time:";

		error_reporting(E_ALL);
		$param = $nsfw . " ".$file;
		$handle = popen($param, 'r');

		$ret = 0.01;

		while(!feof($handle)) 
		{
			$buffer = fgets($handle);
			echo $buffer;
			$sub = strstr($buffer, "0");
			if(!$sub)
				continue;
			$sub1 = substr($sub,0, strpos($sub, " 计"));
			if(!$sub1)
				continue;
			$ret = $sub1;
		}
		pclose($handle);

		return $ret;
	}

	//
	// Check if image file is a actual image or fake image
	if(isset($_POST)) 
	{
		$conf = array('mode' => 0600, 'timeFormat' => '%X %x');
		$logger = Log::factory('file', OUT_LOG, 'SEX CHECK');
		$ip = getIP();
		$lo = getLocation($ip);
		$loutf8 = iconv("UTF-8", "GB2312//IGNORE", $lo[0].$lo[1]);
		$logger->log( $ip.'|'.$loutf8);

		// check existance upload target directory
		if( !is_dir($target_dir) ) 
			@mkdir($target_dir);
		if( !is_dir($sex_dir) ) 
			@mkdir($sex_dir);

	    $check_before = getimagesize($_FILES["takePictureFieldBefore"]["tmp_name"]);
	    if($check_before !== false) 
	    { 
	        $uploadOk = 1;
			if (file_exists($target_file_before)) 
			{
			    unlink($target_file_before);
			}
		} 
		else 
		{
	        echo "File Before is not an image.";
	        $uploadOk = 0;
	    }

	    // Check if $uploadOk is set to 0 by an error
		if ($uploadOk === 0) 
		{
		    echo "Sorry, your file was not uploaded.";
		} 
		else 
		{
			$do_upload_before = move_uploaded_file($_FILES["takePictureFieldBefore"]["tmp_name"], $target_file_before);

		    if ($do_upload_before )
		    {
		        echo "The file ". basename( $file_name_before ) . " has been uploaded. <br/>";

				echo '<pre>';  
				$ret = sexRecognition($target_file_before);
				$cmp = round($ret, 3) - 0.400;
				if( $cmp > 0)
					echo $cmp;
				if($cmp >= 0 )
				{
					echo "色情图片不展示哦^_^";
					rename($target_file_before, $target_file_sex);
				}
				echo '</pre>';  
		        echo " <form method=\"get\" action=\"list.php\">
		         	<input type=\"submit\" value=\"美女图片中心\">
		         </form>";
				
				echo "<img src='" . $target_file_before . "' /><br/>";
				
				$logger->log( $ip.'|'.$target_file_before."|".$ret);
			} 
			else 
			{
		        echo "Sorry, there was an error uploading your file.";
			}

		}
	}
