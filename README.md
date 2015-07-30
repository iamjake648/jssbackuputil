# jssbackuputil
A web based implemntation of the JSSBackupUtil. <br />

![alt text](screen.png "Screenshot of the Interface")

*This package requires the LAMP stack to function. <br />

###Features: <br />
1. Start and Stop Tomcat over an SSH Connection <br />
2. Backup Databases to a chosen Directory <br />
2a. Backup and download to a remote machine.  <br />
3. Restore a database backup from a .sql file.  <br />
4. Scheudle automatic backups <br />
5. Automatically delete backups older than a certain time. <br />

###TODO:<br />
1. Create an Installer<br />
2. Implement more error logging. <br />
3. Finish Documenting Code<br />
4. Add Windows Support? <br />

###How to use: <br />
1. After installing the LAMP stack, add all of the files to the PHP /var/www/html dir.<br />
2. Import the DB to mysql and configure db.php. <br />
3. Register users who would like to use the app in /register. Each users configuration goes into the DB seperate.<br />
4. Use it! 
