<?php
require_once 'TwistOAuth.phar';
require_once 'mongoQueue.php';
require_once 'config.php';


// DBへ接続してコレクションを指定
$mongo = new Mongo();
$db = $mongo->selectDB(DB_NAME);
$col = $db->selectCollection(DATA_COLLECTION_NAME);
$queue = $db->selectCollection(QUEUE_COLLECTION_NAME);

//Twitter OAuth認証
try {
	$to = new TwistOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
} catch(TwistException $e) {
	echo  $e->getMessage();
}

//はじめのユーザのidを取得
try{
	$user = $to->get('users/show', array('screen_name' => START_SCREEN_NAME));
	$userId = $user->id;
}catch(TwistException $e){
	echo $e->getMessage();
}

$queue->enqueue($userId);

//キューを使って取得
for ($i=0; $i < 1000; $i++) { 

	$userId = $queue->dequeue();

	$limitation = getLimit($to);

	while($limitation == 0){
		echo "sleeping...", "\n";
		sleep(60);
		$limitation = getLimit($to);
	}	

	try{
		$friends = $to->get('friends/ids', array("user_id" => $userId));
	}catch(TwistException $e){
		echo $e->getMessage();
	}

	$data = new stdClass();

	$data->user_id = $userId;
	$data->friends = $friends->ids;
	$col->insert($data);

	foreach ($data->friends as $key => $tUserId) {
		if($col->findOne(array('user_id' => $tUserId)) == null){
			$queue->enqueue($tUserId);
		}
	}

	echo "{$i}. id: {$userId} \n";
	sleep(1);
}


function getLimit($to){
	return $to->get('application/rate_limit_status', array('resources' => 'friends'))
						->resources->friends->{'/friends/ids'}->remaining;
}