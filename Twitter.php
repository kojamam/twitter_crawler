<?php

require_once 'TwistOAuth.phar';

/**
* Twitter API使うクラス
*/
class Twitter
{
	private $con;
	
	function __construct($ck, $cs, $at, $ats)
	{
		//Twitter OAuth認証
		try {
			$this->con = new TwistOAuth($ck, $cs, $at, $ats);
		} catch(TwistException $e) {
			echo  $e->getMessage(), "\n";
		}
	}

	public function getUserIdFromScreenName($screenName)
	{
		try{
			$user = $this->con->get('users/show', array('screen_name' => $screenName));
			$userId = $user->id;
		}catch(TwistException $e){
			echo $e->getMessage(), "\n";
		}

		return $userId;
	}

	public function getFriendIds($userId)
	{
		try{
			$friends = $this->con->get('friends/ids', array("user_id" => $userId));
		}catch(TwistException $e){
			echo $userId;
			echo $e->getMessage(), "\n";
		}

		return $friends->ids;
	}


	public function getFrendIdsAPILimit(){
		return $this->con
				->get('application/rate_limit_status', array('resources' => 'friends'))	
				->resources
				->friends
				->{'/friends/ids'}
				->remaining;
	}

	public function isProtected($userId)
	{
		try{
			$user = $this->con->get('users/show', array('user_id' => $userId));
			$ret = $user->protected;
		}catch(TwistException $e){
			echo $e->getMessage(), "\n";
		}

		return $ret;
	}
}