# ScriptTask for Catalyst
*Author: Guillaume BARAT 13/06/2023*


- Activate PDO in php.ini “extension=pdo_mysql”
- create_table will drop the table if it exists already without back up
- in the dbconfig.php file, replace **[DB_NAME]** with the name of the database

+ To define the host / username / password to connect to the Database, there are two possibilities:


| Use the dbconfig.php file and replace |
|---------------------------------------|
| **[DB_HOST]**                         |
| **[DB_USERNAME]**                     |
| **[DB_PASSWORD]**                     | 

<b> OR</b>

| Use the script options: |
|------------------------|
| -u “username"          |
| -p “password”          |
| -h “hostname”          |

<br/>

| List of options available:                                                                                                                                                                   |
|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| --file [csv file name] – this is the name of the CSV to be parsed                                                                                                                            |
| --create_table – this will cause the MySQL users table to be built (and no further action will be taken)                                                                                     |
| --dry_run – this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered |
| -u – MySQL username                                                                                                                                                                          |
| -p – MySQL password                                                                                                                                                                          |
| -h – MySQL host                                                                                                                                                                              |
| --help – which will output the above list of directives with details.                                                                                                                        |
