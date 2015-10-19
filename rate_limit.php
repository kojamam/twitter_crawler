<?php
require_once 'TwistOAuth.phar';
require_once 'config.php';

$twitter = new TwistOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

$limit = $twitter->get('application/rate_limit_status');

$friends = $limit->resources->friends;
$users = $limit->resources->users;

echo "/friends/ids : ", $friends->{"/friends/ids"}->limit, "\n";
echo "/users/show : ", $users->{"/users/show/:id"}->limit, "\n";
