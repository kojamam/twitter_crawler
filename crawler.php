<?php
require_once 'Twitter.php';
require_once 'mongoQueue.php';
require_once 'config.php';


// DBへ接続してコレクションを指定
$mongo = new Mongo();
$db = $mongo->selectDB(DB_NAME);
$dataCol = $db->selectCollection(DATA_COLLECTION_NAME);

//キューの用意
$queue = new mongoQueue(DB_NAME, QUEUE_COLLECTION_NAME);
//twitterの認証
$twitter = new Twitter(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

//キューが空の時はじめのユーザのidを取得してenqueue
if($queue->is_empty() == true){
	$userId = $twitter->getUserIdFromScreenName(INITIAL_SCREEN_NAME);
	$queue->enqueue(array("user_id" => $userId));
	file_put_contents("log.txt", '----crawling starts from @'.INITIAL_SCREEN_NAME.'----'."\n\n");
}

//キューを使ってfriendsを幅優先で取得
for ($i=0; $i < 1000; $i++) { 

	$userId = $queue->dequeue()["user_id"];

	if($twitter->isProtected($userId)) {
		echo "{$i}. id: {$userId} skipped\n";
		file_put_contents("log.txt", "{$i}. id: {$userId} skipped\n",  FILE_APPEND);
		continue;
	}

	$limitation = $twitter->getFrendIdsAPILimit();
	while($limitation == 0){
		echo "sleeping...", "\n";
		sleep(300);
		$limitation = $twitter->getFrendIdsAPILimit();
	}

	$data = new stdClass();
	$data->user_id = $userId;
	$data->screen_name = $twitter->getScreenNameFromUserId($userId);
	$data->friends = $twitter->getFriendIds($userId);
	$dataCol->insert($data);

	foreach ($data->friends as $key => $waitingUserId) {
		$enqueued = $dataCol->count(array('user_id' => $waitingUserId));
		if($enqueued == 0){
			$queue->enqueue(array("user_id" => $waitingUserId));
		}
	}

	$data = null;

	echo "{$i}. id: {$userId} inserted\n";
	file_put_contents("log.txt", "{$i}. id: {$userId} inserted\n",  FILE_APPEND);


	sleep(1);
}