<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 20/03/18
 * Time: 12:49
 */

class restEndpointApi
{
    public function restEndpointsURLs(){
        register_rest_route('blogging/v1', '/preview/', [
            'methods' => 'GET',
            'callback' => [$this, 'generatePreviewUrl'],
        ]);

        register_rest_route('blogging/v1', '/seo_meta/', [
            'methods' => 'POST',
            'callback' => [$this, 'updateSEOMetasByPlugin'],
        ]);
    }


    private function transformMetaByWpSeoPlugin($name) {
        $pluginList = get_option( 'active_plugins' );

        $seoPlugins = [
            'Yoast' => [
                'dir' => 'wordpress-seo/wp-seo.php',
                'seo_title' => '_yoast_wpseo_title',
                'seo_description' => '_yoast_wpseo_metadesc'
                ],
            'AllInOne' => [
                'dir' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
                'seo_title' => '_aioseop_title',
                'seo_description' => '_aioseop_description'
                ]
        ];


        foreach($seoPlugins as $key => $seoPlugin ) {
            if ( in_array( $seoPlugin['dir'] , $pluginList ) ) {
                switch($name){
                    case 'seo_title':
                        return $seoPlugin['seo_title'];
                    case 'seo_description':
                        return $seoPlugin['seo_description'];
                }
            }
        }
    }


    public function updateSEOMetasByPlugin($request) {
        $params = $request->get_params();

        foreach($params['meta'] as $key => $value) {
            $meta_key = $this->transformMetaByWpSeoPlugin($key);

            if($meta_key) {
                update_post_meta($params['id'], $meta_key, $value);
            }
        }

        return ['success' => true];
    }

    public function generatePreviewUrl($params) {
        update_option('public_post_preview', [$params->get_param('id')]);

        return ['url' => draftPostPreview::get_preview_link(get_post($params->get_param('id')))];
    }
}