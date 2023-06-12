<?php
$option = getopt("u::p::h::", ["help", "file:", "create_table", "dry_run"]);
$noInsert = FALSE;
if (isset($option["help"])) {
    echo "
    --file [csv file name] – Name of the csv file to be parsed \n
    --create_table – Create an empty table \"users\" if it does not exist \n
    --dry_run – Use this otpion with the --file directive to run the script without inserting the data to the Database. \n
    -u – MySQL username \n
    -p – MySQL password \n
    -h – MySQL host\n
    --help – Display the list of options.";
    exit;
}
if (isset($option["dry_run"])) {
    // run without insertion
    $noInsert = TRUE;
}

$fileName = "";
if (isset($option["file"])) {
    $fileName = $option["file"];
    if (!file_exists($fileName)) {
        throw new Exception('File not found.');
    }
    $fileOpen = fopen($fileName, "r");
    if (!$fileOpen) {
        throw new Exception('Could not open file');
    } else {
        $rows = array_map('str_getcsv', file($fileName));
        $header = array_shift($rows);
        $csv = array();
        foreach ($rows as $row) {
            $csv[] = array_combine($header, $row);
        }
        $finalData = array();
        foreach ($csv as $k => $arrayRow) {
            foreach ($arrayRow as $key => $value) {
                $dataValue = trim($value);
                $dataKey = trim($key);

                if (in_array($dataKey, ["name", "surname"])) {
                    $finalData[$k][$dataKey] = cleanDataName($dataValue);
                } elseif ($dataKey == "email") {
                    if(checkEmail($dataValue)){
                        $finalData[$k][$dataKey] = $dataValue;

                    }else{
                        //stop all and return error msg
                        echo "wrong format email " . $dataValue;
                    }
                }
            }

        }
        //var_dump($header);
        //var_dump($csv);
        //var_dump($finalData);
        fclose($fileOpen);

    }
}

function cleanDataName($string = "test")
{
    $string = trim($string);
    $string = stripslashes($string);
    $string = htmlspecialchars($string);
    $cleanData = ucfirst(strtolower($string));
    return $cleanData;
}

function checkEmail($email = "email")
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format";
        return false;
        var_dump($emailErr);
        var_dump($email);
    } else {
        return true;
    }
}

?>
