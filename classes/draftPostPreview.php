<?php

class DS_Public_Post_Preview {

	public static function init() {
		add_action( 'transition_post_status', array( __CLASS__, 'unregister_public_preview_on_status_change' ), 20, 3 );

		if ( ! is_admin() ) {
			add_filter( 'pre_get_posts', array( __CLASS__, 'show_public_preview' ) );
			add_filter( 'query_vars', array( __CLASS__, 'add_query_var' ) );
		}
	}

	private static function get_published_statuses() {
		$published_statuses = array( 'publish', 'private' );

		return apply_filters( 'ppp_published_statuses', $published_statuses );
	}

	public static function get_preview_link( $post ) {
		if ( 'page' == $post->post_type ) {
			$args = array(
				'page_id'    => $post->ID,
			);
		} else if ( 'post' == $post->post_type ) {
			$args = array(
				'p'          => $post->ID,
			);
		} else {
			$args = array(
				'p'          => $post->ID,
				'post_type'  => $post->post_type,
			);
		}

		$args['preview'] = true;
		$args['_ppp'] = self::create_nonce( 'public_post_preview_' . $post->ID );



		$link = add_query_arg( $args, home_url( '/' ) );
		return apply_filters( 'ppp_preview_link', $link,  $post->ID, $post );
	}


	public static function unregister_public_preview_on_status_change( $new_status, $old_status, $post ) {
		$disallowed_status = self::get_published_statuses();
		$disallowed_status[] = 'trash';

		if ( in_array( $new_status, $disallowed_status ) ) {
			return self::unregister_public_preview( $post->ID );
		}

		return false;
	}

	public static function unregister_public_preview_on_edit( $post_id, $post ) {
		$disallowed_status = self::get_published_statuses();
		$disallowed_status[] = 'trash';

		if ( in_array( $post->post_status, $disallowed_status ) ) {
			return self::unregister_public_preview( $post_id );
		}

		return false;
	}

	private static function unregister_public_preview( $post_id ) {
		$preview_post_ids = self::get_preview_post_ids();

		if ( ! in_array( $post_id, $preview_post_ids ) ) {
			return false;
		}

		$preview_post_ids = array_diff( $preview_post_ids, (array) $post_id );

		return self::set_preview_post_ids( $preview_post_ids );
	}


	public static function add_query_var( $qv ) {
		$qv[] = '_ppp';

		return $qv;
	}

	public static function show_public_preview( $query ) {
		if (
			$query->is_main_query() &&
			$query->is_preview() &&
			$query->is_singular() &&
			$query->get( '_ppp' )
		) {
			if ( ! headers_sent() ) {
				nocache_headers();
			}

			add_filter( 'posts_results', array( __CLASS__, 'set_post_to_publish' ), 10, 2 );
		}

		return $query;
	}

	private static function is_public_preview_available( $post_id ) {
		if ( empty( $post_id ) ) {
			return false;
		}

		if ( ! self::verify_nonce( get_query_var( '_ppp' ), 'public_post_preview_' . $post_id ) ) {
			wp_die( __( 'The link has been expired!', 'public-post-preview' ) );
		}

		if ( ! in_array( $post_id, self::get_preview_post_ids() ) ) {
			wp_die( __( 'No Public Preview available!', 'public-post-preview' ) );
		}

		return true;
	}

	public static function filter_wp_link_pages_link( $link, $page_number ) {
		$post = get_post();
		if ( ! $post ) {
			return $link;
		}

		$preview_link = self::get_preview_link( $post );
		$preview_link = add_query_arg( 'page', $page_number, $preview_link );

		return preg_replace( '~href=(["|\'])(.+?)\1~', 'href=$1' . $preview_link . '$1', $link );
	}

	public static function set_post_to_publish( $posts ) {
		// Remove the filter again, otherwise it will be applied to other queries too.
		remove_filter( 'posts_results', array( __CLASS__, 'set_post_to_publish' ), 10 );

		if ( empty( $posts ) ) {
			return;
		}

		$post_id = $posts[0]->ID;

		// If the post has gone live, redirect to it's proper permalink.
		self::maybe_redirect_to_published_post( $post_id );

		if ( self::is_public_preview_available( $post_id ) ) {
			// Set post status to publish so that it's visible.
			$posts[0]->post_status = 'publish';

			// Disable comments and pings for this post.
			add_filter( 'comments_open', '__return_false' );
			add_filter( 'pings_open', '__return_false' );
			add_filter( 'wp_link_pages_link', array( __CLASS__, 'filter_wp_link_pages_link' ), 10, 2 );
		}

		return $posts;
	}

	private static function maybe_redirect_to_published_post( $post_id ) {
		if ( ! in_array( get_post_status( $post_id ), self::get_published_statuses() ) ) {
			return false;
		}

		wp_redirect( get_permalink( $post_id ), 301 );
		exit;
	}

	private static function nonce_tick() {
		$nonce_life = apply_filters( 'ppp_nonce_life', 60 * 60 * 48 ); // 48 hours

		return ceil( time() / ( $nonce_life / 2 ) );
	}

	private static function create_nonce( $action = -1 ) {
		$i = self::nonce_tick();

		return substr( wp_hash( $i . $action, 'nonce' ), -12, 10 );
	}

	private static function verify_nonce( $nonce, $action = -1 ) {
		$i = self::nonce_tick();

		// Nonce generated 0-12 hours ago.
		if ( substr( wp_hash( $i . $action, 'nonce' ), -12, 10 ) == $nonce ) {
			return 1;
		}

		// Nonce generated 12-24 hours ago.
		if ( substr( wp_hash( ( $i - 1 ) . $action, 'nonce' ), -12, 10 ) == $nonce ) {
			return 2;
		}

		// Invalid nonce.
		return false;
	}

	private static function get_preview_post_ids() {
		return get_option( 'public_post_preview', array() );
	}

	private static function set_preview_post_ids( $post_ids = array() ) {
		return update_option( 'public_post_preview', $post_ids );
	}
}

add_action( 'plugins_loaded', array( 'DS_Public_Post_Preview', 'init' ) );
