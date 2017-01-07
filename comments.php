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

if ($method == "GET") {
	if (isset($_POST["postid"])) {
		$postid = $_POST["postid"];
	
		$sql = "SELECT * FROM comments where postid = $postid";
		if (!$cmts = $mysqli->query($sql)) {
			$response["errno"] = 4;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
		$response["count"] = $cmts->num_rows;
		$i = 1;
		while ($comment = $cmts->fetch_assoc()) {
			$userid = $comment["userid"];
			$sql = "SELECT * FROM users WHERE userid = $userid";
			if (!$result = $mysqli->query($sql)) {
				$response["errno"] = 4;
				$response["error"] = $mysqli->error;
				respond($response);
				exit;
			}
			$user = $result->fetch_assoc();
			$item = array("userid"=>$userid, "username"=>$user["username"], "nickname"=>$user["nickname"], "picture"=>file_get_contents($user["picture"]), "commentid"=>$comment["commentid"], "commentedid"=>$comment["commentedid"], "content"=>$comment["content"], "time"=>$comment["time"]);
			$response[$i] = $item;
			$i++;
		}

		respond($response);
		exit;

	} else if (isset($_POST["userid"])) {
		$userid = $_POST["userid"];

		$sql = "SELECT * FROM comments where userid = $userid and got = 0";
		if (!$cmts = $mysqli->query($sql)) {
			$response["errno"] = 4;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
		$response["count"] = $cmts->num_rows;
		$i = 1;
		while ($comment = $cmts->fetch_assoc()) {
			$userid = $comment["userid"];
			$sql = "SELECT * FROM users WHERE userid = $userid";
			if (!$result = $mysqli->query($sql)) {
				$response["errno"] = 4;
				$response["error"] = $mysqli->error;
				respond($response);
				exit;
			}
			$user = $result->fetch_assoc();

			$item = array("userid"=>$userid, "username"=>$user["username"], "nickname"=>$user["nickname"], "picture"=>file_get_contents($user["picture"]), "postid"=>$comment["postid"], "commentid"=>$comment["commentid"], "commentedid"=>$comment["commentedid"], "content"=>$comment["content"], "time"=>$comment["time"]);
			
			$postid = $comment["postid"];
			$sql = "SELECT * FROM posts WHERE postid = $postid";
			if (!$result = $mysqli->query($sql)) {
				$response["errno"] = 4;
				$response["error"] = $mysqli->error;
				respond($response);
				exit;
			}
			$post = $result->fetch_assoc();
			if ($post["count"] > 0) {
				$item["post_picture"] = file_get_contents($post["picture1"]);
			}
			
			
			$response[$i] = $item;
			$i++;
		}
		
		$sql = "UPDATE comments SET got = 1 where userid = $userid";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 4;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
		
		respond($response);
		exit;

	} else {
		$response["errno"] = 3;
		$response["error"] = "parameter not received";
		respond($response);
		exit;
	}

} else if ($method == "POST") {
	if (isset($_POST["token"]) && isset($_POST["postid"]) && isset($_POST["time"]) && isset($_POST["content"])) {
		$token = $_POST["token"];
		$postid = $_POST["postid"];
		$commentedid = $_POST["commentedid"];
		$time = $_POST["time"];
		$content = $_POST["content"];
	} else {
		$response["errno"] = 3;
		$response["error"] = "parameter not received";
		respond($response);
		exit;
	}

	if (isset($_POST["commentedid"])) {
		$commentedid = $_POST["commentedid"];
	} else {
		$commentedid = 0;
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
	
	$sql = "INSERT INTO comments (postid, userid, commentedid, time, content) VALUES ($postid, $userid, $commentedid, '$time', '$content')";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 6;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}
	$commentid = $mysqli->insert_id;	
	$response["commentid"] = $commentid;

	$sql = "UPDATE posts SET comments = comments + 1 WHERE postid = $postid";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 6;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}

	respond($response);
	exit;

} else if ($method == "DELETE") {
	if (isset($_POST["token"]) && isset($_POST["id"])) {
		$token = $_POST["token"];
		$id = $_POST["id"];
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

	$sql = "SELECT * FROM comments WHERE commentid = $id";
	$result = $mysqli->query($sql);
	$row = $result->fetch_assoc();
	if ($row["userid"] != $userid) {
		$response["errno"] = 6;
		$response["error"] = "You cannot delete others' comment!";
		respond($response);
		exit;
	}

	$postid = $row["postid"];
	$sql = "UPDATE posts SET comments = comments - 1 WHERE postid = $postid";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 6;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}

	$sql = "DELETE FROM comments WHERE commentid = $id";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 7;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}
	respond($response);
	exit;

}

?>
