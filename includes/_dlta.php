<?php

if ( ! class_exists( 'DL_TA' ) ) {

	class DL_TA {

		/**
		* WordPress requires an action name for each AJAX request
		*
		* @var string $action
		*/
		private $action = 'aggregator-request';
		private $fs;
		
		function __construct() {
			
			// Add our javascript file that will initiate our AJAX requests
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_widget_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_page_scripts' ) );
			
			wp_register_sidebar_widget(
			    'widget_DL_TA_1',        // your unique widget id
			    'Twitter Aggregator (Basic)',          // widget name
			    array($this, 'widget_DL_TA_basic'),  // callback function
			    array(                  // options
			        'description' => 'A basic tweet aggregator, outputting the DL Twitter Aggregator content'
			    )
			);
			
			// Let's make sure we are actually doing AJAX first
			if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

				// Add our callbacks for AJAX requests
				add_action( 'wp_ajax_' . $this->action, array( $this, 'do_ajax' ) ); // For logged in users
				add_action( 'wp_ajax_nopriv_' . $this->action, array( $this, 'do_ajax' ) ); // For logged out users
			}
			
			// Shortcode(s)
			add_shortcode( 'twitter_stream', array($this,'twitter_stream_func') );
		}
		
		function widget_DL_TA_basic($args) {
		    extract($args);
		?>
			<?php echo $before_widget; ?>
	            <?php echo $before_title
	                . 'I&rsquo;m testing something'
	                . $after_title; ?>
				<!--<button class="DL_TA"><? _e('Click me!', 'DL_TA'); ?></button>-->
				<div class="twitter-timeline"></div>
	        <?php echo $after_widget; ?>
		<?php
		}
		
		function wp_enqueue_widget_scripts() {
			wp_enqueue_script( 'dlta', plugins_url('includes/ajax.js', __FILE__), array('jquery') );
			
            // Pass a collection of variables to our JavaScript
			wp_localize_script( 'dlta', 'dlTA', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'action' => $this->action,
				'nonce' => wp_create_nonce( $this->action ),
			) );
			
			wp_register_style( 'dlta-widget-style', plugins_url('includes/DL_TA.sidebar.css', __FILE__) );
			wp_enqueue_style( 'dlta-widget-style' );
		}
		
		function wp_enqueue_page_scripts() {
			wp_enqueue_script( 'dlta', plugins_url('includes/ajax.js', __FILE__), array('jquery') );
			
            // Pass a collection of variables to our JavaScript
			wp_localize_script( 'dlta', 'dlTA', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'action' => $this->action,
				'nonce' => wp_create_nonce( $this->action ),
			) );
			
			wp_register_style( 'dlta-stream-style', plugins_url('includes/DL_TA.stream.css', __FILE__) );
			wp_enqueue_style( 'dlta-stream-style' );
		}
		
		function get_data(){
			
			// By default, let's start with an error message
			$response = array(
				'status' => 'error',
				'message' => 'Invalid verification data',
			);
			
			$this->fs = new Fs('DL_TA');
			
			// Next, check to see if the nonce is valid
			if( isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], $this->action ) ){
				
				// Update our message / status since our request was successfully processed
				$response['status'] = 'success';
				$response['message'] = "Ajax test works!";
				
				$existing = $this->fs->get_static_file(C_DL_TA_CACHE_FILE_NAME);
				$age = $this->fs->get_file_age(C_DL_TA_CACHE_FILE_NAME);
				$old = (60 * 5) < $age; // 5 minutes?
				
				$generate_new = is_null($existing) || !$existing || $old;
				
				if($generate_new){
					
					include_once(plugin_dir_path( __FILE__ ) . 'includes/twitteroauth/twitteroauth/twitteroauth.php');
					
					// start twitter connection
					$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
					
					$content_of_interest = array('users'=>array(), 'stream'=>array());
					foreach(get_option('twitter_usernames', array()) as $user_id => $meta){
						$content_of_interest['users'][$user_id]['meta'] = $meta;
						$content_of_interest['users'][$user_id]['tweets'] = $connection->get('/statuses/user_timeline', array('user_id' => $user_id));
					}

					$filtered_content = array();
					foreach($content_of_interest['users'] as $user_id => $data){
						foreach($data['tweets'] as $tweet){
							
							// create an orderable integer
							$i = strtotime($tweet->created_at);
							
							// create a js friendly timestamp [being kind]
							$tweet->js_timestamp = (string) strtotime($tweet->created_at) + "000";
							
							if(empty($tweet->in_reply_to_screen_name)){
								$filtered_content['stream'][$i] = $tweet;
							}
						}
					}
					
					ksort($filtered_content['stream']);
					$filtered_content['stream'] = array_reverse($filtered_content['stream']);
					// die(print_r($filtered_content['stream']));
					$json_content = json_encode($filtered_content['stream']);
										
					if (!$this->fs->generate_static_file(C_DL_TA_CACHE_FILE_NAME, $json_content)){
						$response = array(
							'status' => 'error',
							'message' => 'Could not set data'
						);
					};
					
					if ($freshly_cached = $this->fs->get_static_file(C_DL_TA_CACHE_FILE_NAME)){
						$response['message'] = "Tmp output file exists";
						$response['data'] = $freshly_cached;
					}
					
				} else {
					$response['message'] = "Cached data sent";
					$response['data'] = $existing;
				}
				
				// $response['message'] .= $age;
			}
			
			return $response;
		}
		
		function do_ajax(){
			
			$response = $this->get_data();
			
			// Return our response to the script in JSON format
			header( 'Content: application/json' );
			echo json_encode( $response );
			die;
		}
		
		function trash_static_file(){
			if(!$this->fs) $this->fs = new Fs('DL_TA');
			$this->fs->trash_static_file(C_DL_TA_CACHE_FILE_NAME);
		}
		
		/****************************************
		*** SHORT TAG HANDLER
		****************************************/
		
		// Size parameter irrelevant at the moment, but it does insert a css class name
		// uses same ajax method as widget
		// [twitter_stream size="big"]
		function twitter_stream_func( $atts ) {
			extract( shortcode_atts( array(
				'size' => 'big'
			), $atts ) );
			
			
			return "<div class=\"twitter-timeline {$size}\">Loading...</div>";
		}
	}
	
	
	$dlta_object = new DL_TA();
}

?>