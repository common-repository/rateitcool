<?php
function wc_display_rateitcool_admin_page() {

	if ( function_exists('current_user_can') && !current_user_can('manage_options') ) {
		die(__(''));
	}
	if(wc_rateitcool_compatible()) {
		if (isset($_POST['rateitcool_settings'])) {
			check_admin_referer( 'rateitcool_settings_form' );
			wc_proccess_rateitcool_settings();
			wc_display_rateitcool_settings();
		}
		else {
			$rateitcool_settings = get_option('rateitcool_settings', wc_rateitcool_get_default_settings());
			wc_display_rateitcool_settings();
		}
	}
	else {
		if(version_compare(phpversion(), '5.2.0') < 0) {
			echo '<h1>RateItCool plugin requires PHP 5.2.0 above.</h1><br>';
		}
		if(!function_exists('curl_init')) {
			echo '<h1>RateItCool plugin requires cURL library.</h1><br>';
		}
	}
}

//@done reviewed
function wc_display_rateitcool_settings($success_type = false) {
	$rateitcool_settings = get_option('rateitcool_settings', wc_rateitcool_get_default_settings());
	$username = $rateitcool_settings['username'];
	$apikey = $rateitcool_settings['apikey'];
	$serverapikey = $rateitcool_settings['serverapikey'];
	$securitytext = $rateitcool_settings['securitytext'];

	$language_code = $rateitcool_settings['language_code'];

	$widget_tab_name = $rateitcool_settings['widget_tab_name'];

	if(empty($rateitcool_settings['username']) || empty($rateitcool_settings['apikey']) || empty($rateitcool_settings['serverapikey'])) {
    wc_rateitcool_display_message('Set your username, apikey and serverapikey in order the rateit.cool plugin to work correctly', '');
		wc_rateitcool_display_message('If you not registered at rateit.cool yet, here is the <a href="https://www.rateit.cool/register/en?campaign=55f32da4a2eadd0cd85c1421">registration link</a>', 'warning');
	}

	$settings_html =
		"<div class='wrap'>" .
		   "<h2><img src='" . plugins_url('../assets/images/logo.png', __FILE__) . "'> Product Reviews from <b>rate<span style='color:#FFC107;'>it</span>.cool</b> - Settings</h2>
			  <form  method='post' id='rateitcool_settings_form'>
			  	<table class='form-table rateit-cool-settings-table'>".
			  		wp_nonce_field('rateitcool_settings_form').
			  	  "<fieldset>
								<tr>
									<th colspan='2'><h3>User Settings</h3></th>
								</tr>
								<tr valign='top'>
								 <th scope='row'><div>Username:</div></th>
								 <td><input id='username' type='text' name='rateitcool_username' value='$username'  /></td>
							 </tr>
							 <tr valign='top'>
								 <th scope='row'><div>Apikey:</div></th>
								 <td><input id='apikey' type='text'  name='rateitcool_apikey' value='$apikey'  /></td>
							 </tr>
							 <tr valign='top'>
								 <th scope='row'><div>Serverapikey:</div></th>
								 <td><input id='serverapikey' type='text'  name='rateitcool_serverapikey' value='$serverapikey'  /></td>
							 </tr>
							 <tr valign='top'>
								 <th scope='row'><div>Securitytext:</div></th>
								 <td><input id='serverapikey' type='text'  name='rateitcool_securitytext' value='$securitytext'  /></td>
							 </tr>
							 </fieldset>
							 <fieldset>
							 <tr>
								 <th colspan='2'><hr><h3>Api Parameters</h3></th>
							 </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Type of the Global Product Number (f.e. gtin):</div></th>
		   		       <td><input type='text' name='rateitcool_gpntype' value='" . $rateitcool_settings['rateitcool_gpntype'] . "' placeholder='GTIN' /></td>
		   		     </tr>
               <tr valign='top'>
               	 <th scope='row'><div>If you would like to choose a set language, please type the 4-letter ISO-Code (f.e. en_US) here.</div></th>
               	 <td><div><input type='text' class='rateitcool_language_code_text' name='rateitcool_widget_language_code' maxlength='5' value='$language_code'/></div></td>
               </tr>
							 <tr valign='top'>
		             	<th scope='row'><div>For multiple-language sites, mark this check box. This will choose the language according to the user's site language.</div></th>
                 	<td><input type='checkbox' name='rateitcool_language_as_site' value='1' ".checked(1, $rateitcool_settings['rateitcool_language_as_site'], false)."/></td>
               </tr>

							 </fieldset>
							 <fieldset>
							 <tr>
								 <th colspan='2'><hr><h3>Where to display</h3></th>
							 </tr>

					 		 <tr valign='top'>
		   		       <th scope='row'><div>Disable native reviews system:</div></th>
		   		       <td><input type='checkbox' name='disable_native_review_system' value='1' ".checked(1, $rateitcool_settings['disable_native_review_system'], false)." /></td>
		   		     </tr>

							 <tr valign='top'>
		   		       <th scope='row'><div>Show the stars at the name of the product detail page:</div></th>
		   		       <td><input type='checkbox' name='rateitcool_bottom_line_enabled_product' value='1' ".checked(1, $rateitcool_settings['bottom_line_enabled_product'], false)." /></td>
		   		     </tr>
					 	 	 <tr valign='top'>
		   		       <th scope='row'><div>Show the stars at the name of the product lists:</div></th>
		   		       <td><input type='checkbox' name='rateitcool_bottom_line_enabled_category' value='1' ".checked(1, $rateitcool_settings['bottom_line_enabled_category'], false)." />
		   		       </td>
		   		     </tr>
							 <tr valign='top'>
							 <th scope='row'><div>Select widget location</div></th>
							 <td>
							 	<select name='rateitcool_widget_location' class='rateitcool-widget-location'>
							 		<option value='footer' ".selected('footer',$rateitcool_settings['widget_location'], false).">Page footer</option>
							 		<option value='tab' ".selected('tab',$rateitcool_settings['widget_location'], false).">Tab</option>
							 		<option value='other' ".selected('other',$rateitcool_settings['widget_location'], false).">Other</option>
							 	</select>
							 	</td>
							 </tr>
							 <tr valign='top' class='rateitcool-widget-location-other-explain'>
							  <th scope='row'><p class='description'>In order to locate the widget in a custome location open 'wp-content/plugins/woocommerce/templates/content-single-product.php' and add the following line <code>wc_rateitcool_show_widget();</code> in the requested location.</p></th>
							 </tr>
							 <tr valign='top' class='rateitcool-widget-tab-name'>
							 	<th scope='row'><div>Select tab name:</div></th>
							 	<td><div><input type='text' name='rateitcool_widget_tab_name' value='$widget_tab_name' /></div></td>
							 </tr>
		           </fieldset>
							 <fieldset>
							 <tr>
							 	<td colspan='2'><hr></td>
							 </tr>
							 <tr>
							 	<td colspan='2'><h3>Template Text Translations</h3></td>
							 </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Review template title:</div></th>
		   		       <td><input type='text' name='translation_review_template_title' value='" . $rateitcool_settings['translation_review_template_title'] . "' /></td>
		   		     </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Title of Overview table:</div></th>
		   		       <td><input type='text' name='review_overview_title' value='" . $rateitcool_settings['review_overview_title'] . "' /></td>
		   		     </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Title of Detail table:</div></th>
		   		       <td><input type='text' name='review_detail_title' value='" . $rateitcool_settings['review_detail_title'] . "' /></td>
		   		     </tr>

							 <tr valign='top'>
							 	<th scope='row'><div>Label for 'verified customer':</div></th>
							 	<td><input type='text' name='verified' value='" . $rateitcool_settings['verified'] . "' /></td>
							 </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Label for 'public review':</div></th>
		   		       <td><input type='text' name='public' value='" . $rateitcool_settings['public'] . "' /></td>
		   		     </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Label for 'mobile app review':</div></th>
		   		       <td><input type='text' name='mobile' value='" . $rateitcool_settings['mobile'] . "' /></td>
		   		     </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Show only five stars:</div></th>
		   		       <td><input type='text' name='show_only_five_stars' value='" . $rateitcool_settings['show_only_five_stars'] . "' /></td>
		   		     </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Show only four stars:</div></th>
		   		       <td><input type='text' name='show_only_four_stars' value='" . $rateitcool_settings['show_only_four_stars'] . "' /></td>
		   		     </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Show only three stars:</div></th>
		   		       <td><input type='text' name='show_only_three_stars' value='" . $rateitcool_settings['show_only_three_stars'] . "' /></td>
		   		     </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Show only two stars:</div></th>
		   		       <td><input type='text' name='show_only_two_stars' value='" . $rateitcool_settings['show_only_two_stars'] . "' /></td>
		   		     </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Show only two stars:</div></th>
		   		       <td><input type='text' name='show_only_one_stars' value='" . $rateitcool_settings['show_only_one_stars'] . "' /></td>
		   		     </tr>
							 <tr valign='top'>
		   		       <th scope='row'><div>Customer recommend this:</div></th>
		   		       <td><input type='text' name='customer_recommend_this' value='" . $rateitcool_settings['customer_recommend_this'] . "' /></td>
		   		     </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Show the latest reviews first:</div></th>
							 	<td><input type='text' name='show_the_latest_reviews_first' value='" . $rateitcool_settings['show_the_latest_reviews_first'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Show the oldest reviews first:</div></th>
							 	<td><input type='text' name='show_the_oldest_reviews_first' value='" . $rateitcool_settings['show_the_oldest_reviews_first'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Show the best reviews first:</div></th>
							 	<td><input type='text' name='show_the_best_reviews_first' value='" . $rateitcool_settings['show_the_best_reviews_first'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Show the worst reviews first:</div></th>
							 	<td><input type='text' name='show_the_worst_reviews_first' value='" . $rateitcool_settings['show_the_worst_reviews_first'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Show the best helpful reviews first:</div></th>
							 	<td><input type='text' name='show_the_best_helpful_reviews_first' value='" . $rateitcool_settings['show_the_best_helpful_reviews_first'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Show the worst helpful reviews first:</div></th>
							 	<td><input type='text' name='show_the_worst_helpful_reviews_first' value='" . $rateitcool_settings['show_the_worst_helpful_reviews_first'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Show more reviews text:</div></th>
							 	<td><input type='text' name='show_more_reviews_text' value='" . $rateitcool_settings['show_more_reviews_text'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Is the review helpful for you:</div></th>
							 	<td><input type='text' name='is_the_review_helpful_for_you' value='" . $rateitcool_settings['is_the_review_helpful_for_you'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Yes helpful text:</div></th>
							 	<td><input type='text' name='yes_helpful_text' value='" . $rateitcool_settings['yes_helpful_text'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>No helpful text:</div></th>
							 	<td><input type='text' name='no_helpful_text' value='" . $rateitcool_settings['no_helpful_text'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Report this review:</div></th>
							 	<td><input type='text' name='report_this_review' value='" . $rateitcool_settings['report_this_review'] . "' /></td>
							 </tr>
							 <tr valign='top'>
 							 <th scope='row'><div>Write product review title (public view):</div></th>
 							 <td><input type='text' name='write_a_product_review' value='" . $rateitcool_settings['write_a_product_review'] . "' /></td>
 							</tr>
							<tr valign='top'>
							 <th scope='row'><div>Write product review title:</div></th>
							 <td><input type='text' name='write_product_review_title' value='" . $rateitcool_settings['write_product_review_title'] . "' /></td>
							</tr>
							<tr valign='top'>
							 <th scope='row'><div>Write product review link:</div></th>
							 <td><input type='text' name='write_product_review_link' value='" . $rateitcool_settings['write_product_review_link'] . "' /></td>
							</tr>
							<tr valign='top'>
							 <th scope='row'><div>Write product review hint:</div></th>
							 <td><input type='text' name='write_product_review_hint' value='" . $rateitcool_settings['write_product_review_hint'] . "' /></td>
							</tr>
							 <tr valign='top'>
							  <th scope='row'><div>Title text for 1 star:</div></th>
							  <td><input type='text' name='1_stars_not_really_ok' value='" . $rateitcool_settings['1_stars_not_really_ok'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Title text for 2 stars:</div></th>
							 	<td><input type='text' name='2_stars_hm_ok' value='" . $rateitcool_settings['2_stars_hm_ok'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Title text for 3 stars:</div></th>
							 	<td><input type='text' name='3_stars_ok' value='" . $rateitcool_settings['3_stars_ok'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Title text for 4 stars:</div></th>
							 	<td><input type='text' name='4_stars_cool' value='" . $rateitcool_settings['4_stars_cool'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Title text for 5 stars:</div></th>
							 	<td><input type='text' name='5_stars_coolest' value='" . $rateitcool_settings['5_stars_coolest'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							  <th scope='row'><div>Review title global:</div></th>
							  <td><input type='text' name='review_global' value='" . $rateitcool_settings['review_global'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Placeholder review title:</div></th>
							 	<td><input type='text' name='placeholder_review_title' value='" . $rateitcool_settings['placeholder_review_title'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Placeholder review content:</div></th>
							 	<td><input type='text' name='placeholder_review_content' value='" . $rateitcool_settings['placeholder_review_content'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Recommend it to a friend:</div></th>
							 	<td><input type='text' name='review_recommend_a_friend' value='" . $rateitcool_settings['review_recommend_a_friend'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Send review link:</div></th>
							 	<td><input type='text' name='review_send_link' value='" . $rateitcool_settings['review_send_link'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Thank you for review text:</div></th>
							 	<td><input type='text' name='thank_you_for_the_review' value='" . $rateitcool_settings['thank_you_for_the_review'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							 	<th scope='row'><div>Error text sending review:</div></th>
							 	<td><input type='text' name='error_send_the_review' value='" . $rateitcool_settings['error_send_the_review'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							  <th scope='row'><div>Write shop review link:</div></th>
							  <td><input type='text' name='write_shop_review_link' value='" . $rateitcool_settings['write_shop_review_link'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							  <th scope='row'><div>Write shop review title:</div></th>
							  <td><input type='text' name='write_shop_review_title' value='" . $rateitcool_settings['write_shop_review_title'] . "' /></td>
							 </tr>
							 <tr valign='top'>
							  <th scope='row'><div>Write shop review hint:</div></th>
							  <td><input type='text' name='write_shop_review_hint' value='" . $rateitcool_settings['write_shop_review_hint'] . "' /></td>
							 </tr>

							 <tr valign='top'>
							  <th scope='row'><div>Please fill in the fellowing text:</div></th>
							  <td><input type='text' name='please_fill_in_the_fellowing_text' value='" . $rateitcool_settings['please_fill_in_the_fellowing_text'] . "' /></td>
							 </tr>
					 		 </fieldset>
		         </table>
						 <hr><br/>
		         <div class='buttons-container'>
		        <button type='button' id='rateitcool-export-reviews' class='button-secondary' ".disabled(true,empty($username) || empty($apikey), false).">Export Reviews</button>
						<input type='submit' name='rateitcool_settings' value='Update' class='button-primary' id='save_rateitcool_settings'/>
			  		</br></br><p class='description'>*Learn <a href='' target='_blank'>how to export your existing reviews</a> into rateit.cool.</p>
			</div>
		  </form>
		  <iframe name='rateitcool_export_reviews_frame' style='display: none;'></iframe>
		  <form action='' method='get' target='rateitcool_export_reviews_frame' style='display: none;'>
			<input type='hidden' name='download_exported_reviews' value='true' />
			<input type='submit' value='Export Reviews' class='button-primary' id='export_reviews_submit'/>
		  </form>
		</div>";

	echo $settings_html;
}

function wc_proccess_rateitcool_settings() {
	$current_settings = get_option('rateitcool_settings', wc_rateitcool_get_default_settings());
	$new_settings = array(
		'username' => $_POST['rateitcool_username'],
		'apikey' => $_POST['rateitcool_apikey'],
		'serverapikey' => $_POST['rateitcool_serverapikey'],
		'widget_location' => $_POST['rateitcool_widget_location'],
		'language_code' => $_POST['rateitcool_widget_language_code'],
		'securitytext' => $_POST['rateitcool_securitytext'],
		'widget_tab_name' => $_POST['rateitcool_widget_tab_name'],
		'bottom_line_enabled_product' => isset($_POST['rateitcool_bottom_line_enabled_product']) ? true : false,
		'bottom_line_enabled_category' => isset($_POST['rateitcool_bottom_line_enabled_category']) ? true : false,
		'rateitcool_language_as_site' => isset($_POST['rateitcool_language_as_site']) ? true : false,
		'disable_native_review_system' => isset($_POST['disable_native_review_system']) ? true : false,
		'rateitcool_gpntype' =>  isset($_POST['rateitcool_gpntype']) ? $_POST['rateitcool_gpntype'] : '',
		'show_submit_past_orders' => $current_settings['show_submit_past_orders'],

		'translation_review_template_title' => isset($_POST['translation_review_template_title']) ? $_POST['translation_review_template_title'] : $current_settings['translation_review_template_title'],
		'review_overview_title' => isset($_POST['review_overview_title']) ? $_POST['review_overview_title'] : $current_settings['review_overview_title'],
		'review_detail_title' => isset($_POST['review_detail_title']) ? $_POST['review_detail_title'] : $current_settings['review_detail_title'],
		'verified' => isset($_POST['verified']) ? $_POST['verified'] : $current_settings['verified'],
		'public' => isset($_POST['public']) ? $_POST['public'] : $current_settings['public'],
		'mobile' => isset($_POST['mobile']) ? $_POST['mobile'] : $current_settings['remobileview_detail_title'],
		'show_only_five_stars' => isset($_POST['show_only_five_stars']) ? $_POST['show_only_five_stars'] : $current_settings['show_only_five_stars'],
		'show_only_four_stars' => isset($_POST['show_only_four_stars']) ? $_POST['show_only_four_stars'] : $current_settings['show_only_four_stars'],
		'show_only_three_stars' => isset($_POST['show_only_three_stars']) ? $_POST['show_only_three_stars'] : $current_settings['show_only_three_stars'],
		'show_only_two_stars' => isset($_POST['show_only_two_stars']) ? $_POST['show_only_two_stars'] : $current_settings['show_only_two_stars'],
		'show_only_one_stars' => isset($_POST['show_only_one_stars']) ? $_POST['show_only_one_stars'] : $current_settings['show_only_one_stars'],
		'customer_recommend_this' => isset($_POST['customer_recommend_this']) ? $_POST['customer_recommend_this'] : $current_settings['customer_recommend_this'],
		'show_the_latest_reviews_first' => isset($_POST['show_the_latest_reviews_first']) ? $_POST['show_the_latest_reviews_first'] : $current_settings['show_the_latest_reviews_first'],
		'show_the_oldest_reviews_first' => isset($_POST['show_the_oldest_reviews_first']) ? $_POST['show_the_oldest_reviews_first'] : $current_settings['show_the_oldest_reviews_first'],
		'show_the_best_reviews_first' => isset($_POST['show_the_best_reviews_first']) ? $_POST['show_the_best_reviews_first'] : $current_settings['show_the_best_reviews_first'],
		'show_the_worst_reviews_first' => isset($_POST['show_the_worst_reviews_first']) ? $_POST['show_the_worst_reviews_first'] : $current_settings['show_the_worst_reviews_first'],
		'show_the_best_helpful_reviews_first' => isset($_POST['show_the_best_helpful_reviews_first']) ? $_POST['show_the_best_helpful_reviews_first'] : $current_settings['show_the_best_helpful_reviews_first'],
		'show_the_worst_helpful_reviews_first' => isset($_POST['show_the_worst_helpful_reviews_first']) ? $_POST['show_the_worst_helpful_reviews_first'] : $current_settings['show_the_worst_helpful_reviews_first'],
		'show_more_reviews_text' => isset($_POST['show_more_reviews_text']) ? $_POST['show_more_reviews_text'] : $current_settings['show_more_reviews_text'],
		'is_the_review_helpful_for_you' => isset($_POST['is_the_review_helpful_for_you']) ? $_POST['is_the_review_helpful_for_you'] : $current_settings['is_the_review_helpful_for_you'],
		'yes_helpful_text' => isset($_POST['yes_helpful_text']) ? $_POST['yes_helpful_text'] : $current_settings['yes_helpful_text'],
		'no_helpful_text' => isset($_POST['no_helpful_text']) ? $_POST['no_helpful_text'] : $current_settings['no_helpful_text'],
		'report_this_review' => isset($_POST['report_this_review']) ? $_POST['report_this_review'] : $current_settings['report_this_review'],
		'write_a_product_review' => isset($_POST['write_a_product_review']) ? $_POST['write_a_product_review'] : $current_settings['write_a_product_review'],
		'write_product_review_title' => isset($_POST['write_product_review_title']) ? $_POST['write_product_review_title'] : $current_settings['write_product_review_title'],
		'write_product_review_link' => isset($_POST['write_product_review_link']) ? $_POST['write_product_review_link'] : $current_settings['write_product_review_link'],
		'write_product_review_hint' => isset($_POST['write_product_review_hint']) ? $_POST['write_product_review_hint'] : $current_settings['write_product_review_hint'],
		'1_stars_not_really_ok' => isset($_POST['1_stars_not_really_ok']) ? $_POST['1_stars_not_really_ok'] : $current_settings['1_stars_not_really_ok'],
		'2_stars_hm_ok' => isset($_POST['2_stars_hm_ok']) ? $_POST['2_stars_hm_ok'] : $current_settings['2_stars_hm_ok'],
		'3_stars_ok' => isset($_POST['3_stars_ok']) ? $_POST['3_stars_ok'] : $current_settings['3_stars_ok'],
		'4_stars_cool' => isset($_POST['4_stars_cool']) ? $_POST['4_stars_cool'] : $current_settings['4_stars_cool'],
		'5_stars_coolest' => isset($_POST['5_stars_coolest']) ? $_POST['5_stars_coolest'] : $current_settings['5_stars_coolest'],
		'review_global' => isset($_POST['review_global']) ? $_POST['review_global'] : $current_settings['review_global'],
		'placeholder_review_title' => isset($_POST['placeholder_review_title']) ? $_POST['placeholder_review_title'] : $current_settings['placeholder_review_title'],
		'placeholder_review_content' => isset($_POST['placeholder_review_content']) ? $_POST['placeholder_review_content'] : $current_settings['placeholder_review_content'],
		'review_recommend_a_friend' => isset($_POST['review_recommend_a_friend']) ? $_POST['review_recommend_a_friend'] : $current_settings['review_recommend_a_friend'],
		'review_send_link' => isset($_POST['review_send_link']) ? $_POST['review_send_link'] : $current_settings['review_send_link'],
		'thank_you_for_the_review' => isset($_POST['thank_you_for_the_review']) ? $_POST['thank_you_for_the_review'] : $current_settings['thank_you_for_the_review'],
		'error_send_the_review' => isset($_POST['error_send_the_review']) ? $_POST['error_send_the_review'] : $current_settings['error_send_the_review'],
		'write_shop_review_link' => isset($_POST['write_shop_review_link']) ? $_POST['write_shop_review_link'] : $current_settings['write_shop_review_link'],
		'write_shop_review_title' => isset($_POST['write_shop_review_title']) ? $_POST['write_shop_review_title'] : $current_settings['write_shop_review_title'],
		'write_shop_review_hint' => isset($_POST['write_shop_review_hint']) ? $_POST['write_shop_review_hint'] : $current_settings['write_shop_review_hint'],
		'please_fill_in_the_fellowing_text' => isset($_POST['please_fill_in_the_fellowing_text']) ? $_POST['please_fill_in_the_fellowing_text'] : $current_settings['please_fill_in_the_fellowing_text'],
	);
	update_option( 'rateitcool_settings', $new_settings );
	if($current_settings['disable_native_review_system'] != $new_settings['disable_native_review_system']) {
		if($new_settings['disable_native_review_system'] == false) {
			update_option( 'woocommerce_enable_review_rating', get_option('native_star_ratings_enabled'));
		}
		else {
			update_option( 'woocommerce_enable_review_rating', 'no');
		}
	}
}

function wc_rateitcool_display_message($messages = array(), $type = '') {
	$class = ($type === 'warning' ? 'notice notice-warning': ($type === 'error' ? 'error' : 'updated fade'));
	if(is_array($messages)) {
		foreach ($messages as $message) {
			echo "<div id='message' class='$class'><p><strong>$message</strong></p></div>";
		}
	}
	elseif(is_string($messages)) {
		echo "<div id='message' class='$class'><p><strong>$messages</strong></p></div>";
	}
}
