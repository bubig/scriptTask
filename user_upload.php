<?php
$host = "";
$username = "";
$password = "";
$option = getopt("u:p:h:", ["help", "file:", "create_table", "dry_run"]);
$GLOBALS["NOINSERT"] = FALSE;
$debugMode = TRUE;
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
    $GLOBALS["NOINSERT"] = TRUE;
}
if (isset($option["u"]) && $option["u"] != "") $username = $option["u"];
if (isset($option["p"]) && $option["p"] != "") $password = $option["p"];
if (isset($option["h"]) && $option["h"] != "") $host = $option["h"];


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
        fclose($fileOpen);
        $tempHeader = array_shift($rows);
        foreach ($tempHeader as $text) {
            $header[] = trim($text);
        }
        if (!(array_search("name", $header) !== false
            && array_search("surname", $header) !== false
            && array_search("email", $header) !== false)) {
            echo "Problems with header";
            if (!$debugMode) exit;
        }
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
                    if (checkEmail($dataValue)) {
                        $finalData[$k][$dataKey] = strtolower($dataValue);

                    } else {
                        //stop all and return error msg
                        fwrite(STDOUT, "wrong format email \"" . $dataValue . "\" line " . $k + 2 . " \n");
                        if (!$debugMode) exit;
                        if ($debugMode) $finalData[$k][$dataKey] = strtolower($dataValue);
                    }
                }
            }

        }
        //var_dump($header);
        //var_dump($csv);
        //var_dump($finalData);
        $pdo = connectToDB($username, $password, $host);
        if ($pdo !== false) {
                $result = manageInsert($finalData, $pdo);

        }
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
    } else {
        return true;
    }
}

function connectToDB($username, $password, $host)
{
    try {
        $conn = new PDO("mysql:host=$host;dbname=catalyst_test", $username, $password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        return false;
    }
}

function manageInsert($data, $pdo)
{
    try {
        $pdo->beginTransaction();
        $sql = "INSERT INTO users (name, surname, email) VALUES (?,?,?)";
        foreach ($data as $value) {
            $sqlReady = $pdo->prepare($sql);
            if(!$GLOBALS["NOINSERT"]) {
                $sqlReady->execute([$value["name"], $value["surname"], $value["email"]]);
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollback();
        if ($e->errorInfo[1] == 1146) {
            echo "Table doesn't exist. Please refer to the documentation to create the table or use the option -create_table";
            return false;
        }
        if ($e->errorInfo[1] == 1062) {
            echo $e->errorInfo[2] . " insertion aborted";
            return false;
        } else {
            throw $e;
        }
    }
    return true;
}
?>
