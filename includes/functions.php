<?php
/**
 * Holds miscellaneous functions for use in the WP Listings plugin
 *
 */

add_image_size( 'listings-full', 1060, 9999, false );
add_image_size( 'listings', 560, 380, true );

add_filter( 'template_include', 'wp_listings_template_include' );


function wp_listings_template_include( $template ) {

	$post_type = 'listing';

    if ( wp_listings_is_taxonomy_of($post_type) ) {
        if ( file_exists(get_stylesheet_directory() . '/archive-' . $post_type . '.php' ) ) {
            return get_stylesheet_directory() . '/archive-' . $post_type . '.php';
        } else {
            return dirname( __FILE__ ) . '/views/archive-' . $post_type . '.php';
        }
    }

	if ( is_post_type_archive( $post_type ) ) {
		if ( file_exists(get_stylesheet_directory() . '/archive-' . $post_type . '.php') ) {
			$template = get_stylesheet_directory() . '/archive-' . $post_type . '.php';
			return $template;
		} else {
			return dirname( __FILE__ ) . '/views/archive-' . $post_type . '.php';
		}
	}

	if ( is_single() && $post_type == get_post_type() ) {

		global $post;

		$custom_template = get_post_meta( $post->ID, '_wp_post_template', true );

		/** Prevent directory traversal */
		$custom_template = str_replace( '..', '', $custom_template );

		if( ! $custom_template )
			if( file_exists(get_stylesheet_directory() . '/single-' . $post_type . '.php') )
				return $template;
			else
				return dirname( __FILE__ ) . '/views/single-' . $post_type . '.php';
		else
			if( file_exists( get_stylesheet_directory() . "/{$custom_template}" ) )
				$template = get_stylesheet_directory() . "/{$custom_template}";
			elseif( file_exists( get_template_directory() . "/{$custom_template}" ) )
				$template = get_template_directory() . "/{$custom_template}";

	}

	return $template;
}

/* http://meigwilym.com/fixing-the-wordpress-pagination-404-error/ */
/*
function mg_news_pagination_rewrite() {
  add_rewrite_rule(get_option('listing').'/page/?([0-9]{1,})/?$', 'index.php?pagename='.get_option('listing').'&paged=$matches[1]', 'top');
}
add_action('init', 'mg_news_pagination_rewrite');
*/

/**
 * Controls output of default state for the state custom field if there is one set
 */
function wp_listings_get_state() {

	$options = get_option('plugin_wp_listings_settings');

	global $post;

	$state = get_post_meta($post->ID, '_listing_state', true);

	if (isset($options['wp_listings_default_state'])) {
		$default_state = $options['wp_listings_default_state'];
	}

	if ( empty($default_state) ) {
		$default_state = 'ST';
	}

	if ( empty($state) ) {
		return $default_state;
	}

	return $state;
}

/**
 * Controls output of city name
 */
function wp_listings_get_city() {

	global $post;

	$city = get_post_meta($post->ID, '_listing_city', true);

	if ( '' == $city ) {
		$city = 'Cityname';
	}

	return $city;
}

/**
 * Controls output of address
 */
function wp_listings_get_address($post_id = null) {

	global $post;

	$address = get_post_meta($post->ID, '_listing_address', true);

	if ( '' == $address ) {
		$address = 'Address Unavailable';
	}

	return $address;
}

/**
 * Displays the status (active, pending, sold, for rent) of a listing
 */
function wp_listings_get_status($post_id = null) {

	if ( null == $post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$listing_status = wp_get_object_terms($post_id, 'status');

	if ( empty($listing_status) || is_wp_error($listing_status) ) {
		return;
	}

	foreach($listing_status as $term) {
		if ( $term->name != 'Featured' ) {
			return $term->name;
		}
	}
}

/**
 * Get featured status
 */
function wp_listings_get_featured($post_id = null) {

	if ( null == $post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$listing_status = wp_get_object_terms($post_id, 'status');

	if ( empty($listing_status) || is_wp_error($listing_status) ) {
		return;
	}

	foreach($listing_status as $term) {
		if ( $term->name == 'Featured' ) {
			return true;
		}
	}
}

/**
 * Displays the property type (residential, condo, comemrcial, etc) of a listing
 */
function wp_listings_get_property_types($post_id = null) {

	if ( null == $post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$listing_property_types = wp_get_object_terms($post_id, 'property-types');

	if ( empty($listing_property_types) || is_wp_error($listing_property_types) ) {
		return;
	}

	foreach($listing_property_types as $type) {
		return $type->name;
	}
}

/**
 * Displays the location term of a listing
 */
function wp_listings_get_locations($post_id = null) {

	if ( null == $post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$listing_locations = wp_get_object_terms($post_id, 'locations');

	if ( empty($listing_locations) || is_wp_error($listing_locations) ) {
		return;
	}

	foreach($listing_locations as $location) {
		return $location->name;
	}
}

function wp_listings_post_number( $query ) {

	if ( !$query->is_main_query() || is_admin() || !is_post_type_archive('listing') ) {
		return;
	}

	$options = get_option('plugin_wp_listings_settings');

	$archive_posts_num = $options['wp_listings_archive_posts_num'];

	if ( empty($archive_posts_num) ) {
		$archive_posts_num = '9';
	}

	$query->query_vars['posts_per_page'] = $archive_posts_num;

}
add_action( 'pre_get_posts', 'wp_listings_post_number' );

function wp_listings_domain( $query ) {

	if ( !$query->is_main_query() || is_admin() || !is_post_type_archive('listing') ) {
		return;
	}

	$options = get_option('plugin_wp_listings_settings');
	$domain = $options['wp_listings_domain'];
	$query->query_vars['domain'] = $domain;

}
add_action( 'pre_get_domain', 'wp_listings_domain' );

/**
 * Better Jetpack Related Posts Support for Listings
 */
function wp_listings_jetpack_relatedposts( $headline ) {
  if ( is_singular( 'listing' ) ) {
    $headline = sprintf(
            '<h3 class="jp-relatedposts-headline"><em>%s</em></h3>',
            esc_html( 'Similar Listings' )
            );
    return $headline;
  }
}
add_filter( 'jetpack_relatedposts_filter_headline', 'wp_listings_jetpack_relatedposts' );


/**
 * Add Listings to Jetpack Omnisearch
 */
if ( class_exists( 'Jetpack_Omnisearch_Posts' ) ) {
new Jetpack_Omnisearch_Posts( 'listing' );
}


/**
 * Add Jetpack JSON Rest API Support
 */
function wp_listings_allow_post_types($allowed_post_types) {
	$allowed_post_types[] = 'listing';
	return $allowed_post_types;
}
add_filter( 'rest_api_allowed_post_types', 'wp_listings_allow_post_types');


function track_widgets_init() {
	register_sidebar( array(
		'name' => 'Track Connect',
		'id' => 'track_connect',
		'before_widget' => '<div id="track-widget">',
		'after_widget' => '</div>',
		'before_title' => '<h2 class="rounded">',
		'after_title' => '</h2>',
	) );
}
add_action( 'widgets_init', 'track_widgets_init' );

/**
 * Add Jetpack JSON Rest API Support (Listing MetaData)
 */
function wp_listings_rest_api_allowed_public_metadata( $allowed_meta_keys )
{
    // only run for REST API requests
    if ( ! defined( 'REST_API_REQUEST' ) || ! REST_API_REQUEST )
        return $allowed_meta_keys;
    
    $allowed_meta_keys[] = '_listing_occupancy';
    $allowed_meta_keys[] = '_listing_first_image';
    $allowed_meta_keys[] = '_listing_unit_id';
    $allowed_meta_keys[] = '_listing_price';
    $allowed_meta_keys[] = '_listing_address';
    $allowed_meta_keys[] = '_listing_city';
    $allowed_meta_keys[] = '_listing_state';
    $allowed_meta_keys[] = '_listing_zip';
    $allowed_meta_keys[] = '_listing_mls';
    $allowed_meta_keys[] = '_listing_open_house';
    $allowed_meta_keys[] = '_listing_year_built';
    $allowed_meta_keys[] = '_listing_floors';
    $allowed_meta_keys[] = '_listing_sqft';
    $allowed_meta_keys[] = '_listing_lot_sqft';
    $allowed_meta_keys[] = '_listing_bedrooms';
    $allowed_meta_keys[] = '_listing_bathrooms';
    $allowed_meta_keys[] = '_listing_pool';
    $allowed_meta_keys[] = '_listing_text';
    $allowed_meta_keys[] = '_listing_gallery';
    $allowed_meta_keys[] = '_listing_video';
    $allowed_meta_keys[] = '_listing_map';
    $allowed_meta_keys[] = '_listing_contact_form';
    $allowed_meta_keys[] = '_listing_home_sum';
    $allowed_meta_keys[] = '_listing_ktichen_sum';
    $allowed_meta_keys[] = '_listing_living_room';
    $allowed_meta_keys[] = '_listing_master_suite';
    $allowed_meta_keys[] = '_listing_school_neighborhood';

    return $allowed_meta_keys;
}

add_filter( 'rest_api_allowed_public_metadata', 'wp_listings_rest_api_allowed_public_metadata' );