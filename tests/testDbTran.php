<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/models/VoteQuestion.php';
$userInfo['username'] = 'a';
$userInfo['avatar'] = 'a';
apps\models\VoteQuestion::vote(54, 3, 1, [126], $userInfo);

