<?php
require_once 'Twitter.php';
require_once 'mongoQueue.php';
require_once 'config.php';

$logFile = "crawler.log";

echo "You can kill this process ONLY when this is sleeping.\n\n";

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
	file_put_contents($logFile, '----crawlering starts from @'.INITIAL_SCREEN_NAME.'----'."\n\n");
}

$i = $dataCol->count();

//キューを使ってfriendsを幅優先で取得
for (; $i < MAX; $i++) { 

	// API制限かかっているかの確認。かかっていたら5分待つ
	$limitation = $twitter->getFrendIdsAPILimit();
	while($limitation == 0){
		echo "sleeping...", "\n";
		sleep(300);
		$limitation = $twitter->getFrendIdsAPILimit();
	}

	// キューから取り出してきて、鍵アカなら取得できないのでとばす
	$userId = $queue->dequeue()["user_id"];
	$screenName = $twitter->getScreenNameFromUserId($userId);
	if($twitter->isProtected($userId)) {
		$i--;
		echo $msg = "skipped: id = {$userId},\tscreen_name = {$screenName}\n";
		file_put_contents($logFile, $msg,  FILE_APPEND);
		continue;
	}

	//各種データを取得、オブジェクトのメンバに代入
	$data = new stdClass();
	$data->user_id = $userId;
	$data->screen_name = $screenName;
	$data->friends = $twitter->getFriendIds($userId);
	$dataCol->insert($data);

	// まだ取得してないアカウントのidのみキューに入れる
	foreach ($data->friends as $key => $waitingUserId) {
		$enqueued = $dataCol->count(array('user_id' => $waitingUserId));
		if($enqueued == 0){
			$queue->enqueue(array("user_id" => $waitingUserId));
		}
	}

	echo $msg = "inserted: {$i}. id = {$userId},\tscreen_name = {$screenName}\n";
	file_put_contents($logFile, $msg,  FILE_APPEND);

	$data = null;
	sleep(1);
}