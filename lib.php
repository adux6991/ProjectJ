<?php
error_reporting(0);

function logging($data) {
	$output = date("h:i:sa")." ";
	foreach ($data as $key=>$value) {
		if (strpos($key, "picture") === false) {
			foreach ($value as $k=>$v) {
				if (strpos($k, "picture") !== false) {
					$data[$key][$k] = strlen($v);
				}
			}
		} else {
			$data[$key] = strlen($value);
		}
	}
	$output = $output.print_r($data, true)."\n\n";//."\n<br/><br/>\n\n";
	file_put_contents("log.txt", $output, FILE_APPEND | LOCK_EX);
}

function respond($json) {
	logging($json);
	header('Content-type:text/json'); 
	echo json_encode($json);
}

function calcSim($v1, $v2) {
	$n = count($v1);
	$res = 0.0;
	for ($i = 0; $i < $n; $i++) {
		$res = $res + ($v1[$i] - $v2[$i]) * ($v1[$i] - $v2[$i]);
	}
	return $res;
}

?>
