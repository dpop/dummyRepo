<?php
use RedBeanPHP\R;

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
$versionsList = CallAPI("GET","http://localhost/AutoUpdateServer/getVersion.php",array("clientVersion" => $lastRow->getID()));


foreach ($versionsList as $version)
{
    echo $version["downloadUrl"]. " <br />";
}


// start patching the current code




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