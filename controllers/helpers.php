<?php

function getUser($id) {
	global $helper;
	global $dbh;

	$id = sanitize($id,"int");
	$sql = ("select * from users where id = '".escape($id)."'");
	$query = mysqli_query($dbh,$sql);
	$result = mysqli_fetch_array($query);
	
	$helper->set('user',$result);
	return $helper->render();
}

 