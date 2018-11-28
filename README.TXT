Qwench
Copyright (c) 2009-2010 Inscripts

Installation Instructions
-----------------------------------------------

1. Create a MySQL database on your system.
2. Create a user with full permissions over the new database.
3. Edit config.default.php:
   a. Specify the database name, user name and password (DBNAME, DBUSERNAME, DBPASSWORD).
   b. Generate a long random string and put it as the value of SALT.
   c. Specify BASE_DIR according to where you have your code -- '' if it's in the root, '/directoryname' otherwise.
   d. Save the file as config.php.
4. Go to install.php in your browser.
5. For safety, delete install.php when you're done (the code will try to do this but may fail silently).
6. Run your new site.
7. Register by clicking the register link or going to /users/register

-----------------------------------------------
