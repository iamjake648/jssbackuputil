
<?php
/*
Written by Jake Schultz on 23/7/15
This file handles SSH connections into the JSS Server, and running commands.
*/
require 'db.php';

//Action POST variable tells this file which action to take.
//Not all post variables need to be used every time.
$action = $_POST['action'];
$username = $_POST['username'];
$ssh_password = $_POST['sshpassword'];
$mysql_password = $_POST['mysqlpassword'];
$mysql_db_local_path = $_POST['mysql_db_local_path'];
$backup_path = $_POST['backup_path'];
$time = $_POST['time'];
$delete_older_than = $_POST['delete_older_than'];
$path = $_POST['path'];
$filename_delete_jss_server = $_POST['filename'];

//Pulling the SSH Host, Username, ect from the database.
$getInfo = $db->prepare("SELECT * FROM accounts WHERE username = :username;");
$getInfo ->execute(array(':username' => $username));
$data = $getInfo->Fetch();

//Create a new instance of the encryption class to decode information from the database.
$converter = new Encryption;
$ssh_host = $converter -> decode($data['ssh_host']);
$ssh_username = $converter -> decode($data['ssh_username']);
$mysql_username =  $converter -> decode($data['mysql_username']);
$mysql_database =  $converter -> decode($data['mysql_database_name']);

//SSH Host, Username pulled from the database, SSH Password Posted, and command can be input when function called.
//Echo errors and handle them with JS.
function exec_command($command,$ssh_host,$ssh_username,$ssh_password){
	if (!function_exists("ssh2_connect")) die("function ssh2_connect doesn't exist");
    // log in at server on port 22
	if(!($con = ssh2_connect(trim($ssh_host), 22))){
		echo "hostfail\n";
	} else {
		// try to authenticate with username root, password secretpassword
		if(!ssh2_auth_password($con, $ssh_username, $ssh_password)) {
			echo "loginfail\n";
		} else {
			if (!($stream = ssh2_exec($con, $command ))) {
				echo "commandfail\n";
			} else {
				stream_set_blocking($stream, true);
				$result = stream_get_contents($stream);
				echo "Done";
				//Make sure to close the connection
				fclose($stream);
			}
		}
	}
}

//Simple action to stop tomcat.
//REQUIRED POSTS: Action, Username, SSH Password
if ($action == "stop_tomcat"){
	//Check for OSX/Linux in the DB. Different commands apply.
	if ($data['type'] == "Linux"){
		//Build the command, then run it through our exec function.
		 $com = 'echo '.$ssh_password.' | sudo -S service jamf.tomcat7 stop';
		 exec_command($com,$ssh_host,$ssh_username,$ssh_password);
	} else {
		$com = 'echo '.$ssh_password.' | sudo -S launchctl unload /Library/LaunchDaemons/com.jamfsoftware.tomcat.plist';
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
	}

//Simple action to start tomcat.
//REQUIRED POSTS: Action, Username, SSH Password
} else if ($action == "start_tomcat"){
	if ($data['type'] == "Linux"){
		$com = 'echo '.$ssh_password.' | sudo -S service jamf.tomcat7 start';
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
	} else {
		$com = 'echo '.$ssh_password.' | sudo -S launchctl load /Library/LaunchDaemons/com.jamfsoftware.tomcat.plist';
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
	}

//This function uses mysqldump to dumb the database to a directory timestamped with epoc time
//REQUIRED POSTS: Action, Username, SSH Password, MySQL DB Output Path
//OPTIONAL POSTS: MySQL DB Password
} else if ($action == "backup_local"){
	//Get current epoc time
	$time = time();
	//If they don't supply a MySQL password, we need to run a slightly different command.
	if ($mysql_password == ""){
		//Build the command to dump the database.
		$com = "mysqldump -u ".$mysql_username." ".$mysql_database." > ".$mysql_db_local_path."/JSSUtilBackup-".$time.".sql";
	} else {
		$com = "mysqldump -u ".$mysql_username." -p ".$mysql_password." ".$mysql_database." > ".$mysql_db_local_path."/JSSUtilBackup-".$time.".sql";
	}
	//Execute command built earlier.
	exec_command($com,$ssh_host,$ssh_username,$ssh_password);

//Very similar to the MySQL backup function, but also SSH in and build a download link.
//REQUIRED POSTS: Action, Username, SSH Password, MySQL DB Output Path
//OPTIONAL POSTS: MySQL DB Password
} else if ($action == "backup_local_and_download"){
	//See above documentation for backup.
	$time = time();
	$filename = "JSSUtilBackup-".$time.".sql";
	if ($mysql_password == ""){
		$com = "mysqldump -u ".$mysql_username." ".$mysql_database." > ".$mysql_db_local_path."/".$filename;
	} else {
		$com = "mysqldump -u ".$mysql_username." -p ".$mysql_password." ".$mysql_database." > ".$mysql_db_local_path."/JSSUtilBackup-".$time.".sql";
	}
	exec_command($com,$ssh_host,$ssh_username,$ssh_password);
	//Change the ownership of the backup just made so we can grab it and download it.
	$com = "echo ".$ssh_password." | sudo -S chmod 777 ".$mysql_db_local_path."/".$filename;
	exec_command($com,$ssh_host,$ssh_username,$ssh_password);

	if ($data['type'] == "Linux"){
		$com = "echo ".$ssh_password." | sudo -S mv ".$mysql_db_local_path."/".$filename." /usr/local/jss/tomcat/webapps/ROOT/";
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
	} else {
		$com = "echo ".$ssh_password." | sudo -S mv ".$mysql_db_local_path."/".$filename." /Library/JSS/Tomcat/webapps/ROOT/";
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
	}

	echo "FILE|__|".$filename."|__|".$ssh_host;

//This function restores a DB from a local file.
//REQUIRED POSTS: Action, Username, SSH Password, MySQL DB Output Path
//OPTIONAL POSTS: MySQL DB Password
} else if ($action == "restore_local"){
	//Check if the mysql password is provided.
	if ($mysql_password == "") {
		//Run the mysql comand to import the DB info.
		$com = "mysql -u " . $mysql_username . " " . $mysql_database . " < " . $mysql_db_local_path;
	} else {
		$com = "mysql -u " . $mysql_username . " -p " . $mysql_password . " " . $mysql_database . " < " . $mysql_db_local_path;
	}
	//Execute command.
	exec_command($com,$ssh_host,$ssh_username,$ssh_password);

//This function restores a DB from a local file.
//REQUIRED POSTS: Action, Username, SSH Password, MySQL DB Output Path, Backup Time
//OPTIONAL POSTS: MySQL DB Password
} else if ($action == "schedule_backup"){
	//We are going to enter the user's backup time and path to the DB, but encode it first.
	$time_e = $converter->encode($time);
	$path_e = $converter->encode($backup_path);
	$getInfo = $db->prepare("UPDATE accounts SET schedule_backup = :time, schedule_backup_path = :backup_path WHERE username = :username;");
	$getInfo ->execute(array(':time' => $time_e, ':backup_path' => $path_e, ':username' => $username));
	//Get current time in epoch.
	$epoch = time();
	//Check if the time is never and remove ONLY our cronjob otherwise make a new cronjob based on the time
	if ($time == "Never"){
		$com = "crontab -l > /tmp/cron_jobs.bak";
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);

		$com = "sed -i '/JSSUtilBackup/d' /tmp/cron_jobs.bak";
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);

		$com = "crontab /tmp/cron_jobs.bak";
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
	} else {
		//We were posted a time, so remove trailing 00.
		$time_arr = explode(":",$time);
		$time = $time_arr[0];
		//Backup current cronjobs
		$com = "crontab -l > /tmp/cron_jobs.bak";
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
		//Remove any line that matches our syntax
		$com = "sed -i '/JSSUtilBackup/d' /tmp/cron_jobs.bak";
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
		//Reimport the cron jobs
		$com = "crontab /tmp/cron_jobs.bak";
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
		//Check if they supplied a mysql password, then build the cronjob
		if ($mysql_password == "") {
			$com = "(crontab -u ".$ssh_username." -l; echo '* ".$time." * * * mysqldump -u ".$mysql_username." ".$mysql_database." > ".$backup_path."/JSSUtilBackup-".$epoch.".sql' ) | crontab -u ".$ssh_username." -";
		} else {
			$com = "(crontab -u ".$ssh_username." -l; echo '* ".$time." * * * mysqldump -u ".$mysql_username." -p ".$mysql_password." ".$mysql_database." > ".$backup_path."/JSSUtilBackup-".$epoch.".sql' ) | crontab -u ".$ssh_username." -";
		}
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
	}

//This function restores a DB from a local file.
//REQUIRED POSTS: Action, Username, SSH Password, MySQL DB Output Path, time Older than to delete
//OPTIONAL POSTS: MySQL DB Password
} else if ($action == "delete_older_than"){
	//Insert the Delete Older Than time to the DB.
	$getInfo = $db->prepare("UPDATE accounts SET delete_older_than = :time WHERE username = :username;");
	$getInfo ->execute(array(':time' => $delete_older_than));
	//Open a new SSH connection to check the given directory for files generated by JSSBackupUtil
	//NOTE only files generated from JSSbackupUtil that remain the same name will be effective
	$connection = ssh2_connect($ssh_host, 22);
	ssh2_auth_password($connection, $ssh_username, $ssh_password);
	$sftp = ssh2_sftp($connection);
	$handle = opendir("ssh2.sftp://".$sftp.$path);
	//Loop through all of the files.
	while (false != ($entry = readdir($handle))){
		//$entry is the file name, check if it's generated by JSSBackupUtil.
		if (strpos($entry, 'JSSUtilBackup') !== false){
			//Some functions to strip the date out of the file
			$date1 = explode("-", $entry);
			$date = explode(".", $date1[1]);
			//Final date time stamp from the file
			$file_date_stamp = $date[0];
			//Today's time in epoch
			$today = time();
			//Delete older than is the posted older than time.
			$should_delete = $today - $delete_older_than;
			//Check if the backup is older
			if ($file_date_stamp < $should_delete){
				//Run a command to delete the file.
				$com = "echo ".$ssh_password." | sudo -S rm ".$path.$entry;
				exec_command($com,$ssh_host,$ssh_username,$ssh_password);
			}

		}
	}
} elseif ($action == "delete_db"){
	if ($data['type'] == "Linux"){
		$com = 'echo '.$ssh_password.' | sudo -S rm /usr/local/jss/tomcat/webapps/ROOT/'.$filename_delete_jss_server;
		exec_command($com,$ssh_host,$ssh_username,$ssh_password);
	}	 else {
		$com = 'echo ' . $ssh_password . ' | sudo -S rm /Library/JSS/Tomcat/webapps/ROOT/' . $filename_delete_jss_server;
		exec_command($com, $ssh_host, $ssh_username, $ssh_password);
	}
} elseif ($action == "test_connection"){
	$com = 'echo '.$ssh_password.' | sudo -S ls';
	exec_command($com,$ssh_host,$ssh_username,$ssh_password);
}


?>

