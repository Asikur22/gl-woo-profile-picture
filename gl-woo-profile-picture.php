<?php
/*
Plugin Name: GL Woocommerce Profile Picture
Plugin URI: https://www.greenlifeit.com/
Description: Allows any user to upload their own profile picture to the WooCommerce My Account Page
Version: 1.0
Author: Asiqur Rahman <asikur22@gmail.com>
Author URI: https://www.asique.net/
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'woocommerce_edit_account_form_tag', function () {
	echo 'enctype="multipart/form-data"';
} );

add_action( 'woocommerce_edit_account_form_start', function () {
	?>
<fieldset>
	<legend>Profile Picture</legend>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label><?php _e( 'Profile Picture', 'woocommerce' ); ?></label>
		<?php echo get_avatar( get_current_user_id() ); ?>
	</p>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="profile_picture"><?php _e( 'Upload Profile Picture', 'woocommerce' ); ?></label>
		<input type="file" name="profile_picture" id="profile_picture" accept=".jpg, .jpeg, .png">
	</p>
</fieldset>
	<?php
} );

add_action( 'woocommerce_save_account_details', function ( $user_id ) {
	if ( isset( $_FILES['profile_picture'] ) && ! empty( $_FILES['profile_picture']['name'] ) ) {
		$file = $_FILES['profile_picture'];
		
		$file_type = wp_check_filetype( $file['name'] );
		if ( isset( $file_type['type'] ) && str_contains( $file_type['type'], 'image' ) ) {
			$file_path  = $file['tmp_name'];
			$new_width  = 150;
			$new_height = 150;
			
			$unique_filename = md5( $user_id );
			$wp_upload       = wp_upload_dir();
			$upload_dir      = $wp_upload['basedir'] . '/wc-profile-picture/';
			if ( ! file_exists( $upload_dir ) ) {
				mkdir( $upload_dir );
			}
			$image_path = $upload_dir . $unique_filename . '.jpg';
			$image_url  = $wp_upload['baseurl'] . '/wc-profile-picture/' . $unique_filename . '.jpg';
			
			list( $width, $height ) = wp_getimagesize( $file_path );
			if ( $width > $new_width || $height > $new_height ) {
				$size  = getimagesize( $file_path );
				$ratio = $size[0] / $size[1]; // width/height
				
				if ( $ratio > 1 ) {
					$width  = $new_width;
					$height = $new_height / $ratio;
				} else {
					$width  = $new_width * $ratio;
					$height = $new_height;
				}
				
				$image_src = imagecreatefromstring( file_get_contents( $file_path ) );
				$new_image = imagecreatetruecolor( $width, $height );
				imagecopyresampled( $new_image, $image_src, 0, 0, 0, 0, $width, $height, $size[0], $size[1] );
				imagejpeg( $new_image, $image_path );
			} else {
				$uploaded_file = move_uploaded_file( $file_path, $image_path );
				if ( empty( $uploaded_file ) ) {
					wc_add_notice( __( 'Error uploading profile picture. Please try again.', 'woocommerce' ), 'error' );
				}
			}
			
			update_user_meta( $user_id, 'profile_picture', $image_url );
		} else {
			wc_add_notice( __( 'Invalid file type. Please try again.', 'woocommerce' ), 'error' );
		}
	}
} );

add_filter( 'pre_get_avatar', function ( $avatar, $id_or_email, $args ) {
	$user_id = null;
	if ( is_object( $id_or_email ) ) {
		if ( ! empty( $id_or_email->comment_author_email ) ) {
			$user_id = $id_or_email->user_id;
		}
	} else {
		if ( is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			if ( $user ) {
				$user_id = $user->ID;
			}
		} else {
			$user_id = $id_or_email;
		}
	}
	
	$avatar_url = get_user_meta( $user_id, 'profile_picture', true );
	
	if ( ! empty( $avatar_url ) ) {
		return '<img class="woo-profile-picture size-' . $args['size'] . '" src="' . $avatar_url . '" alt="Profile Picture" style="object-fit: cover;width:' . $args['size'] . 'px; height:' . $args['size'] . 'px;">';
	}
	
	return $avatar;
}, 10, 3 );