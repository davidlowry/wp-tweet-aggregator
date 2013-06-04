<?php
/*  Copyright 2013 David Lowry (dave@infinity21.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
 * Plugin Name: DL's Twitter Aggregator
 * Description: A plugin to consume multiple (public) twitter feeds and display them on your wordpress website. Requires twitter app authentication credentials - i.e. for developers only at this time.
 * Author: David Lowry
 * Author URI: http://davidlowry.co.uk
 * Plugin URI: http://davidlowry.co.uk/plugins/
 * Author Email: hello@davidlowry.co.uk
 * Version: 0.1
 * License: GPL2
 */

 // outline of js click event + theory behind plugin 
 // basics with thanks to http://micahwood.me/doing-ajax-in-wordpress/

include_once(plugin_dir_path( __FILE__ ) . 'includes/_wp_admin_settings.php');
include_once(plugin_dir_path( __FILE__ ) . 'includes/_twitter_config.php');
include_once(plugin_dir_path( __FILE__ ) . 'includes/_fs.php');
include_once(plugin_dir_path( __FILE__ ) . 'includes/_dlta.php');

define('C_DL_TA_CACHE_FILE_NAME', 'DL_TA_cached_output');

?>