<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'KT_Galleries_Get_Image' ) ) {
	class KT_Galleries_Get_Image {
        /**
         * The singleton instance
         */
        static private $instance = null;
        /**
         * No initialization allowed
         */
        private function __construct() {}

        /**
         * No cloning allowed
         */
        private function __clone() {}

        static public function getInstance() {
            if(self::$instance == null) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        public function process($id = null, $width = null, $height = null) {
			// return if no ID
			if(empty($id)) {
				return false;
			}
			// return with orginal if no width or height set.
			if(empty($width) && empty($height) ) {
				return self::kt_gal_get_full_image($id);
			}
			// Find width or height if one or the other is not set.
			$org_height = true;
			if(empty($height) ) {
				$org_height = false;
		        $image_attributes = wp_get_attachment_image_src( $id, 'full' );
		        $sizes = image_resize_dimensions($image_attributes[1], $image_attributes[2], $width, null, false );
		        $height = $sizes[5];
		    } else if(empty($width) ) {
		        $image_attributes = wp_get_attachment_image_src( $id, 'full' );
		        $sizes = image_resize_dimensions($image_attributes[1], $image_attributes[2], null, $height, false );
		        $width = $sizes[4];
		    }
		    // Now we checked for an ID, made sure the width and height have values lets check if we can make the size at all
		    if ( self::kt_gal_image_size_larger_than_original( $id, $width, $height ) ) {
		    	return self::kt_gal_get_full_image($id);
			}
		    //Check for jetpack
		    if( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
		    	$args = array( 'resize' => $width . ',' . $height );
		    	$image_url = wp_get_attachment_image_url($id, 'full');
		    	return array (
		            0 => jetpack_photon_url( $image_url, $args ),
		            1 => $width,
		            2 => $height,
		            3 => self::kt_gal_get_srcset_output($id, $image_url, $width, $height),
					4 => $image_url,
					5 => $id
		        );
		    } else if( self::kt_gal_image_size_already_exists( $id, $width, $height ) ) {

		    		return self::kt_gal_get_image_at_size($id, $width, $height );

		    } else if(class_exists( 'Kadence_Image_Processing' )) {
		    	// lets process the image
		    	$Kadence_Image_Processing = Kadence_Image_Processing::getInstance();
            	$created = $Kadence_Image_Processing->process($id, $width, $height);
            	if($created) {
		    		return self::kt_gal_get_image_at_size($id, $width, $height );
			    } else {
			    	return self::kt_gal_get_full_image($id);
			    }
		    } else {
		    	//get the next best thing
		    	if(800 < $width) {
		    		// Large image
		    		return self::kt_gal_get_full_image($id);
		    	} else if($org_height) {
		    		//custom_ratio
		    		if($width == $height) {
		    			// square
		    			if(150 >= $width) {
		    				// Thumbnail
		    				return self::kt_gal_get_image_at_size($id, 150,150);
		    			} else {
		    				return self::kt_gal_get_full_image($id);
		    			}
		    		} else {
						return self::kt_gal_get_full_image($id);
					}
		    	} else {
		    		// Orginal ratio
		    		if(300 >= $width) {
		    			return self::kt_gal_get_image_at_size_name($id, 'medium');
		    		} elseif(800 > $width) {
		    			return self::kt_gal_get_image_at_size_name($id, 'large');
		    		} else {
		    			return self::kt_gal_get_full_image($id);
		    		}
		    	}

		    }
		}
		public static function kt_gal_get_image_srcset($id = null, $url = null, $width = null, $height = null) {
		  	if(empty($id) || empty($url) || empty($width) || empty($height)) {
		    	return false;
		  	}
		  
		  	$image_meta = self::kt_gal_get_image_meta($id);
		  	if ( ! $image_meta ) {
				return false;
			}
		  	
			if(function_exists ( 'wp_calculate_image_srcset') ){
		  		$output = wp_calculate_image_srcset(array( $width, $height), $url, $image_meta, $id);
			} else {
		  		$output = '';
			}

		    return $output;
		}
		public static function kt_gal_get_srcset_output($id = null, $url = null, $width = null, $height = null) {
		    $img_srcset = self::kt_gal_get_image_srcset($id, $url, $width, $height);
		    if(!empty($img_srcset) ) {
		      	$output = 'srcset="'.esc_attr($img_srcset).'" sizes="(max-width: '.esc_attr($width).'px) 100vw, '.esc_attr($width).'px"';
		    } else {
		      	$output = '';
		    }
		    return $output;
		}
		public static function kt_gal_image_size_larger_than_original($id, $width, $height) {
				$image_meta = self::kt_gal_get_image_meta( $id );

				if ( ! isset( $image_meta['width'] ) || ! isset( $image_meta['height'] ) ) {
					return true;
				}
				if ( $width > $image_meta['width'] || $height > $image_meta['height'] ) {
					return true;
				}

				return false;
		}
		public static function kt_gal_get_full_image($id) {
				$src = wp_get_attachment_image_src($id, 'full' );
				// array return.
				$image = array (
					0 => $src[0],
					1 => $src[1],
					2 => $src[2],
					3 => self::kt_gal_get_srcset_output($id, $src[0], $src[1], $src[2]),
					4 => $src[0],
					5 => $id
				);
				return $image;
		}
		public static function kt_gal_get_image_at_size($id, $width, $height) {
				$size = array(
					0 => $width,
					1 => $height
				);
				$src = wp_get_attachment_image_src($id, $size );
				$full = wp_get_attachment_image_url($id, 'full' );
				// array return.
				$image = array (
					0 => $src[0],
					1 => $src[1],
					2 => $src[2],
					3 => self::kt_gal_get_srcset_output($id, $src[0], $src[1], $src[2]),
					4 => $full,
					5 => $id
				);
				return $image;
		}
		public static function kt_gal_get_image_at_size_name($id, $size) {
				$src = wp_get_attachment_image_src($id, $size );
				$full = wp_get_attachment_image_url($id, 'full' );
				// array return.
				$image = array (
					0 => $src[0],
					1 => $src[1],
					2 => $src[2],
					3 => self::kt_gal_get_srcset_output($id, $src[0], $src[1], $src[2]),
					4 => $full,
					5 => $id
				);
				return $image;
		}
		public static function kt_gal_get_image_meta( $id ) {
			return wp_get_attachment_metadata( $id );
		}
		public static function kt_gal_image_size_already_exists( $id, $width, $height ) {
			$image_meta = self::kt_gal_get_image_meta( $id );
			$kip_size_name = self::kip_get_size_name( array( $width, $height ));
			if(isset( $image_meta['sizes'][ $kip_size_name ] ) ) {
				return true;
			} else {
				return false;
			}
		}
		public static function kip_get_size_name( $size ) {
			return 'kip-' . $size[0] . 'x' . $size[1];
		}
	}
}


/**
 * Image Functions
 */

function kt_gal_lazy_load_filter() {
  $lazy = false;
  if(function_exists( 'get_rocket_option' ) && get_rocket_option( 'lazyload') ) {
    $lazy = true;
  }
  return apply_filters('kadence_gallery_lazy_load', $lazy);
}
function kt_gal_get_attachment_meta( $id ) {

    $attachment = get_post( $id );
    $meta = wp_get_attachment_metadata($id);
    return array(
        'alt' => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
        'caption' => $attachment->post_excerpt,
        'description' => $attachment->post_content,
        'href' => get_permalink( $attachment->ID ),
        'src' => $attachment->guid,
        'title' => $attachment->post_title,
        'width' => $meta['width'],
        'height' => $meta['height'],
    );
}
function kt_gal_default_placeholder_image() {
    return apply_filters('kt_gal_default_placeholder_image', 'http://placehold.it/');
}

function kt_gal_get_image_array($width = null, $height = null, $crop = true, $class = null, $alt = null, $id = null, $placeholder = false) {
    if(empty($id)) {
        $id = get_post_thumbnail_id();
    }
    if(!empty($id)) {
        $kt_gal_get_image = KT_Galleries_Get_Image::getInstance();
        $image = $kt_gal_get_image->process( $id, $width, $height);
        if(empty($alt)) {
            $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
        }
        $return_array = array(
            'src' => $image[0],
            'width' => $image[1],
            'height' => $image[2],
            'srcset' => $image[3],
            'class' => $class,
            'alt' => $alt,
            'full' => $image[4],
        );
    } else if(empty($id) && $placeholder == true) {
    	if(empty($height)){
    		$height = $width;
    	}
    	if(empty($width)){
    		$width = $height;
    	}
        $return_array = array(
            'src' => kt_gal_default_placeholder_image().$width.'x'.$height.'?text=Image+Placeholder',
            'width' => $width,
            'height' => $height,
            'srcset' => '',
            'class' => $class,
            'alt' => $alt,
            'full' => kt_gal_default_placeholder_image().$width.'x'.$height.'?text=Image+Placeholder',
        );
    } else {
        $return_array = array(
            'src' => '',
            'width' => '',
            'height' => '',
            'srcset' => '',
            'class' => '',
            'alt' => '',
            'full' => '',
        );
    }

    return $return_array;
}

function kt_gal_get_full_image_output($width = null, $height = null, $crop = true, $class = null, $alt = null, $id = null, $placeholder = false, $lazy = false, $schema = true, $extra = null) {
    $img = kt_gal_get_image_array($width, $height, $crop, $class, $alt, $id, $placeholder);
    if($lazy) {
        if( kt_gal_lazy_load_filter() ) {
            $image_src_output = 'src="data:image/gif;base64,R0lGODdhAQABAPAAAP///wAAACwAAAAAAQABAEACAkQBADs=" data-lazy-src="'.esc_url($img['src']).'" '; 
        } else {
            $image_src_output = 'src="'.esc_url($img['src']).'"'; 
        }
    } else {
        $image_src_output = 'src="'.esc_url($img['src']).'"'; 
    }
    $extras = '';
    if(is_array($extra)) {
    	foreach ($extra as $key => $value) {
    		$extras .= esc_attr($key).'="'.esc_attr($value).'" ';
    	}
    } else {
    	$extras = $extra;	
    }
    if(!empty($img['src']) && $schema == true) {
        $output = '<div itemprop="image" itemscope itemtype="http://schema.org/ImageObject">';
        $output .='<img '.$image_src_output.' width="'.esc_attr($img['width']).'" height="'.esc_attr($img['height']).'" '.$img['srcset'].' class="'.esc_attr($img['class']).'" itemprop="contentUrl" alt="'.esc_attr($img['alt']).'" '.$extras.'>';
        $output .= '<meta itemprop="url" content="'.esc_url($img['src']).'">';
        $output .= '<meta itemprop="width" content="'.esc_attr($img['width']).'px">';
        $output .= '<meta itemprop="height" content="'.esc_attr($img['height']).'px">';
        $output .= '</div>';
      	return $output;

    } elseif(!empty($img['src'])) {
        return '<img '.$image_src_output.' width="'.esc_attr($img['width']).'" height="'.esc_attr($img['height']).'" '.$img['srcset'].' class="'.esc_attr($img['class']).'" alt="'.esc_attr($img['alt']).'" '.$extras.'>';
    } else {
        return null;
    }
}
function kt_gal_get_full_intrinsic_image_output($width = null, $height = null, $crop = true, $class = null, $alt = null, $id = null, $placeholder = false, $lazy = false, $schema = true, $extra = null) {
    $img = kt_gal_get_image_array($width, $height, $crop, $class, $alt, $id, $placeholder);
    if($lazy) {
        if( kt_gal_lazy_load_filter() ) {
            $image_src_output = 'src="data:image/gif;base64,R0lGODdhAQABAPAAAP///wAAACwAAAAAAQABAEACAkQBADs=" data-lazy-src="'.esc_url($img['src']).'" '; 
        } else {
            $image_src_output = 'src="'.esc_url($img['src']).'"'; 
        }
    } else {
        $image_src_output = 'src="'.esc_url($img['src']).'"'; 
    }
    $extras = '';
    if(is_array($extra)) {
    	foreach ($extra as $key => $value) {
    		$extras .= esc_attr($key).'="'.esc_attr($value).'" ';
    	}
    } else {
    	$extras = $extra;	
    }
    if(!empty($img['src']) && $schema == true) {
    	$paddingbtn = ($img['height']/$img['width']) * 100;
        $output = '<div itemprop="image" class="kt-gallery-intrinsic" itemscope itemtype="http://schema.org/ImageObject" style="padding-bottom:'.esc_attr($paddingbtn).'%;">';
        $output .='<div class="kt-gallery-intrinsic-inner">';
        $output .='<img '.$image_src_output.' width="'.esc_attr($img['width']).'" height="'.esc_attr($img['height']).'" '.$img['srcset'].' class="'.esc_attr($img['class']).'" itemprop="contentUrl" alt="'.esc_attr($img['alt']).'" '.$extras.'>';
        $output .= '<meta itemprop="url" content="'.esc_url($img['src']).'">';
        $output .= '<meta itemprop="width" content="'.esc_attr($img['width']).'px">';
        $output .= '<meta itemprop="height" content="'.esc_attr($img['height']).'px">';
        $output .= '</div>';
        $output .= '</div>';
      	return $output;

    } elseif(!empty($img['src'])) {
    	$paddingbtn = ($img['height']/$img['width']) * 100;
    	$output = '<div class="kt-gallery-intrinsic" style="padding-bottom:'.esc_attr($paddingbtn).'%;">';
    	$output .='<div class="kt-gallery-intrinsic-inner">';
        $output .= '<img '.$image_src_output.' width="'.esc_attr($img['width']).'" height="'.esc_attr($img['height']).'" '.$img['srcset'].' class="'.esc_attr($img['class']).'" alt="'.esc_attr($img['alt']).'" '.$extras.'>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    } else {
        return null;
    }
}
