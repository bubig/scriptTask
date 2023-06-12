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
if(isset($option["file"])){
    $fileName = $option["file"];
    if ( !file_exists($fileName) ) {
        throw new Exception('File not found.');
    }
    $fileOpen = fopen($fileName, "r");
    if ( !$fileOpen ) {
        throw new Exception('Could not open file');
    }else{
        $rows = array_map('str_getcsv', file($fileName));
        $header = array_shift($rows);
        $csv = array();
        foreach ($rows as $row) {
            $csv[] = array_combine($header, $row);
        }
        var_dump($header);
        var_dump($csv);
        var_dump("");
        fclose($fileOpen);
    }
}

?>
