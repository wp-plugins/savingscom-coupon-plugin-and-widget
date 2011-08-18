<?php
/**
 * @package Savings-com
 */
/*
Plugin Name: Savings.com
Plugin URI: http://savings.com/wordpress_plugin
Description: Show deals form <strong>Savings.com</strong> directly into your wordpress site. 
Version: 1.4.1
Author: MyShuitings
Author URI: http://myshuitings.com
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

include_once dirname( __FILE__ ) . '/savings-com-widget.php';
include_once dirname( __FILE__ ) . '/savings-com-shortcode.php';
if ( is_admin() )
require_once dirname( __FILE__ ) . '/savings-com-admin.php';
	

add_action('wp_ajax_savings_com', 'savings_com_ajax');
add_action('wp_ajax_nopriv_savings_com', 'savings_com_ajax');
	
$savings_com_plugin_path = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)).'/';
$config = get_option('savings_com_config');
$enpoint_info = get_variables($config['endpoint']);

global $savings_com_api_host;
$savings_com_api_host = $enpoint_info['api'];

if (!function_exists('get_site_url')) {
    function get_site_url() {
      return get_option( 'siteurl' );
    }
} 

function savings_com_get_data( $api_name = 'getDeals', $params = array(), $api_key_test = '' ){

	global $wpdb;
	$wpdb->show_errors();
	
	//Define parameters for each API method
	switch( $api_name ){		
		case 'getDeals':
			$expiration_hours = 1;
			$filtered_params = array(
		'startIndex' => $_REQUEST['pagination'] ? (string)(( $_REQUEST['pagination'] - 1 ) * $params['page_size']) : $params['start_index'],
		  'pageSize' => $params['page_size'],
	   'predefinedQuery' => $params['predefined_query'],
             'creatorFilter' => 'ALL',
                   'keyword' => $params['keyword'],
                      'tags' => $params['tags'],
               'categoryIds' => $params['category_ids'],
               'merchantIds' => $params['merchant_ids'],
       'excludedMerchantIds' => $params['excluded_merchant_ids'],
          'monetizeableOnly' => $params['monetizeable_only']
				);
		break;
		case 'getCategories':
			$expiration_hours = 24;
			$filtered_params = array();
		break;
		case 'getMerchants':
			$expiration_hours = 24;
			$filtered_params = array(
			    'startIndex' => $_REQUEST['pagination'] ? (string)(( $_REQUEST['pagination'] - 1 ) * $params['page_size'])  : $params['start_index'],
			      'pageSize' => $params['page_size'],
					);
		break;
		case 'getMerchantsById':
			$expiration_hours = 24;
			$filtered_params = array(
			    'merchantIds' => $_REQUEST['merchant_ids']
					);
		break;
	}
	
	//Remove null values
	$valid_params = array();	
	foreach($filtered_params as $param => $val){
		if( $val != '' ){
			$valid_params[$param] = $val;
		}
	}
	//Build parameters string
	$savings_com_api_parameters = '';	
	foreach( $valid_params as $param => $val) {
		if(!is_array( $val ) ){
			$savings_com_api_parameters .= '&'.$param.'='.$val;
		} elseif( is_array( $val ) ) {
			foreach( $val as $array_val){
				$savings_com_api_parameters .= '&'.$param.'='.$array_val;
				
			}
			
		}
		
		 
	}
	
	//Build API request URL
	global $savings_com_api_host;
	
	//Check if the call is for key validation
	if($api_key_test == ''){
		//If not, get hey from db
		$savings_com_api_key = get_option('savings_com_api_key');
	} else {
		//If is trying to validate a new key, grab it from parameter.
		$savings_com_api_key = $api_key_test;
	}
	if( !$savings_com_api_key ){
		return '<h3>Sevice Unavailable</h3>';
	}	
	$savings_com_url = $savings_com_api_host.$api_name.'?developerKey='.$savings_com_api_key.$savings_com_api_parameters;
	//var_dump($savings_com_url);
	
	$savings_com_url = str_replace('%5C','',$savings_com_url); //take out the backslash if it is added before the appostraphe... since the apostraphe is now urlencoded it is "safe"
	//Check cache data
	$cached_data = savings_com_find_cached( $savings_com_url, $expiration_hours );
	
	if(!$cached_data){
		//If no cached data, retrieves from savings.com and save in cache db.
		$nodes = savings_com_retrieve_from_server( $savings_com_url );
		$json = json_encode( $nodes );
		$wpdb->query( $wpdb->prepare( "INSERT INTO ".$wpdb->prefix."savings_com_cache
						( time, url, content ) VALUES ( NOW(), %s, %s )", 
						array( $savings_com_url, $json) )
						);
		
		$cached_data = savings_com_find_cached( $savings_com_url, $expiration_hours );
	  unset($nodes);
		$nodes = json_decode($cached_data->content);
		
	} elseif( $cached_data && ( ($cached_data->diff_min + ($cached_data->diff_hour*60)) > ( $expiration_hours * 60 ) ) ) { 
		//If cached data is too old, retrieves from savings.com and update in cache db. 
		$nodes = savings_com_retrieve_from_server( $savings_com_url );
		$json = json_encode( $nodes );
		$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."savings_com_cache
				     SET time = NOW(), url = %s, content = %s
				     WHERE id = %d", array( $savings_com_url, $json, $cached_data->id) )
					);
					
		//then pull it back out of the cache so we don't have to deal with two ways of it potentially showing.
		$cached_data = savings_com_find_cached( $savings_com_url, $expiration_hours );
	  unset($nodes);
		$nodes = json_decode($cached_data->content);
	} else { 
		
		$nodes = json_decode($cached_data->content);
	}
	//var_dump($nodes);
	return $nodes;
	
	
}
function savings_com_retrieve_from_server( $savings_com_url ){
	//Connect with api
		$service_data = @file_get_contents( $savings_com_url );
		if (strpos($http_response_header[0], "200")) { 
			$xml = simplexml_load_string( $service_data );
			$nodes = $xml->children( 'https://api.savings.com/deal/v2' );
		     } else { 
			      $result = wp_remote_get($savings_com_url);
			      if($result['response']['code'] == 200) {
			        $xml = simplexml_load_string( $result['body'] );
			        $nodes = $xml->children( 'https://api.savings.com/deal/v2' );
			      }
		     }	    
	return $nodes;
}
function savings_com_find_cached( $url ){
	global $wpdb;
	$wpdb->show_errors();
	$result = $wpdb->get_row( $wpdb->prepare("SELECT scc.id, scc.time, scc.content, MINUTE(TIMEDIFF( NOW(), scc.time )) AS diff_min, HOUR(TIMEDIFF( NOW(), scc.time )) AS diff_hour FROM ".$wpdb->prefix."savings_com_cache AS scc WHERE url = %s ORDER BY scc.id DESC LIMIT 1",$url));
	if(!$result){
		return false;
	} else {
		return $result;
	}
	
}

global $savings_com_db_version;
$savings_com_db_version = "1.4";

function savings_com_create_db_table () {
	global $wpdb, $savings_com_db_version;
	$installed_db_version = get_option( "savings_com_db_version" );
		
	$table_name = $wpdb->prefix . "savings_com_cache";
	
	if( $installed_db_version != $savings_com_db_version ) {
		$sql = "DROP TABLE IF EXISTS " . $table_name;
	  $wpdb->query($sql);
		$sql = "CREATE TABLE " . $table_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		url text NOT NULL,
		content longtext NOT NULL,			
		UNIQUE KEY id (id)			
		);";
    //require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$wpdb->query($sql);
		update_option( "savings_com_db_version", $savings_com_db_version );
    }
}
register_activation_hook(__FILE__,'savings_com_create_db_table');

function savings_com_update_db_check() {
    global $savings_com_db_version;
    if (get_option( "savings_com_db_version" ) != $savings_com_db_version) {
        savings_com_create_db_table();
    }
}
add_action('plugins_loaded', 'savings_com_update_db_check');

function convertXmlObjToArr($obj, &$arr) 
{ 
    $children = $obj->children( 'https://api.savings.com/deal/v2' ); 
    foreach ($children as $elementName => $node) 
    { 
        $nextIdx = count($arr); 
        $arr[$nextIdx] = array(); 
        $arr[$nextIdx]['@name'] = strtolower((string)$elementName); 
        $arr[$nextIdx]['@attributes'] = array(); 
        $attributes = $node->attributes(); 
        foreach ($attributes as $attributeName => $attributeValue) 
        { 
            $attribName = strtolower(trim((string)$attributeName)); 
            $attribVal = trim((string)$attributeValue); 
            $arr[$nextIdx]['@attributes'][$attribName] = $attribVal; 
        } 
        $text = (string)$node; 
        $text = trim($text); 
        if (strlen($text) > 0) 
        { 
            $arr[$nextIdx]['@text'] = $text; 
        } 
        $arr[$nextIdx]['@children'] = array(); 
        convertXmlObjToArr($node, $arr[$nextIdx]['@children']); 
    } 
    return; 
}

/**
 * Load Savings.com css when the user select this option.
 */
function savings_com_load_css() {
	global $savings_com_plugin_path, $config;	
	if ($config['loadcss'] == true) {		
		wp_enqueue_style('savings-com-css',$savings_com_plugin_path.'savings-com.css'); 
	}
}
add_action('wp_print_styles', 'savings_com_load_css');

/**
 *Load Savings.com javascript.
 */
function savings_com_load_js() {
	global $savings_com_plugin_path;
	if ( !is_admin() ) {	
		wp_enqueue_script('jquery');
		wp_enqueue_script('savings-com-js',$savings_com_plugin_path.'savings-com.js', array('jquery'));
    wp_enqueue_script('tiptip', '/wp-content/plugins/savingscom-coupon-plugin-and-widget/tiptip.js');
		wp_enqueue_script('zclip', '/wp-content/plugins/savingscom-coupon-plugin-and-widget/jquery.zclip.js');
		wp_enqueue_script('infieldlabels', '/wp-content/plugins/savingscom-coupon-plugin-and-widget/jquery.infieldlabel.js');
	}
}
add_action('wp_print_scripts', 'savings_com_load_js' );


function savings_com_ajax() {
    //get params with $_POST
    if(isset($_POST['term'])) {
    $term = str_replace('%5C','',rawurlencode($_POST['term'])); //take out the backslash if it is added before the appostraphe... since the apostraphe is now urlencoded it is "safe"
     $params = array(
	        'start_index' => '0',
		  'page_size' => 15,
           'predefined_query' => '',
             'creator_filter' => 'ALL',
                    'keyword' => $term,
                       'tags' => '',
               'category_ids' => '',
               'merchant_ids' => '',
      'excluded_merchant_ids' => '',
          'monetizeable_only' => ''
				);
    } 
    
    if(isset($_POST['category'])) {
     $params = array(
	        'start_index' => '0',
		  'page_size' => 15,
           'predefined_query' => 'TOP_7DAYS',
             'creator_filter' => 'ALL',
                    'keyword' => '',
                       'tags' => '',
               'category_ids' => $_POST['category'],
               'merchant_ids' => '',
      'excluded_merchant_ids' => '',
          'monetizeable_only' => ''
				);
    } 
    
    $deals_data = savings_com_get_data( 'getDeals' , $params );
		$deals = $deals_data->out->deals;
		//var_dump($deals_data);
		$output = '';
		$codes_array = array();
		if(is_array($deals) || is_object($deals)) {
		  foreach($deals as $deal){
						$coupon_code = is_object($deal->couponCode) ? 'Shop Now!' : 'Get Code!';
						$code_tooltip = is_object($deal->couponCode) ? 'Click to Activate Coupon & Open Site' : 'Click to Copy Code & Open Site';
						$class_tooltip = is_object($deal->couponCode) ? 'shop' : 'buy';
		    
						$codes_array[(string) $deal->id] = is_object($deal->couponCode) ? '' : $deal->couponCode;
						$code = is_object($deal->couponCode) ? '' : $deal->couponCode;
							$output .= '<li class="savings-com-widget-item">
								
								      
				          <a target="_blank" class="savings-com-deal-merchant" id="widget-deal-merchant-'.$deal->id.'" href="'.$deal->dealUrl.'">
					      <img width="120" src="'.$deal->merchantImageUrl.'?width=120" alt="'.$deal->merchantName.' coupons"><br/><div class="merchant-name">'.$deal->merchantName.'</div>
				          </a>
				     <a target="_blank" href="'.$deal->dealUrl.'" class="savings-com-deal-code '.$coupon_code.'" title="'.$code_tooltip.'" id="widget-'.$deal->id.'"><span>'.$coupon_code.'</span></a>
				      <br style="clear:both"/>
				          <a target="_blank" class="savings-com-deal-title" id="widget-deal-title-'.$deal->id.'" href="'.$deal->dealUrl.'">'.$deal->title.'</a>
				     
				      
							';
							$output .='
							<script>
							
							jQuery("#widget-'.$deal->id.'").tipTip({defaultPosition: "left", maxWidth:"auto", classname:"'.$class_tooltip.'"});
			        jQuery("#widget-deal-merchant-'.$deal->id.'").tipTip({defaultPosition: "right", maxWidth:"auto", classname:"'.$class_tooltip.'"});
			        jQuery("#widget-deal-title-'.$deal->id.'").tipTip({defaultPosition: "right", maxWidth:"auto", classname:"'.$class_tooltip.'"});
							
				      jQuery("#widget-'.$deal->id.'").zclip({
              path:"'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf",
              copy:"'.$code.'",
              afterCopy:function(){
                  window.open(jQuery(this).attr("href"));
              }
              });
              jQuery("#widget-deal-merchant-'.$deal->id.'").zclip({
              path:"'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf",
              copy:"'.$code.'",
              afterCopy:function(){
                  window.open(jQuery(this).attr("href"));
              }
              }); 
              jQuery("#widget-deal-title-'.$deal->id.'").zclip({
              path:"'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf",
              copy:"'.$code.'",
              afterCopy:function(){
                  window.open(jQuery("#widget-'.$deal->id.'").attr("href"));
              }
              });
              </script></li>';
						} $codes_json = json_encode($codes_array);
							
							$output .='<script type="text/javascript">var widget_codes = '.$codes_json.';
		/*jQuery("#savings-com-widget-content .savings-com-deal-code").tipTip({defaultPosition: "left", maxWidth: "auto"});
		jQuery("#savings-com-widget-content .savings-com-deal-merchant").tipTip({defaultPosition: "right", maxWidth: "auto"});
		jQuery("#savings-com-widget-content .savings-com-deal-title").tipTip({defaultPosition: "right", maxWidth: "auto"});*/
		
		ajaxrefresh();
		
		</script>';  
							
							
		} elseif (isset($deals->id)) {
		  $coupon_code = is_object($deals->couponCode) ? 'Shop Now!' : 'Get Code!';
						$code_tooltip = is_object($deals->couponCode) ? 'Click to Activate Coupon & Open Site' : 'Click to Copy & Open Site';
						$codes_array[(string) $deals->id] = is_object($deals->couponCode) ? '' : $deals->couponCode;
						$class_tooltip = is_object($deal->couponCode) ? 'shop' : 'buy';
		    
						$code = is_object($deals->couponCode) ? '' : $deals->couponCode;
							$output .= '<li class="savings-com-widget-item">
															      
				          <a target="_blank" class="savings-com-deal-merchant" id="widget-deal-merchant-'.$deals->id.'" href="'.$deals->dealUrl.'">
					      <img width="120" src="'.$deals->merchantImageUrl.'?width=120" alt="'.$deals->merchantName.' coupons"><br/><div class="merchant-name">'.$deal->merchantName.'</div>
				          </a>
				     <a target="_blank" href="'.$deal->dealUrl.'" class="savings-com-deal-code '.$coupon_code.'?>" title="'.$code_tooltip.'" id="widget-'.$deals->id.'"><span>'.$coupon_code.'</span></a>
				      <br style="clear:both"/>
				          <a target="_blank" class="savings-com-deal-title" id="widget-deal-title-'.$deal->id.'" href="'.$deals->dealUrl.'">'.$deals->title.'</a>';
							
						 $codes_json = json_encode($codes_array);
							
							$output .='<script type="text/javascript">var widget_codes = '.$codes_json.';
		/*jQuery("#savings-com-widget-content .savings-com-deal-code").tipTip({defaultPosition: "left", maxWidth: "auto"});
		jQuery("#savings-com-widget-content .savings-com-deal-merchant").tipTip({defaultPosition: "right", maxWidth: "auto"});
		jQuery("#savings-com-widget-content .savings-com-deal-title").tipTip({defaultPosition: "right", maxWidth: "auto"});*/
		
		ajaxrefresh();
		
		</script>
							'; $output .='
							<script>
							
							jQuery("#widget-'.$deal->id.'").tipTip({defaultPosition: "left", maxWidth:"auto", classname:"'.$class_tooltip.'"});
			        jQuery("#widget-deal-merchant-'.$deal->id.'").tipTip({defaultPosition: "right", maxWidth:"auto", classname:"'.$class_tooltip.'"});
			        jQuery("#widget-deal-title-'.$deal->id.'").tipTip({defaultPosition: "right", maxWidth:"auto", classname:"'.$class_tooltip.'"});
							
							
				      jQuery("#widget-'.$deals->id.'").zclip({
              path:"'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf",
              copy:"'.$code.'",
              afterCopy:function(){
                  window.open(jQuery(this).attr("href"),"_blank");
              }
              }); 
              jQuery("#widget-deal-merchant-'.$deals->id.'").zclip({
              path:"'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf",
              copy:"'.$code.'",
              afterCopy:function(){
                  window.open(jQuery(this).attr("href"),"_blank");
              }
              }); 
              jQuery("#widget-deal-title-'.$deals->id.'").zclip({
              path:"'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf",
              copy:"'.$code.'",
              afterCopy:function(){
                  window.open(jQuery("#widget-'.$deal->id.'").attr("href"),"_blank");
              }
              });
              </script></li>
							';
		} else {
		  $output = 'There were no results for that search';
		}
		echo $output;
    
    /*
    jQuery.post(
    'http://localhost/wordpress/savingsdev/wp-admin/admin-ajax.php',
    {
        action : 'savings_com',
        term : 'Free'
    },
    function( response ) {
        jQuery('ul.savings-com-widget-list').html(response.substring(0, response.length-1));
        console.log( response );
    }
);
    
    */
}

?>
