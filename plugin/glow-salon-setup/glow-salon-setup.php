<?php
/**
 * Plugin Name: Glow Salon Setup
 * Description: Builds a beauty salon website on activation: pages (Home, Services, Gallery, Contact), the main menu, the homepage setting and styling. Adds an appointment booking form ([gb_booking]) with a Bookings inbox, and a gallery shortcode ([gb_gallery]).
 * Version: 1.0
 * Author: Glow Beauty Salon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Services (used by the Services page and the booking form) ---------- */
function gb_services() {
	return array(
		'Hair' => array(
			'sub'   => 'Cuts, colour and care',
			'items' => array( 'Haircut & Style' => 'Rs. 1,500', 'Blow Dry' => 'Rs. 1,200', 'Hair Colour' => 'Rs. 6,500', 'Keratin Treatment' => 'Rs. 12,000' ),
		),
		'Skin' => array(
			'sub'   => 'Facials for every skin type',
			'items' => array( 'Classic Facial' => 'Rs. 3,500', 'Hydrating Facial' => 'Rs. 4,500', 'Deep Cleanse' => 'Rs. 2,500' ),
		),
		'Nails' => array(
			'sub'   => 'Hands and feet',
			'items' => array( 'Manicure' => 'Rs. 2,000', 'Pedicure' => 'Rs. 2,500', 'Gel Polish' => 'Rs. 3,000' ),
		),
		'Makeup & Spa' => array(
			'sub'   => 'Special days and relaxation',
			'items' => array( 'Party Makeup' => 'Rs. 8,000', 'Head Massage' => 'Rs. 1,500', 'Relaxing Massage' => 'Rs. 5,000' ),
		),
	);
}

function gb_service_names() {
	$names = array();
	foreach ( gb_services() as $group ) {
		foreach ( $group['items'] as $name => $price ) {
			$names[] = $name;
		}
	}
	return $names;
}

/* ---------- Bookings inbox ---------- */
add_action( 'init', function () {
	register_post_type( 'gb_booking', array(
		'labels'       => array( 'name' => 'Bookings', 'singular_name' => 'Booking', 'menu_name' => 'Bookings', 'not_found' => 'No bookings yet.' ),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-calendar-alt',
		'menu_position'=> 26,
		'supports'     => array( 'title', 'editor' ),
		'capabilities' => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap' => true,
	) );
} );

/* ---------- [gb_booking] form ---------- */
add_shortcode( 'gb_booking', function () {
	$status = isset( $_GET['gb_sent'] ) ? sanitize_text_field( wp_unslash( $_GET['gb_sent'] ) ) : '';
	ob_start();
	if ( '1' === $status ) {
		echo '<div class="gb-notice gb-ok">Thank you! Your appointment request has been received. We will confirm it soon.</div>';
	} elseif ( '0' === $status ) {
		echo '<div class="gb-notice gb-err">Please fill in your name, a valid email, a service and a date that is today or later.</div>';
	}
	?>
	<form class="gb-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="gb_booking_submit">
		<input type="hidden" name="gb_back" value="<?php echo esc_url( get_permalink() ); ?>">
		<?php wp_nonce_field( 'gb_booking', 'gb_nonce' ); ?>
		<label>Your name <input type="text" name="gb_name" required></label>
		<div class="gb-row">
			<label>Email address <input type="email" name="gb_email" required></label>
			<label>Phone (optional) <input type="tel" name="gb_phone"></label>
		</div>
		<div class="gb-row">
			<label>Service
				<select name="gb_service" required>
					<option value="">Choose a service</option>
					<?php foreach ( gb_service_names() as $n ) : ?>
						<option value="<?php echo esc_attr( $n ); ?>"><?php echo esc_html( $n ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>Preferred date <input type="date" name="gb_date" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" required></label>
		</div>
		<label>Message (optional) <textarea name="gb_message" rows="4"></textarea></label>
		<p class="gb-hp" aria-hidden="true"><label>Leave empty <input type="text" name="gb_website" tabindex="-1" autocomplete="off"></label></p>
		<button type="submit">Request appointment</button>
	</form>
	<?php
	return ob_get_clean();
} );

function gb_booking_handle() {
	$back  = isset( $_POST['gb_back'] ) ? esc_url_raw( wp_unslash( $_POST['gb_back'] ) ) : home_url( '/' );
	$nonce = isset( $_POST['gb_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['gb_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'gb_booking' ) ) {
		wp_safe_redirect( add_query_arg( 'gb_sent', '0', $back ) );
		exit;
	}
	if ( ! empty( $_POST['gb_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'gb_sent', '1', $back ) );
		exit;
	}
	$name    = isset( $_POST['gb_name'] ) ? sanitize_text_field( wp_unslash( $_POST['gb_name'] ) ) : '';
	$email   = isset( $_POST['gb_email'] ) ? sanitize_email( wp_unslash( $_POST['gb_email'] ) ) : '';
	$phone   = isset( $_POST['gb_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['gb_phone'] ) ) : '';
	$service = isset( $_POST['gb_service'] ) ? sanitize_text_field( wp_unslash( $_POST['gb_service'] ) ) : '';
	$date    = isset( $_POST['gb_date'] ) ? sanitize_text_field( wp_unslash( $_POST['gb_date'] ) ) : '';
	$message = isset( $_POST['gb_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['gb_message'] ) ) : '';

	$date_ok = (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && $date >= gmdate( 'Y-m-d', time() - DAY_IN_SECONDS );
	if ( '' === $name || ! is_email( $email ) || ! in_array( $service, gb_service_names(), true ) || ! $date_ok ) {
		wp_safe_redirect( add_query_arg( 'gb_sent', '0', $back ) );
		exit;
	}

	$body = "Name: $name\nEmail: $email\nPhone: $phone\nService: $service\nPreferred date: $date\n\n$message";
	$id   = wp_insert_post( array(
		'post_type'    => 'gb_booking',
		'post_status'  => 'publish',
		'post_title'   => $service . ' on ' . $date . ' - ' . $name,
		'post_content' => $body,
	) );
	@wp_mail( get_option( 'admin_email' ), '[Glow Beauty Salon] New booking request: ' . $service, $body );
	wp_safe_redirect( add_query_arg( 'gb_sent', '1', $back ) );
	exit;
}
add_action( 'admin_post_nopriv_gb_booking_submit', 'gb_booking_handle' );
add_action( 'admin_post_gb_booking_submit', 'gb_booking_handle' );

/* ---------- Images: find by file name in the Media Library ---------- */
function gb_attachment_id( $slug ) {
	global $wpdb;
	$like = '%/' . $wpdb->esc_like( 'gb-' . $slug ) . '%';
	$id   = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id ASC LIMIT 1", $like ) );
	return $id ? (int) $id : 0;
}

function gb_img_url( $slug ) {
	$id = gb_attachment_id( $slug );
	return $id ? (string) wp_get_attachment_url( $id ) : '';
}

function gb_image_list() {
	return array(
		'haircut'    => 'Haircut and styling',
		'facial'     => 'Facial treatment',
		'manicure'   => 'Manicure and nail art',
		'hair-color' => 'Hair colour',
		'makeup'     => 'Makeup',
		'spa'        => 'Spa and relaxation',
		'pedicure'   => 'Pedicure',
	);
}

function gb_sideload_all() {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	foreach ( gb_image_list() as $slug => $title ) {
		if ( gb_attachment_id( $slug ) ) {
			continue;
		}
		$src = plugin_dir_path( __FILE__ ) . 'images/' . $slug . '.jpg';
		if ( ! file_exists( $src ) ) {
			continue;
		}
		$tmp = wp_tempnam( $slug . '.jpg' );
		copy( $src, $tmp );
		$id = media_handle_sideload( array( 'name' => 'gb-' . $slug . '.jpg', 'tmp_name' => $tmp ), 0, $title );
		if ( is_wp_error( $id ) ) {
			@unlink( $tmp );
			continue;
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $title );
	}
}

/* ---------- [gb_gallery] ---------- */
add_shortcode( 'gb_gallery', function () {
	$out = '<div class="gb-gallery">';
	foreach ( gb_image_list() as $slug => $title ) {
		$id = gb_attachment_id( $slug );
		if ( ! $id ) {
			continue;
		}
		$out .= '<figure>' . wp_get_attachment_image( $id, 'large', false, array( 'alt' => $title ) ) . '<figcaption>' . esc_html( $title ) . '</figcaption></figure>';
	}
	return $out . '</div>';
} );

/* ---------- Page content ---------- */
function gb_page_html( $key ) {
	$book = esc_url( home_url( '/contact/' ) );
	$serv = esc_url( home_url( '/services/' ) );

	if ( 'home' === $key ) {
		$tile = function ( $cls, $title, $text ) use ( $serv ) {
			return '<a class="gb-tile gb-tile--' . esc_attr( $cls ) . '" href="' . $serv . '"><div><h3>' . esc_html( $title ) . '</h3><span>' . esc_html( $text ) . '</span></div></a>';
		};
		return '<div class="gb-hero"><h1>Glow Beauty Salon</h1><p>Hair, skin, nails and makeup in one calm, welcoming space.</p><div class="gb-btns"><a class="gb-btn" href="' . $book . '">Book an appointment</a><a class="gb-btn gb-btn-ghost" href="' . $serv . '">View services</a></div></div>'
			. '<div class="gb-stats"><div class="gb-stat"><strong>4</strong><span>Service areas</span></div><div class="gb-stat"><strong>13</strong><span>Treatments</span></div><div class="gb-stat"><strong>7</strong><span>Days a week</span></div></div>'
			. '<div class="gb-section"><h2>Our services</h2><div class="gb-tiles">'
			. $tile( 'hair', 'Hair', 'Cuts, colour and care' )
			. $tile( 'skin', 'Skin', 'Facials for every skin type' )
			. $tile( 'nails', 'Nails', 'Manicure and pedicure' )
			. $tile( 'makeup', 'Makeup', 'For special days' )
			. '</div></div>'
			. '<div class="gb-section gb-band"><h2>Why choose us</h2><div class="gb-features">'
			. '<div class="gb-feature"><div class="gb-ico">&#10024;</div><h3>Quality products</h3><p>Care you can trust for your hair and skin.</p></div>'
			. '<div class="gb-feature"><div class="gb-ico">&#127807;</div><h3>Relaxing space</h3><p>A calm, clean place to take a break.</p></div>'
			. '<div class="gb-feature"><div class="gb-ico">&#128197;</div><h3>Easy booking</h3><p>Request an appointment online in a minute.</p></div>'
			. '</div></div>'
			. '<div class="gb-section"><h2>Gallery</h2>[gb_gallery]</div>'
			. '<div class="gb-section"><h2>Opening hours</h2><div class="gb-hours">'
			. '<div class="gb-price-row"><span class="gb-name">Monday - Friday</span><span class="gb-dots"></span><span class="gb-amt">9:00 AM - 7:00 PM</span></div>'
			. '<div class="gb-price-row"><span class="gb-name">Saturday</span><span class="gb-dots"></span><span class="gb-amt">9:00 AM - 8:00 PM</span></div>'
			. '<div class="gb-price-row"><span class="gb-name">Sunday</span><span class="gb-dots"></span><span class="gb-amt">10:00 AM - 5:00 PM</span></div>'
			. '</div></div>'
			. '<div class="gb-section"><div class="gb-cta"><h2>Ready for a fresh look?</h2><p>Choose a service and a date, and we will confirm your appointment.</p><a class="gb-btn" href="' . $book . '">Book now</a></div></div>';
	}

	if ( 'services' === $key ) {
		$cards = '';
		foreach ( gb_services() as $group => $data ) {
			$rows = '';
			foreach ( $data['items'] as $name => $price ) {
				$rows .= '<div class="gb-price-row"><span class="gb-name">' . esc_html( $name ) . '</span><span class="gb-dots"></span><span class="gb-amt">' . esc_html( $price ) . '</span></div>';
			}
			$cards .= '<div class="gb-card"><h3>' . esc_html( $group ) . '</h3><p class="gb-sub">' . esc_html( $data['sub'] ) . '</p>' . $rows . '</div>';
		}
		return '<div class="gb-banner gb-banner--services"><h1>Services &amp; prices</h1><p>Simple, clear prices for every treatment.</p></div>'
			. '<div class="gb-section"><div class="gb-menu-grid">' . $cards . '</div><p class="gb-note">Prices are in Sri Lankan rupees and may change for longer or thicker hair.</p></div>'
			. '<div class="gb-section"><div class="gb-cta"><h2>Found something you like?</h2><p>Book your appointment online.</p><a class="gb-btn" href="' . $book . '">Book now</a></div></div>';
	}

	if ( 'gallery' === $key ) {
		return '<div class="gb-banner gb-banner--gallery"><h1>Gallery</h1><p>A look at what we do.</p></div>'
			. '<div class="gb-section">[gb_gallery]</div>'
			. '<div class="gb-section"><div class="gb-cta"><h2>Like what you see?</h2><p>Book a treatment and treat yourself.</p><a class="gb-btn" href="' . $book . '">Book now</a></div></div>';
	}

	return '<div class="gb-banner gb-banner--contact"><h1>Book an appointment</h1><p>Choose a service and a preferred date. We will confirm your booking by email.</p></div>'
		. '<div class="gb-section"><div class="gb-contact-grid"><div>'
		. '<div class="gb-info"><h3>Opening hours</h3><p>Monday - Friday: 9:00 AM - 7:00 PM<br>Saturday: 9:00 AM - 8:00 PM<br>Sunday: 10:00 AM - 5:00 PM</p></div>'
		. '<div class="gb-info"><h3>How booking works</h3><p>Send a request with your service and date. We will reply to confirm the time.</p></div>'
		. '<div class="gb-info"><h3>Questions?</h3><p>Add them in the message box and we will answer when we confirm.</p></div>'
		. '</div><div class="gb-formbox">[gb_booking]</div></div></div>';
}

function gb_make_page( $title, $slug, $html ) {
	if ( get_page_by_path( $slug ) ) {
		return get_page_by_path( $slug )->ID;
	}
	$id = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => "<!-- wp:html -->\n" . $html . "\n<!-- /wp:html -->",
	) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, 'site-sidebar-layout', 'no-sidebar' );
		update_post_meta( $id, 'site-content-layout', 'normal-width-container' );
		update_post_meta( $id, 'site-post-title', 'disabled' );
		return $id;
	}
	return 0;
}

/* ---------- Menu ---------- */
function gb_assign_menu() {
	$menu = wp_get_nav_menu_object( 'Glow Menu' );
	if ( ! $menu ) {
		return;
	}
	$locs = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locs['primary'] ) ) {
		$locs['primary'] = $menu->term_id;
		set_theme_mod( 'nav_menu_locations', $locs );
	}
}
add_action( 'after_switch_theme', 'gb_assign_menu' );

/* ---------- Setup on activation, and a safe re-check in the admin ---------- */
register_activation_hook( __FILE__, 'gb_setup' );
add_action( 'admin_init', 'gb_apply' );

function gb_setup() {
	gb_sideload_all();

	$ids = array(
		'home'     => gb_make_page( 'Home', 'home', gb_page_html( 'home' ) ),
		'services' => gb_make_page( 'Services', 'services', gb_page_html( 'services' ) ),
		'gallery'  => gb_make_page( 'Gallery', 'gallery', gb_page_html( 'gallery' ) ),
		'contact'  => gb_make_page( 'Contact', 'contact', gb_page_html( 'contact' ) ),
	);

	update_option( 'blogname', 'Glow Beauty Salon' );
	update_option( 'blogdescription', 'Hair, skin, nails and makeup' );
	if ( $ids['home'] ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}

	if ( ! wp_get_nav_menu_object( 'Glow Menu' ) ) {
		$menu_id = wp_create_nav_menu( 'Glow Menu' );
		if ( ! is_wp_error( $menu_id ) ) {
			$pos = 1;
			foreach ( array( 'home' => 'Home', 'services' => 'Services', 'gallery' => 'Gallery', 'contact' => 'Book Now' ) as $k => $label ) {
				if ( $ids[ $k ] ) {
					wp_update_nav_menu_item( $menu_id, 0, array(
						'menu-item-title'     => $label,
						'menu-item-object'    => 'page',
						'menu-item-object-id' => $ids[ $k ],
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-position'  => $pos++,
						'menu-item-classes'   => 'contact' === $k ? 'gb-book-btn' : '',
					) );
				}
			}
		}
	}
	gb_assign_menu();
	gb_apply();
	flush_rewrite_rules();
}

function gb_apply() {
	gb_assign_menu();

	// CSS: main file once, then the photo backgrounds once the images exist.
	$current = wp_get_custom_css();
	$add     = '';
	$file    = plugin_dir_path( __FILE__ ) . 'css/glow-salon.css';
	if ( false === strpos( $current, '/* GLOW SALON CSS */' ) && file_exists( $file ) ) {
		$add .= "\n" . file_get_contents( $file );
	}
	if ( false === strpos( $current . $add, '/* GLOW SALON IMAGES */' ) ) {
		$u = array();
		foreach ( array_keys( gb_image_list() ) as $sl ) {
			$u[ $sl ] = gb_img_url( $sl );
		}
		if ( ! in_array( '', $u, true ) ) {
			$bg  = function ( $sel, $sl, $overlay = '' ) use ( $u ) {
				return $sel . "{background-image:" . $overlay . "url('" . esc_url_raw( $u[ $sl ] ) . "');}\n";
			};
			$add .= "\n/* GLOW SALON IMAGES */\n"
				. $bg( '.gb-hero', 'hair-color' )
				. $bg( '.gb-tile--hair', 'haircut' )
				. $bg( '.gb-tile--skin', 'facial' )
				. $bg( '.gb-tile--nails', 'manicure' )
				. $bg( '.gb-tile--makeup', 'makeup' )
				. $bg( '.gb-banner--services', 'haircut' )
				. $bg( '.gb-banner--gallery', 'manicure' )
				. $bg( '.gb-banner--contact', 'makeup' )
				. $bg( '.gb-cta', 'spa' );
		}
	}
	if ( '' !== $add ) {
		wp_update_custom_css_post( $current . $add );
	}

	// Astra options, once.
	if ( ! get_option( 'gb_opts_done' ) ) {
		$opts = get_option( 'astra-settings', array() );
		if ( is_array( $opts ) ) {
			$opts['footer-copyright-editor'] = 'Copyright [copyright] [current_year] [site_title]. All rights reserved.';
			$opts['scroll-to-top-enable']    = 1;
			update_option( 'astra-settings', $opts );
		}
		update_option( 'gb_opts_done', 1 );
	}
}
