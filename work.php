<?php
use RedBeanPHP\R;

$serverBase = "http://localhost/AutoUpdateServer";
R::setup('mysql:host=localhost;dbname=testdbClient','root','');

try
{
    $lastRow =  R::getRow("select * from versions order by `id` desc limit 1");
    $lastHash = trim ($lastRow['hash']);
}
catch (Exception $ex)
{
    $lastRow = R::dispense("versions");
    $lastRow->setProperty("id", 0);
}

// make request to server
$versionsResponse = CallAPI("GET","http://localhost/AutoUpdateServer/getVersion.php",array("clientVersion" => $lastRow->getID()));

$versionsList = unserialize($versionsResponse);

if (is_array($versionsList) || is_object($versionsList))
{
    foreach ($versionsList as $version)
    {
        try
        {
            $zip = new ZipArchive;
            if(get_http_response_code($serverBase.$version["downloadUrl"]) != "200"){
                echo "Could not get version ". $serverBase.$version["downloadUrl"];
            }else{
                file_put_contents('./downloads/tmp.zip',file_get_contents($serverBase.$version["downloadUrl"]));
                $zip = new ZipArchive();
                $res =$zip->open("./downloads/tmp.zip");
                if ($res === TRUE) {
                    $id = $version["id"];
                    $versionTempPath = './downloads/temp/'.$id."/";
                    mkdir($versionTempPath);
                    $zip->extractTo($versionTempPath);
                    $zip->close();
                    recurse_copy($versionTempPath, ".");
                } else {
                    echo 'doh!';
                }
            }


        }catch (Exception $ex)
        {
            echo "Could not get version ". $serverBase.$version["downloadUrl"];
        }
    }
}
else
{
    echo "nope";
}


function recurse_copy($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// start patching the current code


function get_http_response_code($url) {
    $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}

function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}