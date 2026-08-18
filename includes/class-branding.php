<?php
/**
 * Global branding: one option, plus the [cf7etm_*] tags templates can use.
 *
 * These tags are resolved by us before Contact Form 7 ever sees the body,
 * so CF7 only has to deal with its own mail-tags.
 *
 * @package CF7_Email_Template_Manager
 */

defined( 'ABSPATH' ) || exit;

class CF7ETM_Branding {

	const OPTION = 'cf7etm_branding';

	/**
	 * Branding defaults, seeded from the site itself so a fresh install
	 * already produces sensible emails.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'company_name'     => get_bloginfo( 'name' ),
			'logo'             => '',
			'website'          => home_url( '/' ),
			'primary_color'    => '#2271b1',
			'secondary_color'  => '#1d2327',
			'footer_text'      => sprintf(
				/* translators: %s: site name */
				__( 'You are receiving this email because you contacted %s.', 'cf7-email-template-manager' ),
				get_bloginfo( 'name' )
			),
			'address'          => '',
			'social_facebook'  => '',
			'social_twitter'   => '',
			'social_linkedin'  => '',
			'social_instagram' => '',
		);
	}

	/**
	 * Current branding values.
	 *
	 * @return array
	 */
	public static function get() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
	}

	/**
	 * Sanitizes and stores branding values.
	 *
	 * @param array $input Raw input.
	 * @return array Stored values.
	 */
	public static function save( $input ) {
		$clean = array();

		foreach ( self::defaults() as $key => $default ) {
			$value = $input[ $key ] ?? '';

			$clean[ $key ] = match ( $key ) {
				'logo', 'website', 'social_facebook', 'social_twitter', 'social_linkedin', 'social_instagram' => esc_url_raw( $value ),
				'primary_color', 'secondary_color' => self::sanitize_color( $value, $default ),
				'footer_text', 'address' => sanitize_textarea_field( $value ),
				default => sanitize_text_field( $value ),
			};
		}

		update_option( self::OPTION, $clean );

		return $clean;
	}

	/**
	 * Accepts only a hex colour, otherwise keeps the default.
	 *
	 * @param string $value    Candidate colour.
	 * @param string $fallback Value to use when invalid.
	 * @return string
	 */
	public static function sanitize_color( $value, $fallback = '#2271b1' ) {
		$color = sanitize_hex_color( $value );
		return $color ? $color : $fallback;
	}

	/**
	 * Tag name => human label, for the editor sidebar.
	 *
	 * @return array
	 */
	public static function tags() {
		return array(
			'cf7etm_company_name'    => __( 'Company Name', 'cf7-email-template-manager' ),
			'cf7etm_logo'            => __( 'Logo', 'cf7-email-template-manager' ),
			'cf7etm_website'         => __( 'Website URL', 'cf7-email-template-manager' ),
			'cf7etm_address'         => __( 'Company Address', 'cf7-email-template-manager' ),
			'cf7etm_footer_text'     => __( 'Footer Text', 'cf7-email-template-manager' ),
			'cf7etm_primary_color'   => __( 'Primary Colour', 'cf7-email-template-manager' ),
			'cf7etm_secondary_color' => __( 'Secondary Colour', 'cf7-email-template-manager' ),
			'cf7etm_social_links'    => __( 'Social Links', 'cf7-email-template-manager' ),
			'cf7etm_year'            => __( 'Current Year', 'cf7-email-template-manager' ),
		);
	}

	/**
	 * Replaces every [cf7etm_*] tag in a string.
	 *
	 * @param string $text Template text.
	 * @param bool   $html Whether the output is HTML.
	 * @return string
	 */
	public static function replace( $text, $html = true ) {
		$b = self::get();

		$logo = '';

		if ( $b['logo'] ) {
			$logo = $html
				? sprintf(
					'<img src="%s" alt="%s" style="max-width:180px;height:auto;border:0;" />',
					esc_url( $b['logo'] ),
					esc_attr( $b['company_name'] )
				)
				: $b['company_name'];
		} elseif ( $html ) {
			$logo = esc_html( $b['company_name'] );
		} else {
			$logo = $b['company_name'];
		}

		$map = array(
			'[cf7etm_company_name]'    => $html ? esc_html( $b['company_name'] ) : $b['company_name'],
			'[cf7etm_logo]'            => $logo,
			'[cf7etm_website]'         => esc_url( $b['website'] ),
			'[cf7etm_address]'         => $html ? nl2br( esc_html( $b['address'] ) ) : $b['address'],
			'[cf7etm_footer_text]'     => $html ? esc_html( $b['footer_text'] ) : $b['footer_text'],
			'[cf7etm_primary_color]'   => $b['primary_color'],
			'[cf7etm_secondary_color]' => $b['secondary_color'],
			'[cf7etm_social_links]'    => self::social_links( $b, $html ),
			'[cf7etm_year]'            => wp_date( 'Y' ),
		);

		return str_replace( array_keys( $map ), array_values( $map ), $text );
	}

	/**
	 * Builds the social links fragment.
	 *
	 * @param array $b    Branding values.
	 * @param bool  $html Whether to output HTML.
	 * @return string
	 */
	private static function social_links( $b, $html ) {
		$networks = array(
			'social_facebook'  => __( 'Facebook', 'cf7-email-template-manager' ),
			'social_twitter'   => __( 'X', 'cf7-email-template-manager' ),
			'social_linkedin'  => __( 'LinkedIn', 'cf7-email-template-manager' ),
			'social_instagram' => __( 'Instagram', 'cf7-email-template-manager' ),
		);

		$parts = array();

		foreach ( $networks as $key => $label ) {
			if ( empty( $b[ $key ] ) ) {
				continue;
			}

			$parts[] = $html
				? sprintf(
					'<a href="%s" style="color:%s;text-decoration:none;">%s</a>',
					esc_url( $b[ $key ] ),
					esc_attr( $b['primary_color'] ),
					esc_html( $label )
				)
				: $label . ': ' . $b[ $key ];
		}

		if ( ! $parts ) {
			return '';
		}

		return $html ? implode( ' &nbsp;·&nbsp; ', $parts ) : implode( "\n", $parts );
	}
}
