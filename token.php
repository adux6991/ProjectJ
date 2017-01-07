<?php
error_reporting(0);

require "lib.php";
logging($_POST);

$response = array("errno"=>0, "error"=>"");

if (isset($_POST["method"])) {
	$method = $_POST["method"];
} else {
	$response["errno"] = 1;
	$response["error"] = "method not set";
	respond($response);
	exit;
}

$mysqli = new mysqli('127.0.0.1', 'root', '***', 'ProjectJ');
if ($mysqli->connect_errno) {
	$response["errno"] = 2;
	$response["error"] = "database error: ".$mysqli->connect_error;
	respond($response);
	exit;
}

if ($method == "POST") {
	if (isset($_POST["username"]) && isset($_POST["password"])) {
		$username = $_POST["username"];
		$password = $_POST["password"];
	} else {
		$response["errno"] = 3;
		$response["error"] = "parameter not received";
		respond($response);
		exit;
	}

	$sql = "SELECT * FROM users WHERE username = '$username'";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 4;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}

	if ($result->num_rows === 0) {
		$response["errno"] = 5;
		$response["error"] = "username not exist";
		respond($response);
		exit;
	}

	$user = $result->fetch_assoc();
	$encrypted = $user["password"];
	if (!password_verify($password, $encrypted)) {
		$response["errno"] = 6;
		$response["error"] = "password incorrect";
		respond($response);
		exit;
	}

	$userid = $user["userid"];
	$token = md5($username.$userid.(string)time());
	$sql = "INSERT INTO token (userid, token) VALUES ('$userid', '$token') ON DUPLICATE KEY UPDATE token = '$token'";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 7;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}

	$response["token"] = $token;
	$response["userid"] = $userid;
	$response["nickname"] = $user["nickname"];
	$response["gender"] = $user["gender"];
	$response["profile"] = $user["profile"];
	$pic = file_get_contents($user["picture"]);	
	$len = strlen($pic);
	$response["len"] = $len;
	$response["picture"] = $pic;

	logging($response);
	respond($response);
	exit;

} else if ($method ==  "DELTE") {	
	if (isset($_POST["token"])) {
		$token = $_POST["token"];
	} else {
		$response["errno"] = 3;
		$response["error"] = "parameter not received";
		respond($response);
		exit;
	}

	$sql = "SELECT * FROM token WHERE token = '$token'";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 4;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}
	if ($result->num_rows === 0) {
		$response["errno"] = 5;
		$response["error"] = "token invalid";
		respond($response);
		exit;
	}

	$sql = "DELETE FROM token WHERE token = '$token'";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 6;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}
	respond($response);
	exit;
}

?>
