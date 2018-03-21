<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 20/03/18
 * Time: 12:49
 */

class previewPostsWP
{
    public function getPreviewURL(){
        register_rest_route('blogging/v1', '/preview/', [
            'methods' => 'GET',
            'callback' => [$this, 'generatePreviewUrl'],
        ]);
    }

    public function generatePreviewUrl($params) {
        $id =  $params->get_param('id');

        update_option('public_post_preview', [$id]);

        $preview = new DS_Public_Post_Preview();
        return ['url' => $preview->get_preview_link(get_post($id))];
    }
}