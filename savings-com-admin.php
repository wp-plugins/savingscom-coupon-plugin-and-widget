<?php
/**
 * @package Savings_com
 */

global $default_shortcode;
$default_shortcode = '[savings_com]';
add_action('admin_menu', 'saving_com_config_menu');

function saving_com_config_menu() {
	add_options_page('Savings.com Options', 'Savings.com', 'manage_options', 'savings-com-config', 'saving_com_options');
}

if (!function_exists('get_site_url')) {
    function get_site_url() {
      return get_option( 'siteurl' );
    }
} 



function saving_com_options() {
	global $savings_com_nonce, $default_shortcode;
	
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	//Load css selected by default
	$default_config['loadcss'] = true;
	$default_config['endpoint'] = "US";
	//$default_config['monetizable'] = "no";
	$default_config['featured'] = array(1 => 20235, 2 => 29059, 3 => 20729);
	//Try to load configuration array. If fails creates one with default config.
	if (get_option('savings_com_config')){
		$config = get_option('savings_com_config');
	} else {
		add_option('savings_com_config',$default_config);
		$config = get_option('savings_com_config');
	}
	
	if ( isset($_POST['submit']) ) {
		check_admin_referer( $savings_com_nonce );
		$key = $_POST['key'];
		$config['loadcss'] = isset($_POST['loadcss']);
		$config['endpoint'] = $_POST['endpoint'];
		//$config['monetizable'] = $_POST['monetizable'];
		$home_url = parse_url( get_bloginfo('url') );
		update_option('savings_com_config', $config);
		if ( empty($key) ) {
			$key_status = 'empty';
			$ms[] = 'new_key_empty';
			delete_option('savings_com_api_key');
		} elseif ( empty($home_url['host']) ) {
			$key_status = 'empty';
			$ms[] = 'bad_home_url';
		} else {
			$key_status = savings_com_verify_key( $key );
		}

		if ( $key_status == 'valid' ) {
			update_option('savings_com_api_key', $key);
			$ms[] = 'new_key_valid';
		} else if ( $key_status == 'invalid' ) {
			$ms[] = 'new_key_invalid';
		} else if ( $key_status == 'failed' ) {
			$ms[] = 'new_key_failed';
		}

	}
	
	if ( empty( $key_status) ||  $key_status != 'valid' ) {
		$key = get_option('savings_com_api_key');
		if ( empty( $key ) ) {
			if ( empty( $key_status ) || $key_status != 'failed' ) {
				if ( savings_com_verify_key( '1234567890ab' ) == 'failed' )
					$ms[] = 'no_connection';
				else
					$ms[] = 'key_empty';
			}
			$key_status = 'empty';
		} else {
			$key_status = savings_com_verify_key( $key );
		}
		if ( $key_status == 'valid' ) {
			$ms[] = 'key_valid';
		} else if ( $key_status == 'invalid' ) {
			delete_option('savings_com_api_key');
			$ms[] = 'key_empty';
		} else if ( !empty($key) && $key_status == 'failed' ) {
			$ms[] = 'key_failed';
		}
	}
	
	
	$messages = array(
		'new_key_empty' => array('color' => 'aa0', 'text' => __('Your key has been cleared.')),
		'new_key_valid' => array('color' => '4AB915', 'text' => __('Your key has been verified. Happy blogging!')),
		'new_key_invalid' => array('color' => '888', 'text' => __('The key you entered is invalid. Please double-check it.')),
		'new_key_failed' => array('color' => '888', 'text' => __('The key you entered could not be verified because a connection to Savings.com could not be established. Please check your server configuration.')),
		'no_connection' => array('color' => '888', 'text' => __('There was a problem connecting to the Savings.com server. Please check your server configuration.')),
		'key_empty' => array('color' => 'aa0', 'text' => sprintf(__('Please enter an API key. (<a href="%s" style="color:#fff">Get your key.</a>)'), 'http://savings.com')),
		'key_valid' => array('color' => '4AB915', 'text' => __('This key is valid.')),
		'key_failed' => array('color' => 'aa0', 'text' => __('The key below was previously validated but a connection to Savings.com can not be established at this time. Please check your server configuration.')),
		'bad_home_url' => array('color' => '888', 'text' => sprintf( __('Your WordPress home URL %s is invalid.  Please fix the <a href="%s">home option</a>.'), esc_html( get_bloginfo('url') ), admin_url('options.php#home') ) ),
	);
	
	//Loads categories for checkboxes list.
	$categories_data = savings_com_get_data( 'getCategories' );
	$categories = $categories_data->categories;
	
?>
<div class="wrap">
    <h2><?php _e('Savings.com Configuration'); ?></h2>
    <div style="margin: auto; width: 600px; ">
	<form action="" method="post" id="savings-com-configuration">
  <p>The Savings.com WordPress Plugin uses information from the Savings.com API to populate the plugin and widget with Savings.com deal content. Enter your specifications below to tailor the plugin for your audience. If you have any questions please refer to the <a href="http://www.savings-plugin-staging.com/staging1/about-the-plugin/">SDC Plugin Website</a> or you can apply for an API Key by clicking <a href="http://www.tinyurl.com/savingswordpress">Here</a>. </p>
  
	<h3><label for="key"><?php _e('Savings.com API Key'); ?></label></h3>
	<?php foreach ( $ms as $m ) : ?>
	<p style="padding: .5em; background-color: #<?php echo $messages[$m]['color']; ?>; color: #fff; font-weight: bold;"><?php echo $messages[$m]['text']; ?> 
	</p>
	<?php endforeach; ?>
	<p><input id="key" name="key" type="text" size="40" maxlength="36" value="<?php echo get_option('savings_com_api_key'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;" /> <a href="http://www.tinyurl.com/savingswordpress">Obtain API key</a>
	
	<img class="tooltip" title="In order to use this plugin you are required to enter your API developer key for verification purposes. You can obtain a key directly from Savings.com" src="<?php echo get_site_url();?>/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/tooltip.gif"/></p>
	<p><input id="loadcss" name="loadcss" type="checkbox" <?php checked( $config['loadcss'], true ); ?> /> <?php _e("Use Savings.com stylesheet?"); ?></p>
	<p>
	  <input name="endpoint" type="radio" value="US" <?php echo ($config['endpoint'] == "US")?"CHECKED=CHECKED":""; ?>/> <label><?php _e("US API"); ?></label>
	  <input name="endpoint" type="radio" value="UK" <?php echo ($config['endpoint'] == "UK")?"CHECKED=CHECKED":""; ?>/> <label><?php _e("UK API"); ?></label>
	</p>
	
	<!--<p>Would you like the plugin to display deals from non-monetizable merchants? 
	<input name="monetizable" type="radio" value="no" <?php echo ($config['monetizable'] == "no")?"CHECKED=CHECKED":""; ?>/> <label><?php _e("No"); ?></label>
	<input name="monetizable" type="radio" value="yes" <?php echo ($config['monetizable'] == "yes")?"CHECKED=CHECKED":""; ?>/> <label><?php _e("Yes"); ?></label>
	<img class="tooltip" title="Non-monetizable merchants are merchants that Savings.com will not be able to pay you for your conversions." src="<?php echo get_site_url();?>/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/tooltip.gif"/></p>
	-->
	<p class="submit"><input type="submit" name="submit" value="<?php _e('Save configuration &raquo;'); ?>" /></p>
	
	<?php savings_com_nonce_field($savings_com_nonce) ?>
	</form>
    </div>
    <?php if ( $categories ): ?>
    <div style="margin: auto; width: 600px;">
	<form action="" method="post" id="savings-com-shortcode-generator">
	<h3><?php _e('Shortcode'); ?></h3>	
	<p>Paste the shortcode into a page/post. You can use the default shortcode to show all the deals, or generate your custom shortcode.</p>
	<h4><?php _e('Default shortcode: '); ?></h4>
	<p><?php echo $default_shortcode; ?></p>
	<h4><label for="key"><?php _e('Generate your custom shortcode: '); ?></label></h4>
	
	<p><label for="featured"><?php _e('Featured Categories: '); ?></label><br/>
	<select id="featured_1" name="featured_1">
	  <?php if ( $categories ) {
		  foreach($categories as $cat){	?>
		    <option value="<?php echo $cat->id; ?>" <?php echo ($cat->id == $config['featured'][1])?"SELECTED=SELECTED":"";?>> <?php echo $cat->name; ?></option>
	    <?php }};?>
	</select>
	<select id="featured_2" name="featured_2">
	  <?php if ( $categories ) {
		  foreach($categories as $cat){	?>
		    <option value="<?php echo $cat->id; ?>" <?php echo ($cat->id == $config['featured'][2])?"SELECTED=SELECTED":"";?>> <?php echo $cat->name; ?></option>
	    <?php }};?>  
	</select>
	<select is="featured_3" name="featured_3">
	  <?php if ( $categories ) {
		  foreach($categories as $cat){	?>
		    <option value="<?php echo $cat->id; ?>" <?php echo ($cat->id == $config['featured'][3])?"SELECTED=SELECTED":"";?>> <?php echo $cat->name; ?></option>
	    <?php }};?>
	</select>
	</p>
	
	<p><label for="category_ids"><?php _e('Select which categories to show: '); ?></label></p>
	<?php if ( $categories ) { $i=1;
		foreach($categories as $cat){
		?>
		<input type="checkbox" CHECKED=CHECKED name="category_ids[<?php echo $cat->name; ?>]" value="<?php echo $cat->id; ?>" style="float:left; margin:5px"/> <label style="width:250px; float:left"><?php echo $cat->name; ?></label>
	<?php
	  if($i == 2) {echo '<br style="clear:both"/>'; $i=0;} $i++;
		}		
	};
	?>
	<br style="clear:both"/>
	
	<p><label for="page_size"><?php _e('Select how many items to show: '); ?></label>
	<input id="page_size" name="page_size" type="text" size="1" maxlength="2" value="<?php echo $_POST['page_size'] ? $_POST['page_size'] : 10; ?>"/></p>
	<p class="submit"><input type="submit" name="shortcode" value="<?php _e('Generate shortcode &raquo;'); ?>" /></p>
	</form>
	<?php if ( isset($_POST['shortcode']) ) {
		$params = array();
		if($_POST['category_ids']){			
			foreach( (array) $_POST['category_ids'] as $category => $cat_id ){
				$params['category_ids'][] = $cat_id;
			}
		}
		if($_POST['page_size']){
			$params['page_size'] = $_POST['page_size'];
		}
		if($_POST['featured_1']) {
		  $params['featured_1'] = $_POST['featured_1'];
		}
		if($_POST['featured_2']) {
		  $params['featured_2'] = $_POST['featured_2'];
		}
		if($_POST['featured_3']) {
		  $params['featured_3'] = $_POST['featured_3'];
		}
		
		$custom_shortcode = savings_com_generate_shortcode($params);
		
	}; ?>
	<h4><?php _e('Custom shortcode: '); ?></h4>
	<p><?php echo $custom_shortcode ? $custom_shortcode : $default_shortcode; ?></p>
    </div>
    <?php endif; ?>
</div>
<script type="text/javascript">
jQuery(document).ready( function() {
    jQuery('.tooltip').tTips(); // tooltipize elements with classname "givemesometips"
});
</script>
<?php }; 

function savings_com_nonce_field($action = -1) { return wp_nonce_field($action); }
$savings_com_nonce = 'savings-com-api-key';

function savings_com_verify_key( $key, $ip = null ) {
	/*key verification here*/
	$params = array(
	'start_index' => '0',
	  'page_size' => '1',
	  );	
	$validation_deals_data = savings_com_get_data( 'getDeals', $params, $_POST['key'] );
	$validation_deals = $validation_deals_data->out->deals;
	$url_deal = parse_url($validation_deals->dealUrl);
	parse_str($url_deal['query']);
	if($placementid && $placementid != null && $placementid != ''){
		update_option( "savings_com_placementid", $placementid );
		return 'valid';
	}	
	return 'invalid';
}

function savings_com_generate_shortcode($params = array()){
	$custom_shortcode = '[savings_com';
	if( !$params || $params == '' ){
		return $default_shortcode;
	} else {
		$categories_string = '';
		
		foreach($params as $param => $param_val){
			if( $param_val && $param_val != '' ){
				if( is_array( $param_val ) )
					$params_string .= " ".$param."='".serialize($param_val)."'";
				else
					$params_string .= " ".$param."=".$param_val;
			}
		}
	}
	
	$custom_shortcode .= $params_string;
	$custom_shortcode .= ']';
	
	return $custom_shortcode;
	
}

function crawl_merchants(){
	global $wpdb;
	$wpdb->show_errors();
	
	$has_more = true;
	$start_index = 0;
	$page_size = 500;
	
	
		
	$params = array(
	'start_index' => $_REQUEST['start_index'],
	  'page_size' => (string)$page_size,
	);
	
	$merchants_data = savings_com_get_data( 'getMerchants' , $params );
	$merchants = $merchants_data->out->merchants;
	
	$has_more = $merchants_data->out->hasMore;
	$start_index += $page_size;
	
	
	foreach($merchants as $merchant){
		$id = is_object( $merchant->id ) ? '' : $merchant->id;
		$name = is_object( $merchant->name ) ? '' : $merchant->name;
		$description = is_object( $merchant->description ) ? '' : $merchant->description;
		$displayUrl = is_object( $merchant->displayUrl ) ? '' : $merchant->displayUrl;
		$imageUrl = is_object( $merchant->imageUrl ) ? '' : $merchant->imageUrl;
		$allowPartnerDeals = is_object( $merchant->allowPartnerDeals ) ? '' : $merchant->allowPartnerDeals;
		$categoryIds = is_object( $merchant->categoryIds ) ? '' : $merchant->categoryIds;
		
		$wpdb->query( $wpdb->prepare( "INSERT INTO ".$wpdb->prefix."savings_merchants
						( id, name, description, displayUrl, imageUrl, allowPartnerDeals, categoryIds ) VALUES ( %s, %s, %s, %s, %s, %s, %s )", 
						array( (string)$id, (string)$name, (string)$description, (string)$displayUrl, (string)$imageUrl, (string)$allowPartnerDeals, (string)$categoryIds) )
						);
		  
		}
	
	
}
