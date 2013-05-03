<?php

// make these top two defines and the users of interest var be stored in special system thing
// define("CONSUMER_KEY",    'jeJ6J48qxkse9XBd39bvHA'); //1QDjvQE60yrLsk0eaNTg');
// define("CONSUMER_SECRET", '4bdKmZmD9z8BHJ05K9POnVEyDAUJ0JwVPtNkzlleqtQ'); //B98lfrSIfshvnJz10szcwaUQjFNOp8fh8YqwxI');
define('OAUTH_CALLBACK', admin_url('admin.php?page=dlta_settings'));

// using https://api.twitter.com/1/users/show.xml?screen_name=ukhockeyvideo for data (may be deprecated)
// $users_of_interest = array(
// 	'8320952' => array('username'=>'djlowry'),
// 	'71835693' => array('username'=>'giantslivetv'),
// 	'1125787242' => array('username'=>'ukhockeyvideo')
// );

define("TMP_LOCATION", plugins_url('tmp/', __FILE__));
define("TMP_OUTPUT_FILE", 'tmp-twitter-feed.json');
define("TMP_OUTPUT_MARKER", 'tmp-twitter-feed-marker.txt');
?>