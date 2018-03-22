<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 19/03/18
 * Time: 15:31
 */

/*
Plugin Name: OptimizaBlogging
Plugin URI: https://www.optimizaclick.com/
Description: Include gandalfJS script to tracking analytics events and allow protected meta
Author: David
Version: 1.0
Author URI: https://www.optimizaclick.com/
*/


if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('optimizaBlogging')) {
    class optimizaBlogging
    {

        private $preview;

        public function __construct()
        {
            spl_autoload_register([$this, 'autoload']);
            add_action( 'wp_head', [$this,'includeGandalf' ]);
            add_action( 'is_protected_meta', [$this, 'allowMetaFieldsInPostRequest'], 10, 2);

            add_action( 'rest_api_init', [new restPostPreview(),'getPreviewURL' ]);
        }

        public function autoload()
        {
            $classes = glob(plugin_dir_path(__FILE__) . 'classes' . '/*.*');
            foreach ($classes as $class) {
                if (file_exists($class)) {
                    require_once $class;
                }
            }
        }

        public function includeGandalf() {
            wp_enqueue_script('GandalfJS', 'https://s3-eu-west-1.amazonaws.com/gandalf-optimiza/gandalf.js', [], '1.0.0', false);
        }

        public function allowMetaFieldsInPostRequest($protected, $meta_key) {
            if ( '_yoast_wpseo_title' == $meta_key || '_yoast_wpseo_metadesc' == $meta_key && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                $protected = false;
            }
        }
    }
    new optimizaBlogging();
}