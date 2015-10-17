<?php

/**
* mongoDBを使ったキュー
*/
class mongoQueue
{
	private $col;

	function __construct($dbname, $colname)
	{
		$mongo = new Mongo();
		$db = $mongo->selectDB($dbname);
		$this->col = $db->selectCollection($colname);
	}

	public function enqueue($data)
	{
		return $this->col->insert($data);
	}

	public function dequeue()
	{
		if($this->is_empty() == false){
			$cur = $this->col->find()->sort(array("_id" => 1))->limit(1);
			$arr = iterator_to_array($cur);
			$id = array_keys($arr)[0];
			$_id = new MongoId($id);
			$this->col->remove(array("_id" => $_id));

			return $arr[$id];
		} else {
			return null;
		}
	}

	public function is_empty()
	{
		return $this->col->count() == 0;
	}

	public function isEnqueued($param)
	{

		return $this->col->count($param) == 0 ? false : true;
	}

}
