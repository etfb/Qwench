<?php

function edit() {
	global $dbh;

	authenticate(1);

	global $path;
	global $template;
 	$answerid = sanitize($path[2],"int");

	$basePath = basePath();
	$basePathNS = basePathNS();

	$js = <<<EOD

<script src="$basePathNS/js/showdown.js"></script>
<script src="$basePathNS/js/wmd.js"></script>
<link href="$basePathNS/css/wmd.css" type="text/css" rel="stylesheet" />

EOD;

	$template->set('js',$js);

	$sql = ("select * from answers where id = '".escape($answerid)."'");
	$query = mysqli_query($dbh,$sql);
	$result = mysqli_fetch_array($query);

	$template->set('description',$result['description']);
	$template->set('answerid',$result['id']);
}

function post() {
	global $dbh;

	authenticate(1);
	$basePath = basePath();

	$description = sanitize($_POST['description'],"markdown");
	$questionid = sanitize($_POST['questionid'],"int");

	$sql = ("select * from questions where id = '".escape($questionid)."'");
	$query = mysqli_query($dbh,$sql);
	$result = mysqli_fetch_array($query);

	if (strlen($description) < 15 || $result['id'] == '' || $result['id'] == 0) {
		header("Location: $basePath/questions/view/$questionid/{$result['slug']}");
		exit;
	}

	$sql = ("insert into answers (questionid,description,created,updated,userid,accepted,votes) values ('".escape($questionid)."','".escape($description)."',NOW(),NOW(),'".escape($_SESSION['userid'])."','0','0')");
	$query = mysqli_query($dbh,$sql);

	$sql = ("update questions set updated = NOW(), answers=answers+1 where id = '".escape($result['id'])."'");
	$query = mysqli_query($dbh,$sql);

	header("Location: $basePath/questions/view/$questionid/{$result['slug']}");
}

function update() {
	global $dbh;

	authenticate(1);

	$answerid = sanitize($_POST['id'],"int");
	$description = sanitize($_POST['description'],"markdown");

	$sql = ("select * from answers where id = '".escape($answerid)."'");
	$query = mysqli_query($dbh,$sql);
	$result = mysqli_fetch_array($query);

	$sql = ("select * from questions where id = '".escape($result['questionid'])."'");
	$query = mysqli_query($dbh,$sql);
	$qresult = mysqli_fetch_array($query);

	if ($qresult['userid'] != $_SESSION['userid']) {
		$basePath = basePath();
		header("Location: $basePath/questions/view/{$qresult['id']}/{$qresult['slug']}");
	}
	
	$sql = ("update answers set description = '".escape($description)."', updated = NOW() where userid = '".escape($_SESSION['userid'])."' and id = '".escape($answerid)."'");
	$query = mysqli_query($dbh,$sql);

	$sql = ("update questions set updated = NOW() where id = '".escape($result['questionid'])."'");
	$query = mysqli_query($dbh,$sql);

	$basePath = basePath();

	header("Location: $basePath/questions/view/{$qresult['id']}/{$qresult['slug']}");
}

function vote() {
	global $dbh;

	if ($_SESSION['userid'] == '') {
		echo "0Please login to vote";
		exit;
	}

	$id = sanitize($_POST['id'],"int");
	$vote = sanitize($_POST['vote'],"string");

	if ($vote == 'plus') {
		$vote = '+1';
	} else {
		$vote = '-1';
	}

	$sql = ("select answers.userid,answers_votes.id qvid,answers_votes.vote qvvote from answers left join answers_votes on (answers.id = answers_votes.answerid and answers_votes.userid =  '".escape($_SESSION['userid'])."') where answers.id = '".escape($id)."'");
	$query = mysqli_query($dbh,$sql);

	$answer = mysqli_fetch_array($query);

	if ($answer['userid'] == $_SESSION['userid']) {
		echo "0"."You cannot up/down vote your own answer";
		exit;
	}

	if ($answer['qvid'] > 0) {
		
		if ($answer['qvvote'] == 1 && $vote == '+1') {
			$vote = "-1";
			score('a_upvoted_removed',$id,$answer['userid']);
		} else if ($answer['qvvote'] == 1 && $vote == '-1') {
			$vote = "-2";
			score('a_upvoted_removed',$id,$answer['userid']);
			score('a_downvoter',$id);
			score('a_downvoted',$id,$answer['userid']);
		} else if ($answer['qvvote'] == -1 && $vote == '-1') {
			$vote = "+1";
			score('a_downvoter_removed',$id);
			score('a_downvoted_removed',$id,$answer['userid']);
		} else if ($answer['qvvote'] == -1 && $vote == '+1') {
			$vote = "+2";
			score('a_downvoter_removed',$id);
			score('a_downvoted_removed',$id,$answer['userid']);
			score('a_upvoted',$id,$answer['userid']);
		} else if ($answer['qvvote'] == 0) {
			if ($vote == 1) {
				score('a_upvoted',$id,$answer['userid']);
			} else {
				score('a_downvoter',$id);
				score('a_downvoted',$id,$answer['userid']);
			}
		}

		$sql = ("update answers_votes set vote = vote".escape($vote)." where id = '".$answer['qvid']."'");
		$query = mysqli_query($dbh,$sql);

	} else {
		$sql = ("insert into answers_votes (answerid,userid,vote) values ('".escape($id)."','".escape($_SESSION['userid'])."','".escape($vote)."')");
		$query = mysqli_query($dbh,$sql);

		if ($vote == 1) {
			score('a_upvoted',$id,$answer['userid']);
		} else {
			score('a_downvoter',$id);
			score('a_downvoted',$id,$answer['userid']);
		}

	}
	
	$sql_nest = ("update answers set votes = votes".escape($vote)." where id = '".escape($id)."'");
	$query_nest = mysqli_query($dbh,$sql_nest);
	
	echo "1Thankyou for voting";
	exit;

}

function accept() {
	global $dbh;

	authenticate(1);

	$answerid = sanitize($_GET['id'],"int");

	$sql = ("select questionid,userid from answers where id = '".escape($answerid)."'");
	$query = mysqli_query($dbh,$sql);
	$answer = mysqli_fetch_array($query);

	$sql = ("select questions.*,answers.id answerid, answers.userid answeruserid from questions left join answers on (questions.id = answers.questionid and answers.accepted = 1) where questions.id = '".escape($answer['questionid'])."'");
	$query = mysqli_query($dbh,$sql);
	$result = mysqli_fetch_array($query);

	if ($result['kb'] == 1) {
		header("Location: $basePath/questions/view/{$result['id']}/{$result['slug']}");
		exit;
	}

	if ($result['answerid'] > 0) {
		score('a_accepted_removed',$answerid,$result['answeruserid']);
	} else {
		score('a_accepter',$answerid);
	}

	if ($result['userid'] == $_SESSION['userid']) {
		$sql = ("update answers set accepted = '0' where questionid = '".escape($result['id'])."'");
		$query = mysqli_query($dbh,$sql);
		$sql = ("update answers set accepted = '1' where questionid = '".escape($result['id'])."' and id = '".escape($answerid)."'");
		$query = mysqli_query($dbh,$sql);
		$sql = ("update questions set accepted = '1' where id = '".escape($result['id'])."' and userid = '".escape($_SESSION['userid'])."'");
		$query = mysqli_query($dbh,$sql);
		
		score('a_accepted',$answerid,$answer['userid']);

	}

	$basePath = basePath();

	header("Location: $basePath/questions/view/{$result['id']}/{$result['slug']}");
}