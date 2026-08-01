<?php
/**
 * Plugin Name: Ureka SEO Tweaks (mu)
 * Description: Capability fix for Rank Math, image alt fallback, header hardening.
 */

// One-time: grant Rank Math admin capabilities to administrators
// (REST-based activation skips Rank Math's capability registration).
// Runs every admin load: Rank Math re-registers (and can drop) these on activation,
// and add_cap() is a no-op when the cap is already present.
add_action( 'admin_init', function () {
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
		if ( ! $role->has_cap( $cap ) ) {
			$role->add_cap( $cap );
		}
	}
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

// GA4 site-wide. The /als/ and /ipp/ templates bypass wp_head, so they carry
// their own copy of this tag — guard against double-tagging there.
define( 'UREKA_GA4_ID', 'G-0BJ1XCV8LZ' );
// Google Ads tag (acct 798-872-3174). Conversion is page-load on /thank-you-ipp/,
// so a site-wide config is all the wiring it needs; page-thankyou.php also reads this.
define( 'UREKA_ADS_ID', 'AW-18358274990' );
add_action( 'wp_head', function () {
	if ( is_page( array( 'als', 'ipp' ) ) ) {
		return;
	}
	printf(
		'<script async src="https://www.googletagmanager.com/gtag/js?id=%1$s"></script>' .
		'<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}' .
		'gtag("js",new Date());gtag("config","%1$s");gtag("config","%2$s");</script>',
		esc_js( UREKA_GA4_ID ),
		esc_js( UREKA_ADS_ID )
	);
}, 1 );

// Security/headers: drop PHP version disclosure, add HSTS on https.
add_action( 'send_headers', function () {
	header_remove( 'X-Powered-By' );
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
} );
