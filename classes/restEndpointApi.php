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

    public function updateSEOMetasByPlugin($request) {
        $params = $request->get_params();

        foreach($params['meta'] as $key => $value) {
            update_post_meta($params['id'], $key, $value);
        }

        return ['success' => true];
    }

    public function generatePreviewUrl($params) {
        update_option('public_post_preview', [$params->get_param('id')]);

        return ['url' => draftPostPreview::get_preview_link(get_post($params->get_param('id')))];
    }
}