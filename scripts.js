//Called from register.html to register users into the DB.
function reg() {
	//Check to make sure they used the captcha
	if (grecaptcha.getResponse() != "") {
		var password = $("#password").val();
		var password1 = $("#password1").val();
		if (password === password1) {
			var email = $("#email").val();
			var username = $("#username").val();
			//Post it to the DB.
			$.ajax({
				type: "post",
				url: "php/userController.php",
				data: {
					username: username,
					password: password,
					email: email
				},
				success: function(data) {
					console.log(data);
					sessionStorage.setItem("current_user", username);
					window.location = "index.html";
				}
			});
		} else {
			alert('Passwords do not Match');
		}
	} else {
		alert("Invalid Captcha");
	}
}
//Ajax to the login controller and check if they exist in the DB
function login() {
	var username = document.getElementById("username").value;
	var password = document.getElementById("password").value;
	$.ajax({
		type: "post",
		url: "php/login.php",
		data: {
			username: username,
			password: password
		},
		success: function(data) {
			if (data == 1) {
				sessionStorage.setItem("current_user", username);
				window.location = "app.html";
			} else {
				alert('Invalid Credentials');
			}
		}
	});
}
//Function to handle inputting information to the DB
function addToDatabase(action, value) {
	var username = sessionStorage.getItem("current_user");
	$.ajax({
		type: "post",
		url: "php/infoController.php",
		data: {
			action: action,
			value: value,
			username: username
		},
		success: function(data) {
			if (action == "mysql_database_name"){
				location.reload();
			}
		}
	});
}
//This function handles inputting all of the information about the User env to the DB
function osType() {
	swal({
		title: "Which OS?",
		text: "What OS is your JSS On?",
		type: "warning",
		closeOnConfirm: false,
		closeOnCancel: false,
		showCancelButton: true,
		cancelButtonText: "OSX",
		confirmButtonText: "Linux"
	}, function(isConfirm) {
		if (isConfirm) {
			addToDatabase("Type", "Linux");
			enterInformation();
		} else {
			addToDatabase("Type", "OSX");
			enterInformation();
		}
		//swal("Congrats!","We are all setup!","success")
	});
}

function enterInformation() {
	swal({
		title: "Let's get setup...",
		text: "Enter your SSH Username",
		type: "input",
		closeOnConfirm: false,
		showCancelButton: false,
	}, function(inputValue) {
		if (inputValue === false) return false;
		if (inputValue === "") {
			swal.showInputError("You need to write something!");
			return false
		}
		addToDatabase("ssh_username", inputValue);
		swal({
			title: "We encrypt all your information.",
			text: "Enter your SSH Host",
			type: "input",
			closeOnConfirm: false,
			showCancelButton: false,
		}, function(inputValue) {
			if (inputValue === false) return false;
			if (inputValue === "") {
				swal.showInputError("You need to write something!");
				return false
			}
			addToDatabase("ssh_host", inputValue);
			swal({
				title: "Root is not recommended",
				text: "Enter your MySQL Username",
				type: "input",
				closeOnConfirm: false,
				showCancelButton: false,
			}, function(inputValue) {
				if (inputValue === false) return false;
				if (inputValue === "") {
					swal.showInputError("You need to write something!");
					return false
				}
				addToDatabase("mysql_username", inputValue);
				swal({
					title: "Your JAMF Software database name.",
					text: "Enter your Database Name",
					type: "input",
					closeOnConfirm: true,
					showCancelButton: false,
				}, function(inputValue) {
					if (inputValue === false) return false;
					if (inputValue === "") {
						swal.showInputError("You need to write something!");
						return false
					}
					addToDatabase("mysql_database_name", inputValue);
				});
			});
		});
	});
}
$(document).ready(function(e) {
	//Check to see if they have informtation in the DB for the given user.
	var username = sessionStorage.getItem("current_user");
	$.ajax({
		type: "post",
		url: "php/checkData.php",
		data: {
			username: username
		},
		success: function(data) {
			if (data == 0) {
				osType();
			} else {
				var items = data.split("||");
				console.log(items);
				if (items[0] == "Linux" && items[1] == ""){
					$("#schedule_backup_path").val("/usr/local/jss/backups/database");
				} else if (items[0] == "Linux" && items[1] != ""){
					$("#schedule_backup_path").val(items[1]);
				} else if (items[1] == "Mac" && items[1] == ""){
					$("#schedule_backup_path").val("/Library/JSS/Backups/Database");
				} else if (items[1] == "Mac" && items[1] != ""){
					$("#schedule_backup_path").val(items[1]);
				}
			}
		}
	});

	$.ajax({
		type: "post",
		url: "php/loadData.php",
		data: {
			username: username
		},
		success: function(data) {
			$("#server_info").html(data);
		}
	});
	//Flash Important Elements
	$("#ssh_pass").fadeIn(1000).fadeOut(1000).fadeIn(1000).fadeOut(1000).fadeIn(1000);
	$("#mysql_pass").fadeIn(1000).fadeOut(1000).fadeIn(1000).fadeOut(1000).fadeIn(1000);

	//Listen for changes with the schedule backup menu
	$("#schedule_backup").change(function() {
		takeAction("schedule_backup");
	});
	//Listen for changes with the delete older than menu
	$("#delete_older_than").change(function() {
		var day_value = parseInt($("#delete_older_than").val()) * 86400;
		var action = "delete_older_than";
		var sshpass = $("#ssh_pass").val();
		var username = sessionStorage.getItem("current_user");
		var path = $("#delete_older_than_path").val();
		$.ajax({
			type: "post",
			url: "php/generateScripts.php",
			data: {
				action: action,
				username: username,
				sshpassword: sshpass,
				delete_older_than : day_value,
				path : path
			},
			success: function(data) {
				console.log(data);
			}
		});
	});
});

//Function to handle showing alerts
function messageCheck(output) {
	console.log(output);
	if (output.indexOf("FILE|__|") != -1) {
		var contents = output.split("|__|");
		$("#backup_download").html('<a href="https://' + contents[2] + ':8443/'+contents[1]+'" target="_blank">' + contents[1] + '</a> <a href="#" onclick="takeAction(\'delete_db\')">!Delete File</a>');

	} else {
		var string = $.trim(output);
		if (string == "hostfail") {
			alertify.error("Unable to connect to SSH Host");
		} else if (string == "loginfail") {
			alertify.error("Unable to Login with Credentials");
		} else if (string == "commandfail") {
			alertify.error("Unable to execute Command");
		} else if (string == "Done") {
			alertify.success("Command Ran Successfully");
		} else if (string == ""){
			alertify.success("Command Ran Successfully");
		} else {
			alertify.success(output);
		}

	}
}

function takeAction(action) {
	alertify.log("Working...");
	var username = sessionStorage.getItem("current_user");
	window.sshpass;
	window.mysqlpassword;
	window.sshpass = $("#ssh_pass").val();
	if (window.sshpass != "") {
		if (action == "backup_local") {
			backupLocal("backup_local", username);
		} else if (action == "restore_local") {
			restoreLocal("restore_local", username);
		} else if (action == "backup_local_and_download") {
			backupLocal("backup_local_and_download", username);
			alertify.success("This can take a while for large databases, file will be outputted above button.");
		}
		if (action == "stop_tomcat") {
			$.ajax({
				type: "post",
				url: "php/generateScripts.php",
				data: {
					action: action,
					username: username,
					sshpassword: window.sshpass
				},
				success: function (data) {
					messageCheck(data);
				}
			});
		} else if (action == "test_connection"){
			$.ajax({
				type: "post",
				url: "php/generateScripts.php",
				data: {
					action: action,
					username: username,
					sshpassword: window.sshpass
				},
				success: function (data) {
					messageCheck(data);
				}
			});
		} else if (action == "start_tomcat") {
			$.ajax({
				type: "post",
				url: "php/generateScripts.php",
				data: {
					action: action,
					username: username,
					sshpassword: window.sshpass
				},
				success: function(data) {
					messageCheck(data);
				}
			});
		} else if (action == "schedule_backup") {
			var time = $("#schedule_backup").val();
			var path = $("#schedule_backup_path").val();
			var mysqlpassword = $("#mysql_pass").val();
			if (mysqlpassword == "") {
				alertify.error("Warning: No SQL pass specified.")
			}
			if (path != "") {
				$.ajax({
					type: "post",
					url: "php/generateScripts.php",
					data: {
						action: action,
						username: username,
						sshpassword: window.sshpass,
						mysqlpassword: mysqlpassword,
						backup_path: path,
						time: time
					},
					success: function(data) {
						console.log(data);
						messageCheck(data);
					}
				});
				if (time == "Never") {
					alertify.success("Cron Job Removed.")
				} else {
					alertify.success("Cron Job is put in place to backup.");
				}
			} else {
				alertify.error("Must Specify Scheduled Backup Path.");
			}
		} else if (action == "delete_db"){
			var full_text = $("#backup_download").text();
			var filename = full_text.substr(0,full_text.indexOf(" "));
			$.ajax({
				type: "post",
				url: "php/generateScripts.php",
				data: {
					action: action,
					username: username,
					sshpassword: window.sshpass,
					filename : filename
				},
				success: function(data) {
					messageCheck(data);
					$("#backup_download").html("");
				}
			});
		}
	} else {
		alertify.error("Must Input SSH Password.");
	}
}

function backupLocal(action, username) {
	var mysqlpassword = $("#mysql_pass").val();
	var mysql_db_local_path = $("#local_path").val();
	if (mysqlpassword == "") {
		alertify.error("Warning: No SQL pass specified.")
	}
	if (mysql_db_local_path != "" || action == "backup_local_and_download") {
		$.ajax({
			type: "post",
			url: "php/generateScripts.php",
			data: {
				action: action,
				username: username,
				sshpassword: window.sshpass,
				mysqlpassword: mysqlpassword,
				mysql_db_local_path: mysql_db_local_path
			},
			success: function(data) {
				messageCheck(data);
			}
		});
	} else {
		alertify.error("Must Input MySQL Path");
	}
}

function restoreLocal(action, username) {
	var mysqlpassword = $("#mysql_pass").val();
	var mysql_db_local_path = $("#local_restore_path").val();
	if (mysqlpassword == "") {
		alertify.error("Warning: No SQL pass specified.")
	}
	if (mysql_db_local_path != "") {
		$.ajax({
			type: "post",
			url: "php/generateScripts.php",
			data: {
				action: action,
				username: username,
				sshpassword: window.sshpass,
				mysqlpassword: mysqlpassword,
				mysql_db_local_path: mysql_db_local_path
			},
			success: function(data) {
				messageCheck(data);
			}
		});
	} else {
		alertify.error("Must Input MySQL Path and Password");
	}
}