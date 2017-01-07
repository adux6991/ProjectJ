<?php
error_reporting(0);

require "lib.php";
logging($argv);

$response = array();

$mysqli = new mysqli('127.0.0.1', 'root', '***', 'ProjectJ');
if ($mysqli->connect_errno) {
	$response["errno"] = 2;
	$response["error"] = "database error: ".$mysqli->connect_error;
	logging($response);
	exit;
}

$postid = $argv[1];
$vector = $argv[2];

$sql = "UPDATE posts SET vector = '$vector' WHERE postid = $postid";
if (!$result = $mysqli->query($sql)) {
	$response["errno"] = 4;
	$response["error"] = $mysqli->error;
	logging($response);
	exit;
}

?>
