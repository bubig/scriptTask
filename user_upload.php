<?php
include 'dbconfig.php';
$host = $dbConfig["host"] ?? '';
$username = $dbConfig["username"] ?? '';
$password = $dbConfig["password"] ?? '';
$db = $dbConfig["database"] ?? 'catalyst_test';
$option = getopt("u:p:h:", ["help", "file:", "create_table", "dry_run"]);
$GLOBALS["NOINSERT"] = FALSE;
$createTable = FALSE;

// Handling options
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
    if(!isset($option["file"])){
        echo "A file option is require to use this option. Please, use the --help option for more information \n";
        exit;
    }
}
if (isset($option["u"]) && $option["u"] != "") $username = $option["u"];
if (isset($option["p"]) && $option["p"] != "") $password = $option["p"];
if (isset($option["h"]) && $option["h"] != "") $host = $option["h"];

if (isset($option["create_table"])) {
    // create table users
    $createTable = TRUE;
}

if ($createTable) {
    $pdo = connectToDB($db, $username, $password, $host);
    if($pdo != FALSE) {
        // create table if connection succesfull
        createTable($pdo);
        // no further action to be taken
    }
    exit;
}

// getting csv file
$fileName = "";
if (isset($option["file"])) {
    $fileName = $option["file"];
    if (!file_exists($fileName)) {
        // file not found
        //throw new Exception('File not found.');
        echo "File not found \n";
        exit;
    }
    $fileOpen = fopen($fileName, "r");
    if (!$fileOpen) {
        // couldn't open file
        //throw new Exception('Could not open file');
        echo "Couldn't open file \n";
        exit;
    } else {
        // file opened
        $rows = array_map('str_getcsv', file($fileName));
        fclose($fileOpen);
        $tempHeader = array_shift($rows);
        // clean header
        foreach ($tempHeader as $text) {
            $header[] = trim($text);
        }
        // verify that the header correspond
        if (!(array_search("name", $header) !== false
            && array_search("surname", $header) !== false
            && array_search("email", $header) !== false)) {
            echo "Problems with header\n";
            if (!$debugMode) exit;
        }
        $csv = array();
        foreach ($rows as $row) {
            $csv[] = array_combine($header, $row);
        }
        $pdo = connectToDB($db, $username, $password, $host);
        if($pdo == false){
            exit;
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
        if ($pdo !== false) {
            $result = manageInsert($finalData, $pdo);
        }
    }
    echo "End.\n";
}


/**
 * get a string to clean
 * @param $string
 * @return string
 */
function cleanDataName($string = "test")
{
    $string = trim($string);
    $string = stripslashes($string);
    $string = htmlspecialchars($string);
    $cleanData = ucfirst(strtolower($string));
    return $cleanData;
}

/**
 * Verify if the email is in valid format
 * @param $email
 * @return bool
 */
function checkEmail($email = "email")
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format\n";
        return false;
    } else {
        return true;
    }
}

/**
 * Try the connection to the DB
 * @param $db
 * @param $username
 * @param $password
 * @param $host
 * @return false|PDO
 */
function connectToDB($db, $username, $password, $host)
{
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connection to the database successful.\n";
        return $conn;
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 2002) {
            echo "Host couldn't match.\n";
            exit;
        }
        if ($e->errorInfo[1] == 1049) {
            echo "Database doesn't exist.\n";
            exit;
        }
        if ($e->errorInfo[1] == 1045) {
            echo "No access for this user.\n";
            exit;
        } else {
            echo "Connection failed: " . $e->getMessage() . "\n";
        }
        return false;
    }
}

/**
 * Insert the data to the table users
 * @param $data
 * @param $pdo
 * @return bool
 * @throws Exception
 */
function manageInsert($data, $pdo)
{
    try {
        $pdo->beginTransaction();
        $sql = "INSERT INTO users (name, surname, email) VALUES (?,?,?)";
        foreach ($data as $value) {
            $sqlReady = $pdo->prepare($sql);
            if (!$GLOBALS["NOINSERT"]) {
                $sqlReady->execute([$value["name"], $value["surname"], $value["email"]]);
            }
        }
        $pdo->commit();
        echo ($GLOBALS["NOINSERT"]?"Data successfully treated without insertion." : "Data successfully inserted.") . "\n";
    } catch (Exception $e) {
        $pdo->rollback();
        if ($e->errorInfo[1] == 1146) {
            echo "Table doesn't exist.\nPlease refer to the documentation to create the table or use the option -create_table\n";
            return false;
        }
        if ($e->errorInfo[1] == 1062) {
            echo $e->errorInfo[2] . " insertion aborted\n";
            return false;
        } else {
            throw $e;
        }
    }
    return true;
}

/**
 * Create the table users
 * @param $pdo
 * @return bool
 * @throws Exception
 */
function createTable($pdo)
{
    try {
        $sql = "DROP TABLE IF EXISTS users; 
            CREATE TABLE IF NOT EXISTS users (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            name varchar(50) NOT NULL,
            surname varchar(50) NOT NULL,
            email varchar(255) NOT NULL,
            UNIQUE KEY email_key (email)
            );";
        $sqlReady = $pdo->prepare($sql);
        $sqlReady->execute();
        echo "Table successfully created!\n";
    } catch (Exception $e) {
        if ($e->errorInfo[1] == 1046) {
            echo "Na database selected.\n";
            exit;
        }
        throw $e;

    }
    return true;
}

?>
