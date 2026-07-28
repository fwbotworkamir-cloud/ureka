<?php
/**
 * Plugin Name: Ureka SEO Tweaks (mu)
 * Description: Capability fix for Rank Math, image alt fallback, header hardening.
 */

// One-time: grant Rank Math admin capabilities to administrators
// (REST-based activation skips Rank Math's capability registration).
add_action( 'admin_init', function () {
	if ( get_option( 'ureka_rm_caps_fixed' ) ) {
		return;
	}
	$role = get_role( 'administrator' );
	if ( ! $role ) {
		return;
	}
	$caps = array(
		'rank_math_titles', 'rank_math_general', 'rank_math_sitemap',
		'rank_math_404_monitor', 'rank_math_link_builder', 'rank_math_redirections',
		'rank_math_role_manager', 'rank_math_analytics', 'rank_math_site_analysis',
		'rank_math_onpage_analysis', 'rank_math_onpage_general', 'rank_math_onpage_advanced',
		'rank_math_onpage_snippet', 'rank_math_onpage_social', 'rank_math_admin_bar',
		'rank_math_edit_htaccess', 'rank_math_content_ai',
	);
	foreach ( $caps as $cap ) {
		$role->add_cap( $cap );
	}
	update_option( 'ureka_rm_caps_fixed', 1 );
} );

// SEO: derive alt text from attachment title/filename when alt is missing (97% of images had none).
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment ) {
	if ( empty( $attr['alt'] ) && $attachment instanceof WP_Post ) {
		$alt = trim( str_replace( array( '-', '_' ), ' ', $attachment->post_title ) );
		if ( $alt ) {
			$attr['alt'] = $alt;
		}
	}
	return $attr;
}, 10, 2 );

// Same fallback for images inside post content.
add_filter( 'the_content', function ( $content ) {
	return preg_replace_callback( '/<img(?![^>]*\balt=)[^>]*>/i', function ( $m ) {
		$tag = $m[0];
		if ( preg_match( '/src="[^"]*\/([^"\/]+)\.(?:jpe?g|png|webp|gif)/i', $tag, $src ) ) {
			$alt = ucwords( trim( preg_replace( '/[-_]+|\d{3,}x\d{3,}|\d{5,}/', ' ', $src[1] ) ) );
			return str_replace( '<img', '<img alt="' . esc_attr( $alt ) . '"', $tag );
		}
		return $tag;
	}, $content );
}, 20 );

// Security/headers: drop PHP version disclosure, add HSTS on https.
add_action( 'send_headers', function () {
	header_remove( 'X-Powered-By' );
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
} );
