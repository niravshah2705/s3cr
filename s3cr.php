<?php

# Common Variable Declaration
# -----------------------------------------------------------------------------------------
        define('__ROOT__', dirname(__FILE__));
        include(__ROOT__.'/config/config.php');
        require (__ROOT__.'/config/aws/aws-autoloader.php');
        use Aws\S3\S3Client;
        use Aws\Common\Aws;

        $aws = Aws::factory(__ROOT__.'/config/s3config.php');
        $s3Client = $aws->get('s3');

function Listfiles($strkeyPrefix)
{
# List files
# -----------------------------------------------------------------------------------------
		global $s3Client;
        $objects = $s3Client->getIterator('ListObjects', array(
        'Bucket' => s3_bucket,
        'Prefix' => $strkeyPrefix.'/'
    ));
		$strFileListHtml ='';
        foreach ($objects as $object){
                $file= substr($object['Key'],  strpos($object['Key'], '/')+1);
                $key= substr($object['Key'],0, strpos($object['Key'], '/'));
                if (strlen($file)>0){
                        $strFileListHtml=$strFileListHtml.'<a href="'.basename(__FILE__).'?action=DownloadFile&customer='.$key.'&file_name='.$file.'">'.$file."</a><br>";
                }
		}
		return $strFileListHtml;
}
function ListCustomer()
{
# List customer
# -----------------------------------------------------------------------------------------
		global $s3Client;
         $objects = $s3Client->getIterator('ListObjects', array(
        'Bucket' => s3_bucket,
        'Delimiter' => '/'
        ),
    array(
        'return_prefixes' => true
    ));
		$strCustomerHtml ='';
        foreach ($objects as $object){
                        $strCustomerHtml=$strCustomerHtml.$object['Prefix'];
        }
		return $strCustomerHtml;
}
function addcustomer($strkeyPrefix)
{
# Add customer
# -----------------------------------------------------------------------------------------
         global $s3Client;
         $objects = $s3Client->putObject(array(
        'Bucket' => s3_bucket,
        'Key' => $strkeyPrefix.'/',
	'Body' => '',
	'ACL' => 'public-read'
        )
    );
  	return "Cusotmer ".$strkeyPrefix." added.";
}
function UploadFile($strkeyPrefix)
{
# Upload File
# -----------------------------------------------------------------------------------------
	global $s3Client;
	$target_dir = UploadDir;
	$target_file = $target_dir. basename($_FILES["fileToUpload"]["name"]);
	$uploadOk = 1;

	if (file_exists($target_file)) {
		$uploadOk = 0;
	}
	if ($uploadOk == 0) {
		echo "Sorry, your file was not uploaded.";
	} else {
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
			$s3_file=$_FILES["fileToUpload"]["name"];
			$s3Client->upload( s3_bucket, $strkeyPrefix.'/'.basename($_FILES["fileToUpload"]["name"]),fopen($target_dir.$_FILES["fileToUpload"]["name"],'rb'),'private', array(
					'concurrency' => 20,
					'debug'       => true
			));
			return "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
		} else {
			return "Sorry, there was an error uploading your file.";
		}
	}
}

function DownloadFile($strkeyPrefix)
{
# Download File
# -----------------------------------------------------------------------------------------
	global $s3Client;
	$s3Client->getObject( array ('Bucket' => s3_bucket,
                                        'Key' => $strkeyPrefix.'/'.$_GET['file_name'],
                                        'SaveAs' => DownloadDir.$_GET['file_name']
                                ));
	
	ignore_user_abort(true);
	set_time_limit(0); // disable the time limit for this script

	//$dl_file = preg_replace("([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})", '', $_GET['file_name']); // simple file name validation
	//$dl_file = filter_var($dl_file, FILTER_SANITIZE_URL); // Remove (more) invalid characters
	$fullPath = DownloadDir.$_GET['file_name'];

	if ($fd = fopen ($fullPath, "r")) {
		$fsize = filesize($fullPath);
		$path_parts = pathinfo($fullPath);
		$ext = strtolower($path_parts["extension"]);
		switch ($ext) {
			case "pdf":
				header("Content-type: application/pdf");
				header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a file download
			break;
			
			default;
				header("Content-type: application/octet-stream");
				header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
			break;
		}
		header("Content-length: $fsize");
		header("Cache-control: private"); //use this to open files directly
		while(!feof($fd)) {
			$buffer = fread($fd, 2048);
			echo $buffer;
		}
	}
	fclose ($fd);
}

		switch ($_GET['action']) {
			case "Listfiles": echo Listfiles($_GET['customer']);break;
			case "ListCustomer": echo ListCustomer();break;
			case "addcustomer": echo addcustomer($_GET['customer']);break;
			case "UploadFile": UploadFile($_GET['customer']);break;
			case "DownloadFile": DownloadFile($_GET['customer']);break;
			default:	
				echo "Invalid Action";
		}

