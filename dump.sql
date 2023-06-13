CREATE DATABASE IF NOT EXISTS CATALYST_TEST;
 USE CATALYST_TEST;
CREATE TABLE IF NOT EXISTS users (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    name varchar(50) NOT NULL,
    surname varchar(50) NOT NULL,
    email varchar(255) NOT NULL,
    UNIQUE KEY email_key (email)
    )

SELECT EXISTS (
               SELECT
                   TABLE_NAME
               FROM
                   information_schema.TABLES
               WHERE
                       TABLE_NAME = 'users'
           )
