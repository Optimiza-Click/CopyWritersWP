<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 20/03/18
 * Time: 12:49
 */

class restPostPreview
{
    public function getPreviewURL(){
        register_rest_route('blogging/v1', '/preview/', [
            'methods' => 'GET',
            'callback' => [$this, 'generatePreviewUrl'],
        ]);
    }

    public function generatePreviewUrl($params) {
        $preview = new DS_Public_Post_Preview();

        update_option('public_post_preview', [$params->get_param('id')]);
        return ['url' => $preview->get_preview_link(get_post($params->get_param('id')))];
    }
}