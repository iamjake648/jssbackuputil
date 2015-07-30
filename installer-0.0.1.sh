sudo apt-get update
sudo apt-get install unzip apache2 mysql-server php5-mysql php5 libapache2-mod-php5 php5-mcrypt
echo "Please complete the following steps, then press enter.\n"
echo "1.Open MySQL: 'mysql -u root -p'\n"
echo "2.Create jssbackuputil DB 'create database jssbackuputil'\n"
echo "3.Quit mysql. Change to the folder with the jssbackuputil.sql db\n"
echo "4.Import the DB. 'mysql -u root -p jssbackuputil < jssbackuputil.sql'\n"
pause 'Press [Enter] key to continue ONLY after these steps have been completed...'
sudo unzip Archive.zip -d /var/www/html/

