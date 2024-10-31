<?php
/*
	Plugin Name: Product Reviews from rateit.cool for Woocommerce
	Description: 65% more sales with many product reviews for each product. Collect product reviews with all other shops using the ratetit.cool service.
	Author: Cool Services UG (haftungsbeschränkt)
	Version: 1.0.3
	Author URI: https://www.rateit.cool/
	Plugin URI: https://www.rateit.cool/howto/woocommerce-4/
 */
register_activation_hook(   __FILE__, 'wc_rateitcool_activation' );
register_uninstall_hook( __FILE__, 'wc_rateitcool_uninstall' );
register_deactivation_hook( __FILE__, 'wc_rateitcool_deactivate' );
add_action('plugins_loaded', 'wc_rateitcool_init');
add_action('init', 'wc_rateitcool_redirect');

function add_meta_tags() {
    global $post;
    if ( is_single() ) {
			$settings = get_option('rateitcool_settings',wc_rateitcool_get_default_settings());
      echo '<meta name="rateit-cool-site-verification" content="' . $settings['apikey'] . '" />' . "\n";
    }
}
add_action( 'wp_head', 'add_meta_tags' , 2 );

// email after order completed
//add_action( 'woocommerce_order_status_completed', 'wc_rateitcool_map');
function wc_rateitcool_init() {
	$is_admin = is_admin();
	if($is_admin) {
		// export existing reviews as csv
		if (isset($_GET['download_exported_reviews'])) {
			if(current_user_can('manage_options')) {
				require('classes/class-wc-rateitcool-export-reviews.php');
				$export = new Rateitcool_Review_Export();
				list($file, $errors) = $export->exportReviews();
				if(is_null($errors)) {
					$export->downloadReviewToBrowser($file);
				}
			}
			exit;
		}
		// template
		include( plugin_dir_path( __FILE__ ) . 'templates/wc-rateitcool-settings.php');
		include(plugin_dir_path( __FILE__ ) . 'lib/rateitcool-api/RateItCool.php');
		add_action( 'admin_menu', 'wc_rateitcool_admin_settings' );
	}
	$rateitcool_settings = get_option('rateitcool_settings', wc_rateitcool_get_default_settings());
	if(!empty($rateitcool_settings['username']) && wc_rateitcool_compatible()) {
		if(!$is_admin) {
			add_action( 'wp_enqueue_scripts', 'wc_rateitcool_load_js' );
			add_action( 'template_redirect', 'wc_rateitcool_front_end_init' );
		}
	}
}

function wc_rateitcool_redirect() {
	if ( get_option('wc_rateitcool_just_installed', false)) {
		delete_option('wc_rateitcool_just_installed');
		wp_redirect( ( ( is_ssl() || force_ssl_admin() || force_ssl_login() ) ? str_replace( 'http:', 'https:', admin_url( 'admin.php?page=woocommerce-rateitcool-settings-page' ) ) : str_replace( 'https:', 'http:', admin_url( 'admin.php?page=woocommerce-rateitcool-settings-page' ) ) ) );
		exit;
	}
}

function wc_rateitcool_admin_settings() {
	add_action( 'admin_enqueue_scripts', 'wc_rateitcool_admin_styles' );
	$page = add_menu_page( 'Product Reviews from rateit.cool', 'Product Reviews', 'manage_options', 'woocommerce-rateitcool-settings-page', 'wc_display_rateitcool_admin_page', plugins_url('rateitcool/assets/images/logo_small.png'), null );
}

add_action('woocommerce_order_details_after_order_table', 'wc_rateitcool_after_order_table' );
function wc_rateitcool_after_order_table($order) {
	if ($order->post_status === 'wc-completed') {
		$settings = get_option('rateitcool_settings',wc_rateitcool_get_default_settings());
		echo '<div class="rateit-cool-feedback-form"><a href="#" data-feedbackid="rateit-cool-shop-reviews">' . $settings['write_shop_review_link'] . ' </a></div>';
		echo '<div style="display:none;" id="rateit-cool-shop-reviews">';
		// shop review
		echo 	'<h3>' . $settings['write_shop_review_title'] . '</h3>' .
			'<p>' . $settings['write_shop_review_hint'] . '</p>' .
			'<form class="rate-it-cool-feedback-form" name="shopfeedbackform">' .
       			'<input type="hidden" name="gpntype" value="shop"/>' .
		       '<input type="hidden" name="gpnvalue" value="myshop"/>' .
		       '<input type="hidden" name="language" value="' . implode('_', explode('-', get_bloginfo('language'))) . '"/>' .
		       '<input type="hidden" name="stars" value="0"/> ' .
       	'<div class="ratings">' .
         '<div class="star-rating oneStars" title="' . $settings['1_stars_not_really_ok'] . '">' .
           '<span class="rate-it-cool-review-summary" style="width: 0;"></span>' .
         '</div>' .
         '<div class="star-rating twoStars" title="' . $settings['2_stars_hm_ok'] . '">' .
           '<span class="rate-it-cool-review-summary" style="width: 0;"></span>' .
         '</div>' .
         '<div class="star-rating threeStars" title="' . $settings['3_stars_ok'] . '">' .
           '<span class="rate-it-cool-review-summary" style="width: 0;"></span>' .
         '</div>' .
         '<div class="star-rating fourStars" title="' . $settings['4_stars_cool'] . '">' .
           '<span class="rate-it-cool-review-summary" style="width: 0;"></span>' .
         '</div>' .
         '<div class="star-rating fiveStars" title="' . $settings['5_stars_coolest'] . '">' .
           '<span class="rate-it-cool-review-summary" style="width: 0;"></span>' .
         '</div>' .
       '</div>' .
       '<span class="rate-it-cool-star-text"></span>' .
       '<div class="feedback-title">' .
         '<input type="text" style="padding: 10px 0 10px 5px; width: 99%" name="feedbackTitle" placeholder="' . $settings['placeholder_review_title'] . '" />' .
       '</div>' .
       '<div class="feedback-content">' .
         '<textarea name="feedbackContent" style="width: 98%;" placeholder="' . $settings['placeholder_review_content'] . '"></textarea>' .
       '</div>'.
       '<div class="feedback-recommend">' .
         '<input name="recommend" type="checkbox"> ' . $settings['review_recommend_a_friend'] .
       '</div>' .
       '<div class="rateit-cool-send-feedback">' .
         '<a href="#" class="button-middle small" data-formname="shopfeedbackform">' . $settings['review_send_link'] . '</a>' .
       '</div>' .
     '</form>' .
     '<div class="rateit-cool-send-feedback-success" style="display:none">' .
       $settings['thank_you_for_the_review'] .
     '</div>' .
     '<div class="rateit-cool-send-feedback-error" style="display:none">' .
       $settings['error_send_the_review'] .
     '</div>';

		echo '</div>';
	}
}

add_action('woocommerce_order_items_table', 'wc_rateitcool_order_items_table');
function wc_rateitcool_order_items_table ($order) {

	if ($order->post_status === 'wc-completed') {
		$settings = get_option('rateitcool_settings',wc_rateitcool_get_default_settings());
		echo '<tr><td colspan="2" class="rateit-cool-feedback-form"><a href="#" data-feedbackid="rateit-cool-product-reviews">' . $settings['write_product_review_link'] . ' </a></td></tr>';
		echo '<tr><td colspan="2" style="display:none;" id="rateit-cool-product-reviews"><p>' . $settings['write_product_review_hint'] . '</p>';
		// product reviews
		$numberOfProducts = count($order->get_items());
		foreach( $order->get_items() as $item_id => $item ) {
			$product = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
			$postData = $product->get_post_data();
			$product_data = wc_rateitcool_get_product_data($product);
			echo 	'<h3>' . $settings['write_product_review_title'] . ' "' . $postData->post_title . '"</h3>' .
						'<form class="rate-it-cool-feedback-form" name="productfeedbackform' . $product->id . '">' .
       			'<input type="hidden" name="gpntype" value="' . $product_data["gpntype"] . '"/>' .
		       '<input type="hidden" name="gpnvalue" value="' . $product_data["gpnvalue"] . '"/>' .
		       '<input type="hidden" name="language" value="' . $product_data['language'] . '"/>' .
		       '<input type="hidden" name="stars" value="0"/> ' .
       	'<div class="ratings">' .
         '<div class="star-rating oneStars" title="' . $settings['1_stars_not_really_ok'] . '">' .
           '<span class="rate-it-cool-review-summary" style="width: 0;"></span>' .
         '</div>' .
         '<div class="star-rating twoStars" title="' . $settings['2_stars_hm_ok'] . '">' .
           '<span class="rate-it-cool-review-summary" style="width: 0;"></span>' .
         '</div>' .
         '<div class="star-rating threeStars" title="' . $settings['3_stars_ok'] . '">' .
           '<span class="rate-it-cool-review-summary" style="width: 0;"></span>' .
         '</div>' .
         '<div class="star-rating fourStars" title="' . $settings['4_stars_cool'] . '">' .
           '<span class="rate-it-cool-review-summary" style="width: 0;"></span>' .
         '</div>' .
         '<div class="star-rating fiveStars" title="' . $settings['5_stars_coolest'] . '">' .
           '<span class="rate-it-cool-review-summary" style="width: 0;"></span>' .
         '</div>' .
       '</div>' .
       '<span class="rate-it-cool-star-text"></span>' .
       '<div class="feedback-title">' .
         '<input type="text" style="padding: 10px 0 10px 5px; width: 99%;" name="feedbackTitle" placeholder="' . $settings['placeholder_review_title'] . '" />' .
       '</div>' .
       '<div class="feedback-content">' .
         '<textarea name="feedbackContent" style="width: 98%;" placeholder="' . $settings['placeholder_review_content'] . '"></textarea>' .
       '</div>'.
       '<div class="feedback-recommend">' .
         '<input name="recommend" type="checkbox"> ' . $settings['review_recommend_a_friend'] .
       '</div>' .
       '<div class="rateit-cool-send-feedback">' .
         '<a href="#" class="button-middle small" data-formname="productfeedbackform' . $product->id . '">' . $settings['review_send_link'] . '</a>' .
       '</div>' .
     '</form>' .
     '<div class="rateit-cool-send-feedback-success" style="display:none">' .
       $settings['thank_you_for_the_review'] .
     '</div>' .
     '<div class="rateit-cool-send-feedback-error" style="display:none">' .
       $settings['error_send_the_review'] .
     '</div>';

		 if ($numberOfProducts > 1 && $item_id <= $numberOfProducts) {
			 echo '<hr>';
		 }
		}
		echo '</td></tr>';
	}
}

function wc_rateitcool_front_end_init() {
	$settings = get_option('rateitcool_settings',wc_rateitcool_get_default_settings());
	if(is_product()) {
		$widget_location = $settings['widget_location'];
		if($settings['disable_native_review_system']) {
			add_filter( 'comments_open', 'wc_rateitcool_remove_native_review_system', null, 2);
		}
		if($widget_location == 'footer') {
			add_action('woocommerce_after_single_product', 'wc_rateitcool_show_widget', 10);
		}
		elseif($widget_location == 'tab') {
			add_action('woocommerce_product_tabs', 'wc_rateitcool_show_widget_in_tab');
		}
		if($settings['bottom_line_enabled_product']) {
			add_action('woocommerce_single_product_summary', 'wc_rateitcool_show_detaiL_buttomline',7);
			wp_enqueue_style('rateitCoolSideBootomLineStylesheet', plugins_url('assets/css/bottom-line.css', __FILE__));
		}
	}
	elseif ($settings['bottom_line_enabled_category']) {
		add_action('woocommerce_after_shop_loop_item_title', 'wc_rateitcool_show_buttomline',7);
		wp_enqueue_style('rateitCoolBootomLineStylesheet', plugins_url('assets/css/bottom-line.css', __FILE__));
	}
}

function wc_rateitcool_activation() {
	if(current_user_can( 'activate_plugins' )) {
		update_option('wc_rateitcool_just_installed', true);
	    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    	check_admin_referer( "activate-plugin_{$plugin}" );
		$default_settings = get_option('rateitcool_settings', false);
		if(!is_array($default_settings)) {
			add_option('rateitcool_settings', wc_rateitcool_get_default_settings());
		}
		update_option('native_star_ratings_enabled', get_option('woocommerce_enable_review_rating'));
		update_option('woocommerce_enable_review_rating', 'no');
	}
}

function wc_rateitcool_uninstall() {
	if(current_user_can( 'activate_plugins' ) && __FILE__ == WP_UNINSTALL_PLUGIN ) {
		check_admin_referer( 'bulk-plugins' );
		delete_option('rateitcool_settings');
	}
}

function wc_rateitcool_show_widget() {

	$product = get_product();
	if($product->post->post_status == 'publish') {
		$product_data = wc_rateitcool_get_product_data($product);
		$postData = $product->get_post_data();
		$settings = get_option('rateitcool_settings',wc_rateitcool_get_default_settings());
		require_once(plugin_dir_path( __FILE__ ) . 'lib/rateitcool-api/RateItCool.php');
		$rateitcool_api = new RateItCool($settings);
		$rateitcool_reviews = $rateitcool_api->getReviews($product_data);

		$rateitcool_div = "<div class='rate-it-cool-product-feedbacks'
	   				data-gpntype='".$product_data['gpntype']."'
						data-gpnvalue='".$product_data['gpnvalue']."'
	   				data-language='".$product_data['language']."'>";

		if (isset($rateitcool_reviews['elements'])) {
			foreach ($rateitcool_reviews['elements'] as $review) {
				$rateitcool_div .= '<div itemscope itemtype="http://schema.org/Review">';
				$timeObj = new DateTime($review['time']);
				$rateitcool_div .= '<meta itemprop="datePublished" content="' . date_i18n( 'Y-m-d', $timeObj->getTimestamp() ) . '">' . date_i18n( get_option( 'date_format' ), $timeObj->getTimestamp() );
				$rateitcool_div .= '<div itemprop="itemReviewed" itemscope itemtype="http://schema.org/Product">';
				$rateitcool_div .= '<span itemprop="name">' . $postData->post_title . '</span>';
				$rateitcool_div .= '</div>';
				$rateitcool_div .= '<span itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">';
				$rateitcool_div .= '<span itemprop="ratingValue">' . $review['stars'] . '</span>';
				$rateitcool_div .= '<br/>Maximum Stars <span itemprop="bestRating">5</span>';
				$rateitcool_div .= '<br/>Minimum Stars <span itemprop="worstRating">1</span>';
				$rateitcool_div .= '</span>';
				$rateitcool_div .= '<br/>';
				$rateitcool_div .= '<h3><span itemprop="name">' . $review['title'] . '</span></h3>';
				$rateitcool_div .= '<span itemprop="reviewBody">' . $review['content'] . '</span>';
				$rateitcool_div .= '<div itemprop="publisher" itemscope itemtype="http://schema.org/Organization">';
				$rateitcool_div .= '<meta itemprop="name" content="">';
				$rateitcool_div .= '</div>';
				$rateitcool_div .= '</div>';
				$rateitcool_div .= '<hr/>';
			}
		}
		$rateitcool_div .= "</div>";
		echo $rateitcool_div;
		echo wc_rateitcool_get_feedback_template($product);

	}
}

function wc_rateitcool_show_qa_bottomline() {
    $product_data = wc_rateitcool_get_product_data(get_product());
    echo "<div class='yotpo QABottomLine'
         data-appkey='".$product_data['app_key']."'
         data-product-id='".$product_data['id']."'></div>";
}

function wc_rateitcool_show_buttomline() {
	$product = get_product();
	$show_bottom_line = is_product() ? $product->post->comment_status == 'open' : true;
	if($show_bottom_line) {
		$product_data = wc_rateitcool_get_product_data($product);
		$settings = get_option('rateitcool_settings',wc_rateitcool_get_default_settings());
		echo '<div class="rate-it-cool-product" ';
		$gpn = NULL;
		if (isset($settings['rateitcool_gpntype']) && $settings['rateitcool_gpntype'] != NULL) {
			$gpn = get_post_meta( $product->id, $settings['rateitcool_gpntype'], true );
		}
		if ($gpn == NULL) {
			echo ' data-gpntype="' . $settings['username'] . '"';
			echo ' data-gpnvalue="' . get_post_meta( $product->id, '_sku', true ) . '"';
      echo ' data-language="'. implode('_', explode('-', get_bloginfo('language'))) .'"';
		} else {
			echo ' data-gpntype="' . $settings['rateitcool_gpntype'] . '"';
			echo ' data-gpnvalue="' . $gpn .'"';
      echo ' data-language="'. implode('_', explode('-', get_bloginfo('language'))) .'"';
		}
		echo '><span class="star-rating"><span class="rate-it-cool-review-summary" style="width:0%"></span></span> (<span class="rate-it-cool-review-counts">0</span>)</div>';
	}

}

function wc_rateitcool_show_detail_buttomline() {
	$product = get_product();
	$show_bottom_line = is_product() ? $product->post->comment_status == 'open' : true;
	if($show_bottom_line) {
		$product_data = wc_rateitcool_get_product_data($product);
		$settings = get_option('rateitcool_settings',wc_rateitcool_get_default_settings());
		echo '<div class="rate-it-cool-product-detail" ';
		$gpn = NULL;
		if (isset($settings['rateitcool_gpntype']) && $settings['rateitcool_gpntype'] != NULL) {
			$gpn = get_post_meta( $product->id, $settings['rateitcool_gpntype'], true );
		}
		if ($gpn == NULL) {
			echo ' data-gpntype="' . $settings['username'] . '"';
			echo ' data-gpnvalue="' . get_post_meta( $product->id, '_sku', true ) . '"';
      echo ' data-language="'. implode('_', explode('-', get_bloginfo('language'))) .'"';
		} else {
			echo ' data-gpntype="' . $settings['rateitcool_gpntype'] . '"';
			echo ' data-gpnvalue="' . $gpn .'"';
      echo ' data-language="'. implode('_', explode('-', get_bloginfo('language'))) .'"';
		}
		echo '><span class="star-rating"><span class="rate-it-cool-review-summary" style="width:0%"></span></span> (<span class="rate-it-cool-review-counts">0</span>)';
    echo '<span class="rate-it-cool-show-stars" style="display:none;">+</span>';
    echo '</div>';
    echo '<div class="ratings rate-it-cool-stars-detail-table" style="display: none;">' .
          '<table style="width:100%;">' .
    '<tbody>' .
      '<tr>' .
        '<th colspan="2">' . $settings['review_overview_title'] . '</th>' .
      '</tr>' .
    '</tbody>' .
    '<tr>' .
      '<td class="stars">'.
        '<div class="star-rating">'.
          '<span class="rating" style="width: 100%;"></span>'.
        '</div>'.
      '</td>'.
      '<td>$review.star5</td>'.
    '</tr>'.
    '<tr>'.
      '<td class="stars">'.
        '<div class="star-rating">'.
          '<span class="rating" style="width: 75%;"></span>'.
        '</div>'.
      '</td>'.
      '<td>$review.star4</td>'.
    '</tr>'.
    '<tr>'.
      '<td class="stars">'.
        '<div class="star-rating">'.
          '<span class="rating" style="width: 55%;"></span>'.
        '</div>'.
      '</td>'.
      '<td>$review.star3</td>'.
    '</tr>'.
    '<tr>'.
      '<td class="stars">'.
        '<div class="star-rating">'.
          '<span class="rating" style="width: 35%;"></span>'.
        '</div>'.
      '</td>'.
      '<td>$review.star2</td>'.
    '</tr>'.
    '<tr>'.
      '<td class="stars">'.
        '<div class="star-rating">'.
          '<span class="rating" style="width: 20%;"></span>'.
        '</div>'.
      '</td>'.
      '<td>$review.star1</td>'.
    '</tr>'.
  '</table>'.
  '<table style="width:100%;$details.display">'.
    '<tbody>'.
      '<tr>'.
        '<th colspan="2">' . $settings['review_detail_title'] . ' ($details.total/$review.total)</th>'.
      '</tr>'.
    '</tbody>'.
    '<tr style="$details.detail1.display">'.
      '<td>$details.detail1.title</td>'.
      '<td class="stars">'.
        '<div class="star-rating">'.
          '<div class="rating" style="width: $review.details.detail1%;"></div>'.
        '</div>'.
      '</td>'.
    '</tr>'.
    '<tr style="$details.detail2.display">'.
      '<td>$details.detail2.title</td>'.
      '<td class="stars">'.
        '<div class="star-rating">'.
          '<div class="rating" style="width: $review.details.detail2%;"></div>'.
        '</div>'.
      '</td>'.
    '</tr>'.
    '<tr style="$details.detail3.display">'.
      '<td>$details.detail3.title</td>'.
      '<td class="stars">'.
        '<div class="star-rating">'.
          '<div class="rating" style="width: $review.details.detail3%;"></div>'.
        '</div>'.
      '</td>'.
    '</tr>'.
    '<tr style="$details.detail4.display">'.
      '<td>$details.detail4.title</td>'.
      '<td class="stars">'.
        '<div class="star-rating">'.
          '<div class="rating" style="width: $review.details.detail4%;"></div>'.
        '</div>'.
      '</td>'.
    '</tr>'.
  '</table>'.
'</div>';
	}

}

function wc_rateitcool_show_widget_in_tab($tabs) {
	$product = get_product();
	if($product->post->comment_status == 'open') {
		$settings = get_option('rateitcool_settings', wc_rateitcool_get_default_settings());
	 	$tabs['rateitcool_widget'] = array(
	 	'title' => $settings['widget_tab_name'],
	 	'priority' => 50,
	 	'callback' => 'wc_rateitcool_show_widget'
	 	);
	}
 	return $tabs;
}

function wc_rateitcool_load_js(){

	if(wc_rateitcool_is_who_commerce_installed()) {
		wp_enqueue_script('rateitcoolJs', plugins_url('assets/js/rateit.cool.js', __FILE__) ,null,null);
		wp_enqueue_style('rateitcoolCss', plugins_url('assets/css/rate-it-cool.css', __FILE__) ,null,null);
		$settings = get_option('rateitcool_settings',wc_rateitcool_get_default_settings());
		wp_localize_script('rateitcoolJs', 'rateitcool_settings', $settings);
	}

}

function wc_rateitcool_is_who_commerce_installed() {
	return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
}

function wc_rateitcool_get_product_data($product) {
	$product_data = array();
	$settings = get_option('rateitcool_settings',wc_rateitcool_get_default_settings());
	$product_data['username'] = $settings['username'];
	$gpn = NULL;
	if (isset($settings['rateitcool_gpntype']) && $settings['rateitcool_gpntype'] != NULL) {
		$gpn = get_post_meta($product->id, $settings['rateitcool_gpntype'], true );
	}
 	if ($gpn == NULL) {
		$product_data['gpntype'] = $settings['username'];
		$product_data['gpnvalue'] = $product->get_sku();
	} else {
		$product_data['gpntype'] = $settings['rateitcool_gpntype'];
		$product_data['gpnvalue'] = $gpn;
	}
	$product_data['language'] = implode('_', explode('-', get_bloginfo('language')));
	if($settings['rateitcool_language_as_site'] == true) {
		$product_data['language'] = implode('_', explode('-', get_bloginfo('language')));
	}
	return $product_data;
}

function wc_rateitcool_remove_native_review_system($open, $post_id) {
	if(get_post_type($post_id) == 'product') {
		return false;
	}
	return $open;
}

function wc_rateitcool_get_default_settings() {
	return array( 'username' => '',
	  'apikey' => '',
		'serverapikey' => '',
    'securitytext' => '',
	  'widget_location' => 'tab',
	  'language_code' => implode('_', explode('-', get_bloginfo('language'))),
	  'widget_tab_name' => 'Reviews',
	  'bottom_line_enabled_product' => true,
	  'bottom_line_enabled_category' => true,
	  'rateitcool_language_as_site' => true,
		'rateitcool_gpntype' => 'gtin',
	  'disable_native_review_system' => true,
	  'native_star_ratings_enabled' => 'no',
    'write_a_product_review' => 'Write a product review',
		'translation_review_template_title' => 'Reviews',
    'review_overview_title' => 'Overview',
    'review_detail_title' => 'Detail',
		'show_only_five_stars' => 'Show only reviews with 5 stars',
		'show_only_four_stars' => 'Show only reviews with 4 stars',
		'show_only_three_stars' => 'Show only reviews with 3 stars',
		'show_only_two_stars' => 'Show only reviews with 2 stars',
		'show_only_one_stars' => 'Show only reviews with 1 star',
		'customer_recommend_this' => 'Customer recommended this product',
		'show_the_latest_reviews_first' => 'Show the latest reviews first',
		'show_the_oldest_reviews_first' => 'Show the oldest reviews first',
		'show_the_best_reviews_first' => 'Show best reviews first',
		'show_the_worst_reviews_first' => 'Show the worst reviews first',
		'show_the_best_helpful_reviews_first' => 'Show the best helpful reviews first',
		'show_the_worst_helpful_reviews_first' => 'Show the worst helpful reviews first',
		'show_more_reviews_text' => 'Show more reviews...',
		'is_the_review_helpful_for_you' => 'Is the review helpful for you?',
		'yes_helpful_text' => 'Yes',
		'no_helpful_text' => 'No',
		'report_this_review' => 'Report this review.',
    'verified' => 'Verified Customer',
    'public' => 'Public Review',
    'mobile' => 'Mobile App Review',
		'write_product_review_title' => 'Product Review for',
		'write_product_review_link' => 'Write Product reviews',
		'write_product_review_hint' => 'A product review contains the voting about the product itself. It contains not the voting about the shop, shipping and other things.',
		'1_stars_not_really_ok' => '1_stars_not_really_ok',
		'2_stars_hm_ok' => '2_stars_hm_ok',
		'3_stars_ok' => '3_stars_ok',
		'4_stars_cool' => '4_stars_cool',
		'5_stars_coolest' => '5_stars_coolest',
    'review_global' => 'Global',
		'placeholder_review_title' => 'Title of the review',
		'placeholder_review_content' => 'Content of the review',
		'review_recommend_a_friend' => 'Recommend it to a friend',
		'review_send_link' => 'Send the review',
		'thank_you_for_the_review' => 'Thank you for the review.',
		'error_send_the_review' => 'There was a problem while sending your review to the shop',
		'write_shop_review_link' => 'Write a shop review',
		'write_shop_review_title' => 'Review for this shop',
		'write_shop_review_hint' => 'A shop review contains the voting about the shop itself, shipping and other things.',
    'please_fill_in_the_fellowing_text' => 'Please fill in the fellowing text',
	);
}

function wc_rateitcool_admin_styles($hook) {
	if($hook == 'toplevel_page_woocommerce-rateitcool-settings-page') {
		wp_enqueue_script( 'rateitcoolSettingsJs', plugins_url('assets/js/settings.js', __FILE__), array('jquery-effects-core'));
		wp_enqueue_style( 'rateitcoolSettingsStylesheet', plugins_url('assets/css/rateit-cool.admin.css', __FILE__));
	}
}

function wc_rateitcool_compatible() {
	return version_compare(phpversion(), '5.2.0') >= 0 && function_exists('curl_init');
}

function wc_rateitcool_deactivate() {
	update_option('woocommerce_enable_review_rating', get_option('native_star_ratings_enabled'));
}

add_filter('woocommerce_tab_manager_integration_tab_allowed', 'wc_rateitcool_disable_tab_manager_managment');

function wc_rateitcool_disable_tab_manager_managment($allowed, $tab = null) {
	if($tab == 'rateitcool_widget') {
		$allowed = false;
		return false;
	}
}

function wc_rateitcool_get_feedback_template($product) {
	$settings = get_option('rateitcool_settings',wc_rateitcool_get_default_settings());
	$template = '<div id="rate-it-cool-product-feedbacks-template" style="display:none;">' .
	  '<div class="feedbackElements">' .
	    '<div class="overview">' .
	      '<h3>' . $settings['translation_review_template_title'] . '</h3>'.
	      '<table class="feedbackOverview">' .
	        '<tr>' .
	          '<td class="ratings">' .
	            '<div class="star-rating">' .
	              '<span style="width:100%;"></span>' .
	            '</div>' .
	          '</td>' .
	          '<td class="count">' .
	            '<a class="showOnlyStars" data-stars="5" href="#" title="' . $settings['show_only_five_stars'] . '">' .
	              '$five' .
	            '</a>'.
	          '</td>' .
	        '</tr>' .
	        '<tr>' .
	          '<td class="ratings">' .
	            '<div class="star-rating">' .
	              '<span style="width:80%;"></span>' .
	            '</div>' .
	          '</td>' .
	          '<td class="count">' .
	            '<a class="showOnlyStars" data-stars="4" href="#" title="' . $settings['show_only_four_stars'] . '">' .
	              '$four' .
	            '</a>' .
	          '</td>' .
	        '</tr>' .
	        '<tr>' .
	          '<td class="ratings">' .
	            '<div class="star-rating">' .
	              '<span style="width:60%;"></span>' .
	            '</div>' .
	          '</td>' .
	          '<td class="count">' .
	            '<a class="showOnlyStars" data-stars="3" href="#" title="' . $settings['show_only_three_stars'] . '">' .
	              '$three' .
	            '</a>' .
	          '</td>' .
	        '</tr>' .
	        '<tr>' .
	          '<td class="ratings">' .
	            '<div class="star-rating">' .
	              '<span style="width:40%;"></span>' .
	            '</div>' .
	          '</td>' .
	          '<td class="count">' .
	            '<a class="showOnlyStars" data-stars="2" href="#" title="' . $settings['show_only_two_stars'] . '">' .
	              '$two' .
	            '</a>' .
	          '</td>' .
	        '</tr>' .
	        '<tr>' .
	          '<td class="ratings">' .
	            '<div class="star-rating">' .
	              '<span style="width:20%;"></span>' .
	            '</div>' .
	          '</td>' .
	          '<td class="count">' .
	            '<a class="showOnlyStars" data-stars="1" href="#" title="' . $settings['show_only_one_stars'] . '">' .
	              '$one' .
	            '</a>' .
	          '</td>' .
	        '</tr>' .
	      '</table>' .
        '<table class="rate-it-cool-detail-stars" style="$detail.show">' .
        '<thead>' .
          '<tr>' .
            '<th colspan="2">' . $settings['review_detail_title'] . ' ($details.total / $overview.total)</th>' .
          '</tr>' .
        '</thead>' .
        '<tr style="$details.detail1.display">' .
          '<td>$details.detail1.title</td>' .
          '<td class="ratings">' .
            '<div class="star-rating">' .
              '<span class="rating" style="width:$detail.detail1%;"></span>' .
            '</div>'.
          '</td>'.
        '</tr>'.
        '<tr style="$details.detail2.display">'.
          '<td>$details.detail2.title</td>'.
          '<td class="ratings">'.
            '<div class="star-rating">'.
              '<span class="rating" style="width:$detail.detail2%;"></span>'.
            '</div>'.
          '</td>'.
        '</tr>'.
        '<tr style="$details.detail3.display">'.
          '<td>$details.detail3.title</td>'.
          '<td class="ratings">'.
            '<div class="star-rating">'.
              '<span class="rating" style="width:$detail.detail3%;"></span>'.
            '</div>'.
          '</td>'.
        '</tr>'.
        '<tr style="$details.detail4.display">'.
          '<td>$details.detail4.title</td>'.
          '<td class="ratings">'.
            '<div class="star-rating">'.
              '<span class="rating" style="width:$detail.detail4%;"></span>'.
            '</div>'.
          '</td>'.
        '</tr>'.
      '</table>'.
      '<div class="clearfix"></div>'.
	    '</div><hr/>' .
      '<div class="rate-it-cool-product-public-feedbackform">'.
      '<div class="rate-it-cool-product-feedbackform">'.
        '<a href="#" '.
        'class="rateit-cool-show-feedbackform-link" '.
        'title="'. $settings['write_a_product_review'] . '">'. $settings['write_a_product_review'] . '</a>'.
      '</div><hr/>'.
      '<div id="feedbackform" class="rate-it-cool-feedback-form" style="display:none;">'.
        '<h3>'. $settings['write_product_review_title'] . ' ' . $product->post->post_title . '</h3>' .
          '<form name="productDetailFeedbackform$gpnvalue">'.
            '<input type="hidden" name="gpntype" value="$gpntype"/>'.
            '<input type="hidden" name="gpnvalue" value="$gpnvalue"/>'.
            '<input type="hidden" name="source" value="public"/>'.
            '<input type="hidden" name="language" value="' . implode('_', explode('-', get_bloginfo('language'))). '"/>'.
            '<table>'.
              '<tr>'.
                '<td class="label second">' . $settings['review_global'] . '</td>'.
                '<td class="ratings">'.
                  '<span class="reviewStars">'.
                    '<input type="hidden" class="stars" name="stars" value="0"/>'.
                    '<div class="star-rating oneStars" title="' . $settings['1_stars_not_really_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating twoStars" title="' . $settings['2_stars_hm_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating threeStars" title="' . $settings['3_stars_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating fourStars" title="' . $settings['4_stars_cool'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating fiveStars" title="' . $settings['5_stars_coolest'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                  '</div>'.
                  '</span>'.
                '</td>'.
              '</tr>'.
              '<tr class="reviewDetail1" style="display:none;">'.
                '<td class="label"></td>'.
                '<td class="ratings">'.
                  '<span class="reviewStars">'.
                    '<input type="hidden" class="stars" name="detail1" value="0"/>'.
                    '<div class="star-rating oneStars" title="' . $settings['1_stars_not_really_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating twoStars" title="' . $settings['2_stars_hm_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating threeStars" title="' . $settings['3_stars_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating fourStars" title="' . $settings['4_stars_cool'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating fiveStars" title="' . $settings['5_stars_coolest'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                  '</div>'.
                  '</span>'.
                '</td>'.
              '</tr>'.
              '<tr class="reviewDetail2" style="display:none;">'.
                '<td class="label second"></td>'.
                '<td class="ratings">'.
                  '<span class="reviewStars">'.
                    '<input type="hidden" class="stars" name="detail2" value="0"/>'.
                    '<div class="star-rating oneStars" title="' . $settings['1_stars_not_really_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating twoStars" title="' . $settings['2_stars_hm_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating threeStars" title="' . $settings['3_stars_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating fourStars" title="' . $settings['4_stars_cool'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating fiveStars" title="' . $settings['5_stars_coolest'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                  '</div>'.
                  '</span>'.
                '</td>'.
              '</tr>'.
              '<tr class="reviewDetail3" style="display:none;">'.
                '<td class="label"></td>'.
                '<td class="ratings">'.
                  '<span class="reviewStars">'.
                    '<input type="hidden" class="stars" name="detail3" value="0"/>'.
                    '<div class="star-rating oneStars" title="' . $settings['1_stars_not_really_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating twoStars" title="' . $settings['2_stars_hm_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating threeStars" title="' . $settings['3_stars_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating fourStars" title="' . $settings['4_stars_cool'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating fiveStars" title="' . $settings['5_stars_coolest'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                  '</div>'.
                  '</span>'.
                '</td>'.
              '</tr>'.
              '<tr class="reviewDetail4" style="display:none;">'.
                '<td class="label second"></td>'.
                '<td class="ratings">'.
                  '<span class="reviewStars">'.
                    '<input type="hidden" class="stars" name="detail4" value="0"/>'.
                    '<div class="star-rating oneStars" title="' . $settings['1_stars_not_really_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating twoStars" title="' . $settings['2_stars_hm_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating threeStars" title="' . $settings['3_stars_ok'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating fourStars" title="' . $settings['4_stars_cool'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                    '<div class="star-rating fiveStars" title="' . $settings['5_stars_coolest'] . '">'.
                      '<span class="rating rate-it-cool-review-summary-empty rate-it-cool-review-summary"></span>'.
                    '</div>'.
                  '</div>'.
                  '</span>'.
                '</td>'.
              '</tr>'.
            '</table>'.
            '<br/>'.
            '<div class="feedback-title">'.
              '<input type="text" name="feedbackTitle" placeholder="' . $settings['placeholder_review_title'] . '" />'.
            '</div>'.
            '<div class="feedback-content">'.
              '<textarea name="feedbackContent" placeholder="' . $settings['placeholder_review_content'] . '"></textarea>'.
            '</div>'.
            '<div class="feedback-recommend">'.
              '<input name="recommend" type="checkbox"> ' . $settings['review_recommend_a_friend'] .
            '</div>'.
            '<br/>'.
            '<div class="feedback-title">'.
              $settings['please_fill_in_the_fellowing_text'] . ': <b>' . $settings['securitytext'] . '</b>'.
              '<br/>'.
              '<input name="securityText" type="text" placeholder="">'.
            '</div>'.
            '<br/>'.
            '<div class="rateit-cool-send-feedback">'.
              '<a href="#" data-formname="productDetailFeedbackform$gpnvalue" class="button rateit-cool-send-produkt-feedback">' . $settings['review_send_link'] . '</a>'.
            '</div>'.
          '</form>'.
          '<div class="rateit-cool-send-feedback-success" style="display:none">'.
             $settings['thank_you_for_the_review'] .
          '</div>'.
          '<div class="rateit-cool-send-feedback-error" style="display:none">'.
            $settings['error_send_the_review'] .
          '</div><hr/>'.
      '</div>'.
    '</div>'.
	    '<div class="recommend">' .
	      $settings['customer_recommend_this'] . ': $recommend%' .
	      '<span style="float:right;">' .
	        '<select data-extraParameter="" name="reorderReviews">' .
	          '<option value="time;desc">' . $settings['show_the_latest_reviews_first'] . '</option>' .
	          '<option value="time;asc">' . $settings['show_the_oldest_reviews_first'] . '</option>' .
	          '<option value="stars;desc">' . $settings['show_the_best_reviews_first'] . '</option>' .
	          '<option value="stars;asc">' . $settings['show_the_worst_reviews_first'] . '</option>' .
	          '<option value="helpful;desc">' . $settings['show_the_best_helpful_reviews_first'] . '</option>' .
	          '<option value="helpful;asc">' . $settings['show_the_worst_helpful_reviews_first'] . '</option>' .
	        '</select>' .
	      '</span>' .
	    '</div><hr/>' .
	    '<dl class="list">$list</dl>' .
	    '<div class="showmoreContainer">' .
	      '<div class="showmore" style="display: $showNewtLink;">' .
	        '<a href="#" class="showMoreFeedbacks" data-extraParameter="" data-count="$count" data-language="$language" data-gpntype="$gpntype" data-gpnvalue="$gpnvalue">' . $settings['show_more_reviews_text'] . '</a>' .
	      '</div>' .
	    '</div>' .
	  '</div>' .
	  '<dl class="feedbackElement" style="display:none;">' .
	    '<dt>' .
	      '<div class="star-rating">' .
          '<span style="width:$review.stars%;"></span>' .
	      '</div>' .
	      '<span class="date">$review.time</span>' .
        '<br />'.
        '<span class="rate-it-cool-verified" style="$review.verified_source">' . $settings['verified'] . '</span>'.
        '<h3>$review.title</h3>' .
      '<span class="rate-it-cool-public" style="$review.public_source">' . $settings['public'] . '</span>'.
      '<span class="rate-it-cool-mobile" style="$review.mobile_source">' . $settings['mobile'] . '</span>'.
	    '</dt>' .
	    '<dd>' .
	      '$review.content' .
	      '<div class="helpful">' .
	        $settings['is_the_review_helpful_for_you'] .
	          '<span class="positive" data-positive="$review.positive" data-language="$review.language" data-gpntype="$review.gpntype" data-gpnvalue="$review.gpnvalue" data-feedbackid="$review.id">' .
	            $settings['yes_helpful_text'] . ' (<span class="positiveValue">$review.positive</span>)' .
	          '</span> |' .
	          '<span class="negative" data-negative="$review.negative"  data-language="$review.language" data-gpntype="$review.gpntype" data-gpnvalue="$review.gpnvalue" data-feedbackid="$review.id">' .
	            $settings['no_helpful_text'] . ' (<span class="negativeValue">$review.negative</span>)' .
	          '</span>' .
	      '</p>' .
	      '<p class="incorrect">' .
	        '<span class="report" data-language="$review.language" data-gpntype="$review.gpntype" data-gpnvalue="$review.gpnvalue" data-feedbackid="$review.id">' . $settings['report_this_review'] . '</span>'.
	      '</p>'.
	    '</dd>' .
	  '</dl>' .
	'</div>';
	return $template;
}
