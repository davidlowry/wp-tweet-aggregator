<?php 
 // ------------------------------------------------------------------
 // Add all your sections, fields and settings during admin_init
 // ------------------------------------------------------------------
 //
session_start();

include_once(plugin_dir_path( __FILE__ ) . '/twitteroauth/twitteroauth/twitteroauth.php');
include_once(plugin_dir_path( __FILE__ ) . '/_twitter_config.php');

define('CONSUMER_KEY', get_option('twitter_keys_consumer_key'));
define('CONSUMER_SECRET', get_option('twitter_keys_access_token'));

add_action('admin_menu', 'dl_ta_settings_init');

function dl_ta_settings_init() {

	// Add the section to reading settings so we can add our fields to it
	add_menu_page( 'DL Twitter Aggregator', 'David\'s Twitter Aggregator', 'administrator', 'dlta_settings', 'dlta_settings_page', plugins_url('dl-twitter-aggregator') . '/includes/dlta_icon.png' );
	add_action('admin_init', 'register_dlta_settings');
}

function setup_twitter_usernames_field(){
	$twitter_usernames = get_option('twitter_usernames');
	
	// unset
	if (!$twitter_usernames || empty($twitter_usernames)){
		update_option('twitter_usernames', array());
	}
}

function reset_cache(){
	global $dlta_object;		
	$dlta_object->trash_static_file();
}

function register_dlta_settings() {

// MAGIC KEYS
	register_setting( 'dlta_settings_group', 'twitter_keys_consumer_key', 'twitter_keys_consumer_key_validation' );
	register_setting( 'dlta_settings_group', 'twitter_keys_access_token', 'twitter_keys_access_token_validation' );
	register_setting( 'dlta_settings_group_usernames', 'twitter_username_add', 'twitter_username_add_validation' );
	
	setup_twitter_usernames_field();
	
	if(isset($_GET['remove_user'])){
		$user_id = $_GET['remove_user'];
		
		$existing_users = get_option('twitter_usernames');
		if(isset($existing_users[$user_id])){
			unset($existing_users[$user_id]);
			update_option('twitter_usernames', $existing_users);
			
			reset_cache();
			
			wp_redirect(admin_url('admin.php?page=dlta_settings'));
		}
	}
	
	// Reset of twitter stuff has been received 
	if(isset($_GET['reset'])){
		update_option('twitter_keys_consumer_key','');
		update_option('twitter_keys_access_token','');
		update_option('twitter_keys_oauth_token','');
		update_option('twitter_keys_oauth_token_secret','');
		update_option('twitter_keys_screen_name','');
		update_option('twitter_username_add','');
		update_option('twitter_usernames','');
		
		reset_cache();
		
		wp_redirect(admin_url('admin.php?page=dlta_settings'));
	}
	
	// Twitter username has been input, do the magic and reload page if she works
	$existing_users = get_option('twitter_usernames', array('something'));
	$new_username_input_has_been_set = get_option('twitter_username_add', 'not_set');
	
	if(!empty($new_username_input_has_been_set) && $new_username_input_has_been_set != 'not_set'){
		
		$newinput = explode(',', $new_username_input_has_been_set);
		
		foreach($newinput as $username){
			
			// clean me
			$username = trim($username);
			
			// if this isn't a clean string... jump out
			if (generic_validate_length($username) != $username){
				continue;
			} 
			
			// attempt to get id from twitterz
			$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, get_option('twitter_keys_oauth_token'), get_option('twitter_keys_oauth_token_secret'));
			$response = $connection->get('users/show', array('screen_name' => $username));

			$existing_users[$response->id_str] = $response->screen_name;
		}
		
		// update database
		update_option('twitter_usernames', $existing_users);
		update_option('twitter_username_add', '');
		
		reset_cache();
		
		wp_redirect(admin_url('admin.php?page=dlta_settings'));
	}
	
	// Twitter keys have been entered, validate user details now
	$keys_are_set = defined('CONSUMER_KEY') && defined('CONSUMER_SECRET');
	if($keys_are_set) {
		$key = CONSUMER_KEY;
		$secret = CONSUMER_SECRET;
		$keys_are_set = !empty($key) && !empty($secret);
	}
	
	// Twitter secrets have been retuned and stuck in the right place
	$secrets_are_set = isset($_SESSION['oauth_token']) && isset($_SESSION['oauth_token_secret']);
	if($secrets_are_set) {
		$token = $_SESSION['oauth_token']; //get_option('twitter_keys_oauth_token_secret'); //
		$secret = $_SESSION['oauth_token_secret']; //get_option('twitter_keys_oauth_token_secret'); //
		$secrets_are_set = !empty($token) && !empty($secret);
	}
	
	// Twitter secrets and everything have been SAVED and are available for use
	$secrets_are_stored = false;
	$oauth_token = get_option('twitter_keys_oauth_token');
	$oauth_secret = get_option('twitter_keys_oauth_token_secret');
	$secrets_are_stored = !empty($oauth_token) && !empty($oauth_secret);
	
	if ($secrets_are_stored){

		// Secrets are stored, all good
		
		// show twitter placeholder text, allowing logout
		add_settings_section('dlta_settings_section_keys_set', 'Twitter Connection', 'dlta_settings_section_keys_set_text', 'dlta_settings_users');
		
		// request username input, display a username list and input please
		add_settings_section('dlta_settings_section_tweeters', 'Twitter Users', 'dlta_settings_section_tweeters_text', 'dlta_settings_users');
		add_settings_field('twitter_username_add', 'Twitter Usernames', 'twitter_username_add_input', 'dlta_settings_users', 'dlta_settings_section_tweeters');


	} else {

		// Secrets are inbound, do it
		if ($secrets_are_set){
			
			// Twitter response has been received, we are trying now to verify integity of response
			if($secrets_are_set && isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])){
				//die(print_r($_SESSION));
				$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
				$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
				//die(print_r($access_token));
				update_option('twitter_keys_oauth_token', $access_token['oauth_token']);
				update_option('twitter_keys_oauth_token_secret', $access_token['oauth_token_secret']);
				update_option('twitter_keys_screen_name', $access_token['screen_name']);
				
				// add this user to the list
				update_option('twitter_username_add', $access_token['screen_name']);
				
				// clear session stuff so that secrets dont look like they're working
				unset($_SESSION['oauth_token']);
				unset($_SESSION['oauth_token_secret']);
				
				header('Location: '.OAUTH_CALLBACK); // just clear up page please
			}
	
		} else if ($keys_are_set){
			
			// Keys are set, secrets have not been done yet, so...
			// if KEYS are set but SECRETS are not, and we've just updated the form, go to twitter + validate
			if(isset($_GET['settings-updated']) && $keys_are_set && !$secrets_are_set){
				
				// start twitter connection
				$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
				$connection->host = "https://api.twitter.com/1.1/";
				
				/* Get temporary credentials. */
				$temporary_credentials = $connection->getRequestToken(OAUTH_CALLBACK); //plugins_url('oauth_return.php', __FILE__)); //plugins_url('oauth_return.php', __FILE__)); //
				$redirect_url = $connection->getAuthorizeURL($temporary_credentials, FALSE);
				$_SESSION['oauth_token'] = $temporary_credentials['oauth_token'];
				$_SESSION['oauth_token_secret'] = $temporary_credentials['oauth_token_secret'];
				
				header('Location: '.$redirect_url);
			}
		} else {
			
			// Nothing's set, ask for tweet stuff
			add_settings_section('dlta_settings_section_keys', 'Twitter API Keys', 'dlta_settings_section_keys_text', 'dlta_settings');
			
			add_settings_field('twitter_keys_consumer_key', 'Twitter Consumer Key', 'twitter_keys_consumer_key_input', 'dlta_settings', 'dlta_settings_section_keys');
			add_settings_field('twitter_keys_access_token', 'Twitter Access token', 'twitter_keys_access_token_input', 'dlta_settings', 'dlta_settings_section_keys');
			
		}
	}


}




function generic_input($option_name){
	$options = get_option($option_name);
	if($option_name == 'twitter_usernames_input'){
		$option_name = 'twitter_usernames_input[next_input]';
		$option_id = 'twitter_usernames_input_next_input';
	} else {
		$option_id = $option_name;
	}
	echo "<input id='$option_id' name='".$option_name."' size='40' type='text' value='{$options}' />";
}

function twitter_username_add_input()      { generic_input('twitter_username_add'); }
function twitter_keys_consumer_key_input() { generic_input('twitter_keys_consumer_key'); }
function twitter_keys_access_token_input() { generic_input('twitter_keys_access_token'); }

function dlta_settings_section_keys_text(){
	echo '<p>Enter your twitter API keys. If you don&rsquo;t know these, you can <a href="https://dev.twitter.com/apps">create a new &rsquo;application&lsquo; here</a> - make sure you set the callback URL to be <input type="text" value="'.admin_url('admin.php?page=dlta_settings').'" /></p>';
}
function dlta_settings_section_keys_set_text(){
	echo '<p>Hi <strong>'.get_option('twitter_keys_screen_name').'</strong></p><p>Your twitter details have been stored. Do you wish to <a class="dlta_settings_reset_twitter_auth" href="'.admin_url('admin.php?page=dlta_settings&reset=1').'">reset and/or reconnect?</a></p>';
	echo '<script>jQuery("a.dlta_settings_reset_twitter_auth").on("click", function(e) {
		
		return confirm ("Are you sure you wish to disconnect and reset all twitter settings?");
		
	});</script>';
}
function dlta_settings_section_tweeters_text(){
	$twitter_usernames = get_option('twitter_usernames');
	
	if (!empty($twitter_usernames)){
		foreach($twitter_usernames as $id=>$un){
			if (empty($un)) continue;
			echo '<li>'.$un.' (<a href="'.admin_url('admin.php?page=dlta_settings&remove_user='.$id).'" class="remove_user_by_id">remove user</a>)';
		}
		echo '<script>jQuery("a.remove_user_by_id").on("click", function(e) {
			return confirm ("Are you sure you wish to remove this user?")
		});</script>';
	}
	echo '<p>Input the exact screen names of the tweeters you wish to display, separate them with commas!</p>';
}
function generic_validate_length($input, $length=false){
	// do validation 
	$newinput = trim($input);
// /^[a-z0-9]{'.$length.'}$/i x characters long please

// has no whitespace. 
// TODO be more fastidious .
	if(preg_match('/\s/', $newinput)) { 
		$newinput = '';
	}
	
	return $newinput;
}
function twitter_keys_consumer_key_validation($input){ 
	return generic_validate_length($input, 20);
}
function twitter_keys_access_token_validation($input){ 
	return generic_validate_length($input, 38);
}
function twitter_username_add_validation($input){
	
	$ret = array();
	$newinput = explode(',', $input);
	foreach($newinput as $ni){
		$ret[] = $ni;
	}
	return implode(',', $ret);
}

function dlta_settings_page() {
	
	// TODO: this is duplicate code from above (4 lines)
	$secrets_are_stored = false;
	$oauth_token = get_option('twitter_keys_oauth_token');
	$oauth_secret = get_option('twitter_keys_oauth_token_secret');
	$secrets_are_stored = !empty($oauth_token) && !empty($oauth_secret);
	
	
?>
<div class="wrap">
	<h2>David&rsquo;s Twitter Aggregator Settings Page</h2>
	<?php
		if(!$secrets_are_stored){
	?>
	<form method="post" action="options.php">
		<?php settings_fields( 'dlta_settings_group' );?>
		<?php do_settings_sections ('dlta_settings'); ?>
		<?php submit_button('Save + authenticate with twitter'); ?>
	</form>
	<?php
		}
	?>
	<?php
		if($secrets_are_stored){
	?>
	<form method="post" action="options.php">
		<?php settings_fields( 'dlta_settings_group_usernames' );?>
		<?php do_settings_sections ('dlta_settings_users'); ?>
		<?php submit_button('Add twitter user'); ?>
	</form>
	<?php
		}
	?>
</div>
<?php
}

?>