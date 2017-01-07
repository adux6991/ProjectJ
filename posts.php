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
	if (isset($_POST["userid"])) {
		$userid = $_POST["userid"];
		$sql = "SELECT * FROM posts WHERE userid = $userid";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 4;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
		$response["count"] = $result->num_rows;
		$i = 1;
		while ($post = $result->fetch_assoc()) {
			$item = array("id"=>$post["postid"], "content"=>$post["content"], "longitude"=>$post["longitude"], "latitude"=>$post["latitude"], "time"=>$post["time"], "comments"=>$post["comments"], "likes"=>$post["likes"]); 
			if ($post["count"] > 0) {
				$item["picture"] = file_get_contents($post["picture1"]);
			}
			$response[$i] = $item;
			$i++;
		}
		respond($response);
		exit;
		
	} elseif (isset($_POST["id"])) {
		$id = $_POST["id"];
	} else {
		$response["errno"] = 3;
		$response["error"] = "parameter not received";
		respond($response);
		exit;
	}
	if ($id == "0") {
		$sql = "SELECT * FROM posts";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 4;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
		$response["count"] = $result->num_rows;
		$i = 1;
		while ($post = $result->fetch_assoc()) {
			$item = array("id"=>$post["postid"], "content"=>$post["content"], "longitude"=>$post["longitude"], "latitude"=>$post["latitude"], "time"=>$post["time"]);
			$response[$i] = $item;
			$i++;
		}
		respond($response);
		exit;

	} else {
		$sql = "SELECT * FROM posts WHERE postid = $id";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 4;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
		$post = $result->fetch_assoc();
		$response["postid"] = $post["postid"];
		$response["userid"] = $post["userid"];
		$response["longitude"] = $post["longitude"];
		$response["latitude"] = $post["latitude"];
		$response["time"] = $post["time"];
		$response["content"] = $post["content"];
		$response["comments"] = $post["comments"];
		$response["likes"] = $post["likes"];
		$response["count"] = $post["count"];
		$count = (int)($post["count"]);
		for ($i = 1; $i <= $count; $i++) {
			$pic_name = $post["picture".$i];
			$pic = file_get_contents($pic_name);
			$response["picture".$i] = $pic;
		}

		// user picture
		$userid = $post["userid"];
		$sql = "SELECT * FROM users WHERE userid = $userid";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 4;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
		$user = $result->fetch_assoc();
		$response["username"] = $user["username"];
		$response["nickname"] = $user["nickname"];
		$response["picture"] = file_get_contents($user["picture"]);

		// if liked
		$liked = 0;
		if (isset($_POST["token"])) {
			$token = $_POST["token"];

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

			$sql = "SELECT * FROM likes WHERE userid = $userid and postid = $id";
			if (!$result = $mysqli->query($sql)) {
				$response["errno"] = 4;
				$response["error"] = $mysqli->error;
				respond($response);
				exit;
			}
			if ($result->num_rows === 0) {
				$liked = 0;
			} else {
				$liked = 1;
			}

		}
		$response["liked"] = $liked;

		// recommendation
		$sql = "SELECT postid, vector FROM posts WHERE postid != $id";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 4;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
		$minDist = 200.1;
		$simPost = 0;
		$vector = explode(' ', $post["vector"]);
		while ($row = $result->fetch_assoc()) {
			$target = explode(' ', $row["vector"]);
			$dist = calcSim($vector, $target);
			if ($dist < $minDist) {
				$minDist = $dist;
				$simPost = $row["postid"];
			}
		}
		$sql = "SELECT * FROM posts WHERE postid = $simPost";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 4;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
		$post = $result->fetch_assoc();
		$response["similar_postid"] = $simPost;
		$response["similar_content"] = $post["content"];
		if ($post["count"] > 0) {
			$response["similar_picture"] = file_get_contents($post["picture1"]);
		}

		respond($response);
		exit;

	}

} else if ($method == "POST") {
	if (isset($_POST["token"]) && isset($_POST["longitude"]) && isset($_POST["latitude"]) && isset($_POST["time"]) && isset($_POST["content"]) && isset($_POST["count"])) {
		$token = $_POST["token"];
		$longitude = $_POST["longitude"];
		$latitude = $_POST["latitude"];
		$time = $_POST["time"];
		$content = $_POST["content"];
		$count = (int)($_POST["count"]);
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

	$sql = "UPDATE users SET posts = posts + 1 WHERE userid = $userid";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 6;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}
	
	$sql = "INSERT INTO posts (userid, longitude, latitude, time, content, count) VALUES ($userid, '$longitude', '$latitude', '$time', '$content', $count)";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 6;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}
	$postid = $mysqli->insert_id;
	
	for ($i = 1; $i <= $count; $i++) {
		$pic_name = "uploads/".$userid.'_'.$postid.'_'.$i;
		$picture = $_POST["picture".$i];
		file_put_contents($pic_name, $picture);
		$sql = "UPDATE posts SET picture".$i."='$pic_name' WHERE postid=$postid";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 7;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
	}
		
	$response["postid"] = $postid;
	respond($response);

	// recommendation
	$content = str_replace('"', ' ', $content);
	$command = "/usr/local/Cellar/python/2.7.13/Frameworks/Python.framework/Versions/2.7/Resources/Python.app/Contents/MacOS/Python /Library/WebServer/Documents/api/recommend/test.py ".$postid." \"".$content."\" >> /Library/WebServer/Documents/api/log.txt &";
	exec($command);

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

	$sql = "SELECT * FROM posts WHERE postid = $id";
	$result = $mysqli->query($sql);
	$row = $result->fetch_assoc();
	if ($row["userid"] != $userid) {
		$response["errno"] = 6;
		$response["error"] = "You cannot delete others' post!";
		respond($response);
		exit;
	}

	$sql = "UPDATE users SET posts = posts - 1 WHERE userid = $userid";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 7;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}

	$sql = "DELETE FROM posts WHERE postid = $id";
	if (!$result = $mysqli->query($sql)) {
		$response["errno"] = 7;
		$response["error"] = $mysqli->error;
		respond($response);
		exit;
	}
	respond($response);
	exit;

} else if ($method == "UPDATE") {
	if (isset($_POST["postid"]) && isset($_POST["like"]) && isset($_POST["token"])) {
		$postid = $_POST["postid"];
		$like = $_POST["like"];
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
	$row = $result->fetch_assoc();
	$userid = $row["userid"];

	if ((int)($like) == 1) {
		$sql = "UPDATE posts SET likes = likes + 1 WHERE postid = $postid";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 6;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}

		$sql = "INSERT INTO likes (userid, postid) VALUES ($userid, $postid)";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 6;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}
		

	} else {
		$sql = "UPDATE posts SET likes = likes - 1 WHERE postid = $postid";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 6;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}

		$sql = "DELETE FROM likes WHERE userid = $userid and postid = $postid";
		if (!$result = $mysqli->query($sql)) {
			$response["errno"] = 6;
			$response["error"] = $mysqli->error;
			respond($response);
			exit;
		}

	}

	respond($response);
	exit;
	

}

?>
