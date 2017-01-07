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

	if ($result->num_rows != 0) {
		$response["errno"] = 5;
		$response["error"] = "username already exists";
		respond($response);
		exit;
	}

	$encrypted = password_hash($password, PASSWORD_BCRYPT);
	$sql = "INSERT INTO users (username, password, nickname) VALUES ('$username', '$encrypted', '$username')";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 6;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}

	respond($response);
	exit;

} else if ($method == "UPDATE") {
	if (isset($_POST["token"]) && isset($_POST["nickname"]) && isset($_POST["gender"]) && isset($_POST["profile"])) {
		$token = $_POST["token"];
		$nickname = $_POST["nickname"];
		$gender = $_POST["gender"];
		$profile = $_POST["profile"];
		$picture = $_POST["picture"];
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
	$row = $result->fetch_assoc();
	$userid = $row["userid"];

	if (isset($_POST["picture"])) {
		//$picture = base64_decode($_POST["picture"]);
		$picture = $_POST["picture"];
		$pic_name = "uploads/".$userid;
		file_put_contents($pic_name, $picture);
	} else {
		$pic_name = "uploads/default";
	}
	
	$sql = "UPDATE users SET nickname='$nickname', gender=$gender, profile='$profile', picture='$pic_name' WHERE userid=$userid";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 6;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}
	
	respond($response);
	exit;

} else if ($method == "GET") {

	if (isset($_POST["id"])) {
		$id = $_POST["id"];
	} else {
		$response["errno"] = 3;
		$response["error"] = "parameter not received";
		respond($response);
		exit;
	}
	
	$sql = "SELECT * FROM users WHERE userid = '$id'";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 4;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}
	$user = $result->fetch_assoc();

	$response["username"] = $user["username"];
	$response["nickname"] = $user["nickname"];
	$response["gender"] = $user["gender"];
	$response["profile"] = $user["profile"];
	$response["posts"] = $user["posts"];

	$pic = file_get_contents($user["picture"]);
	$response["len"] = strlen($pic);
	$response["picture"] = $pic;
	
	respond($response);

	exit;

}

?>
