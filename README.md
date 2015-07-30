# jssbackuputil
A web based implemntation of the JSSBackupUtil.

![Alt text](https://www.dropbox.com/s/04ltmbgii86viab/Screen%20Shot%202015-07-30%20at%2012.40.36%20PM.png?dl=0 "Screenshot of the Interface")

*This package requires the LAMP stack to function. 

Features:
1. Start and Stop Tomcat over an SSH Connection
2. Backup Databases to a chosen Directory
2a. Backup and download to a remote machine. 
3. Restore a database backup from a .sql file. 
4. Scheudle automatic backups
5. Automatically delete backups older than a certain time. 

TODO:
1. Create an Installer
2. Implement more error logging. 
3. Finish Documenting Code
4. Add Windows Support? 

How to use: 
1. After installing the LAMP stack, add all of the files to the PHP /var/www/html dir.
2. Import the DB to mysql and configure db.php. 
3. Register users who would like to use the app in /register. Each users configuration goes into the DB seperate.
4. Use it! 
