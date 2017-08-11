<?php
/*
Plugin Name: bg-import
Version: 0.3
Plugin URI: http://kaloyan.info/blog/bg-import
Description: &#1044;&#1086;&#1073;&#1103;&#1074;&#1072; &#1074;&#1098;&#1079;&#1084;&#1086;&#1078;&#1085;&#1086;&#1089;&#1090; &#1079;&#1072; &#1080;&#1084;&#1087;&#1086;&#1088;&#1090; &#1086;&#1090; &#1087;&#1086;&#1087;&#1091;&#1083;&#1103;&#1088;&#1085;&#1080; &#1073;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080; &#1073;&#1083;&#1086;&#1075;-&#1093;&#1086;&#1089;&#1090;&#1080;&#1085;&#1075; &#1091;&#1089;&#1083;&#1091;&#1075;&#1080;. &#1047;&#1072; &#1076;&#1072; &#1080;&#1079;&#1074;&#1098;&#1088;&#1096;&#1080;&#1090;&#1077; &#1090;&#1072;&#1082;&#1098;&#1074; &#1080;&#1084;&#1087;&#1086;&#1088;&#1090; &#1085;&#1072;&#1090;&#1080;&#1089;&#1085;&#1077;&#1090;&#1077; <a href="import.php">&#1090;&#1091;&#1082;</a>. |  
Author: Kaloyan K. Tsvetkov
Author URI: http://kaloyan.info/
*/

/////////////////////////////////////////////////////////////////////////////

/**
* @internal prevent from direct calls
*/
if (!defined('ABSPATH')) {
	return ;
	}

/**
* The directory path to the plugin
*/
define('WP_BG_IMPORT_DIR', dirname( __FILE__ ));

/**
* @internal prevent from second inclusion
*/
if (!isset($wp_bg_import)) {

	/**
	* Initiating the plugin...
	* @see wp_bg_import
	*/
	$wp_bg_import = new wp_bg_import;
	}

/////////////////////////////////////////////////////////////////////////////

/**
* BG Import (blog import)
*
* @author Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/
Class wp_bg_import {

	/**
	* Constructor
	*/
	Function wp_bg_import() {

		// this is the admin
		//
		if (is_admin()) {
			add_action('admin_init',
				array(&$this, 'admin_init'));
			}
		}

	/**
	* Introduce the import classes
	*/
	Function admin_init() {

		register_importer(
			'bg_import_blog_bg',
			'bg.import | Blog.bg &#1080;&#1084;&#1087;&#1086;&#1088;&#1090;',
			'&#1048;&#1084;&#1087;&#1086;&#1088;&#1090; &#1085;&#1072;
				&#1073;&#1083;&#1086;&#1075; &#1086;&#1090; Blog.bg.',
			array ($this, 'import_blog_bg')
			);
		register_importer(
			'bg_import_log_bg',
			'bg.import | Log.bg &#1080;&#1084;&#1087;&#1086;&#1088;&#1090;',
			'&#1048;&#1084;&#1087;&#1086;&#1088;&#1090; &#1085;&#1072;
				&#1073;&#1083;&#1086;&#1075; &#1086;&#1090; Log.bg.',
			array ($this, 'import_log_bg')
			);
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
	
	/**
	* Ignite the Blog.bg import
	*/
	Function import_blog_bg() {
		require_once(
			WP_BG_IMPORT_DIR . '/import/blog_bg.php'
			);
		}

	/**
	* Ignite the Log.bg import
	*/
	Function import_log_bg() {
		require_once(
			WP_BG_IMPORT_DIR . '/import/log_bg.php'
			);
		}
	
	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* Get the version of the plugin
	* @access public
	*/
	Function version() {
		if (preg_match('~Version\:\s*(.*)\s*~i',
				file_get_contents(__FILE__), $R)
			) {
			return trim($R[1]);
			}

		return '$Rev: 139106 $';
		}

	//--end-of-class--
	}
	
/////////////////////////////////////////////////////////////////////////////
