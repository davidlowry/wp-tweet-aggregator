<?php

class Fs {

	var $uploads, $fs, $creds;
	
	// To be used by plugins, identifier creates a folder
	// TODO: check mkdir works as expected on Windoze
	// TODO: check if there's a more efficient way to check folder exists
	function __construct($plugin_identifier){
		$this->uploads = wp_upload_dir();
		$this->uploads['plugin_data_url'] = $this->uploads['basedir'] . '/plugin_data/'.$plugin_identifier.'/';

		WP_Filesystem();
		global $wp_filesystem;
		$this->fs = $wp_filesystem;
		
		if ( wp_mkdir_p( $this->uploads['plugin_data_url'] ) === TRUE ){
			// carry on
		}
	}
	
	// Return the age in seconds
	// TODO: check time() works as expected on Windoze
	function get_file_age($filename){
		return time() - $this->fs->mtime( $this->uploads['plugin_data_url'] . $filename);
	}
	
	// Return contents of file
	function get_static_file($filename){
		return $this->fs->get_contents( $this->uploads['plugin_data_url'] . $filename);
	}
	
	// Create file with given contents
	function generate_static_file($filename, $newdata) {

		/** Define some vars **/
		$data = $newdata; 

		/** Write to file **/
		return $this->fs->put_contents( $this->uploads['plugin_data_url'] . $filename, $data, 0644);

	}	
	
	function trash_static_file($filename) {
		return $this->fs->delete( $this->uploads['plugin_data_url'] . $filename );
	}
}


?>