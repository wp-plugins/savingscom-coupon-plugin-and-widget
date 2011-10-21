<?php
/**
 * @package Savings_com
 */
 
 if (!function_exists('get_site_url')) {
    function get_site_url() {
      return get_option( 'siteurl' );
    }
} 
 
function get_variables($endpoint) {
  if($endpoint == "US") {
    return array(
      'logo' => 'logo_us',
      'about_link' => 'http://www.savings.com/about/',
      'home_link' => 'http://www.savings.com',
      'email' => 'customercare@savings.com',
      'shop_now' => 'shop_us',
      'get_code' => 'code_us',
      'date_format' => 'MM/DD',
      'base' => 'us',
      'api' => 'http://api.savings.com/services/DealServiceV2_1/'
    );
  } elseif ($endpoint == "UK") {
    return array(
      'logo' => 'logo_uk',
      'about_link' => 'http://www.savoo.co.uk/about/uk',
      'home_link' => 'http://www.savoo.co.uk',
      'email' => 'customercareuk@savoo.co.uk',
      'shop_now' => 'shop_uk',
      'get_code' => 'code_uk',
      'date_format' => 'DD/MM',
      'base' => 'uk',
      'api' => 'http://api.savoo.co.uk/services/DealServiceV2_1/'
    );
  }
}

add_shortcode( 'savings_com', 'savings_com_shortcode_handler' );
function savings_com_shortcode_handler( $shortcode_atts ) {
       
	  $config = get_option('savings_com_config');
	  $enpoint_info = get_variables($config['endpoint']);
    	$browse_store = $_REQUEST['browse_store'] ? true : false;
    	$store_letter = $_REQUEST['store_letter'];
    	$merchant_id = $_REQUEST['merchant_ids'] ? true : false;
    	$merchant_name = $_REQUEST['merchant_name'] ? true : false;
    	$keyword = $_REQUEST['keyword'] ? true : false;
    	$category_name = $_REQUEST['category_name'] ? $_REQUEST['category_name'] : false;
    	$search_term = htmlentities(stripslashes($_REQUEST['keyword']));
	if( $shortcode_atts['category_ids'] ){
	    $shortcode_atts['category_ids'] = unserialize( $shortcode_atts['category_ids'] );
	}
	//$monetizable =  ($config['monetizable'] == 'no')?'FALSE':'TRUE';
	//Set default parameters 
	$default_params = array(
	       'start_index' => '0',
		      'page_size' => '20',
           'predefined_query' => 'TOP_7DAYS',
             'creator_filter' => 'ALL',
                   'keyword' => '',
                      'tags' => '',
               'category_ids' => array(),
               'merchant_ids' => '',
       'excluded_merchant_ids' => '',
          'monetizeable_only' => '',
          'featured_1'=>'20235',
          'featured_2'=>'29059',
          'featured_3'=>'20729');
	
	/*
	//Replace defaults with GET request parameters
	$request_params = shortcode_atts( $default_params, $_GET );
	
	//Replace defaults with shortcode parameters
	$final_params = shortcode_atts( $request_params, $shortcode_atts );
  */
  //Replace defaults with shortcode parameters
	$request_param = shortcode_atts( $default_params, $shortcode_atts );
	//Replace defaults with GET request parameters
	$final_params = shortcode_atts( $request_param, $_GET );
	//var_dump($final_params['category_ids']);
	
  
  
  //if category ids is blank, use the featured.
  if(count($final_params['category_ids'])<1) {
    $final_params['category_ids']=array($final_params['featured_1'],$final_params['featured_2'],$final_params['featured_3']);
  }
  if($final_params['merchant_ids']!='') {
    $final_params['category_ids'] = array();
    $final_params['predefined_query']='V2_SCORE';
  }
  if($final_params['keyword']!='') {
    $final_params['category_ids'] = array();
    $final_params['predefined_query']='';
    $keyword_encode = rawurlencode($final_params['keyword']);
    $final_params['keyword']=$keyword_encode;
  }
  
  //var_dump($final_params);      
        $deals_data = savings_com_get_data( 'getDeals' , $final_params );
        $deals = $deals_data->out->deals;
	//var_dump($deals_data);
	$categories_data = savings_com_get_data( 'getCategories' , $final_params );
	$categories = $categories_data->categories;
	foreach ($categories as $cat) {
	  $new_categories[$cat->id] = $cat->name;
	}

	if($merchant_id) {
	  $merchant_data = savings_com_get_data( 'getMerchantsById' , $final_params );
	  $merchant = $merchant_data->out;
  }
	$request_uri = explode('?',$_SERVER['REQUEST_URI']);
	$request_uri = $request_uri[0];
        $html = '<div id="savings-com-page" class="';
        if(!$merchant_id){
			    $html .= '';
	      } else {
	        $html .= 'merchant-page';
	      }
        
        $html.='">';
        
        $html .= '<div id="savings-com-page-navigation-filters">
			<form action="'.str_replace( '%7E', '~', $request_uri).'" method="get" id="savings-com-form">
			    '; if($_GET['page_id']!='') { '<input type="hidden" name="page_id" value="'.$_GET['page_id'].'">'; }
		  $html .='<p class="savings-search"><label for="keyword">Search by store, brand or keyword</label> <input class="text" type="text" id="keyword" name="keyword" value="'.stripslashes($_GET['keyword']).'"/><input id="savings-com-search-submit" type="image" src="'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/submit.png" value="Search"/></form>
		  <a rel="nofollow" ';  $html .= 'class="logo-link" href="'.$enpoint_info['home_link'].'?placementid='.get_option('savings_com_placementid').'"><img src="'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/logo-'.$enpoint_info['base'].'.png"/></a>
		  <script>jQuery(document).ready(function(){
  jQuery("#savings-com-page label").inFieldLabels()}); </script></p><br style="clear:both"/>';   
		 if(!$browse_store && !$merchant_id){ 
		$html .= '<span class="savings-com-featured-categories">Featured Categories:</span><br style="clear:both"/>
		  <ul class="featured-categories">
		    <li>
			 <a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?category_ids='.$final_params['featured_1'].'&category_name='.urlencode($new_categories[$final_params['featured_1']]).'">'.$new_categories[$final_params['featured_1']].'</a>
			</li><li>
			 <a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?category_ids='.$final_params['featured_2'].'&category_name='.urlencode($new_categories[$final_params['featured_2']]).'">'.$new_categories[$final_params['featured_2']].'</a>
			</li><li>
			 <a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?category_ids='.$final_params['featured_3'].'&category_name='.urlencode($new_categories[$final_params['featured_3']]).'">'.$new_categories[$final_params['featured_3']].'</a>
		</li>
		  
		  </ul>
		  <p class="category-select">';
		  $html .= '<form action="'.str_replace( '%7E', '~', $request_uri).'" method="get" id="savings-com-form-category">';
		  $html .= '<input type="hidden" name="category_name" id="category_name" value="'.$_GET['category_name'].'"/><select id="cmb_category_ids" name="category_ids[]">';
		    if(!$_GET['category_ids'] || $_GET['category_ids'] == '' ){
			$html .=  '<option value="0" selected="selected">Browse by Category</option>';
		    } else {
			$html .=  '<option value="0">Browse by Category</option>';
		    }		
		    foreach($categories as $cat){
			
			    if(in_array( $cat->id, (array) $_GET['category_ids'] ) ){
			        $html .=  '<option value="'.$cat->id.'" selected="selected">'.$cat->name.'</option>';
			    } elseif(in_array($cat->id, $request_param['category_ids'])) {
			        $html .=  '<option value="'.$cat->id.'">'.$cat->name.'</option>';
			    }
		    }
		
		$html .= '</select></form><script>jQuery("#cmb_category_ids").change(function() {
		  jQuery("#category_name").val(jQuery("#cmb_category_ids option:selected").text());
		  jQuery("#savings-com-form-category").submit();
		})</script>';
		  
		  
		  $html .='</p>'; 
		} else { 
		    $categoryhtml ='';
		    if($merchant && is_array($merchant->categoryIds)) {
		      foreach($categories as $cat){
			      if(in_array($cat->id , $merchant->categoryIds)){
			          $categoryhtml =  '<a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?category_ids='.$cat->id.'&category_name='.urlencode($cat->name).'">'.$cat->name.'</a> > ';
			      } else {
			         
			      }
		      }
		    
		
		  $html .= '<p class="savings-com-breadcrumbs"><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'">Deal Home</a> > '.$categoryhtml.'<a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?&merchant_ids='.$merchant->id.'&merchant_name='.$merchant->name.'">'.$merchant->name .'</a></p>';
		  } else {
		    $html .= '<p class="savings-com-breadcrumbs"><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'">Deal Home</a> > <a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1">Browse Stores by Letter</a></p>';
		  }
		}
			$html .= '</form><br style="clear:both"/>
			</div>';
        
     if(!$keyword && !$merchant_id && !$category_name && !$browse_store){ 
     $staff_picks =  ($_REQUEST['predefined_query'] == 'STAFF_PICKS')?"active":"inactive";
     $popular =  ($_REQUEST['predefined_query'] == 'RECENTLY_POPULAR')?"active":"inactive";
     $newest =  ($_REQUEST['predefined_query'] == 'NEWEST')?"active":"inactive";
        $html .='
		<div id="savings-nav">
		    
		    <ul id="savings-com-page-tabs-list">
			
			<li class="savings-com-page-tab savings-category-btn">
			 <a rel="nofollow" class="'.$popular.'" href="'.str_replace( '%7E', '~', $request_uri).'?predefined_query=RECENTLY_POPULAR">Popular Deals</a>
			</li> 
			<li class="savings-com-page-tab savings-category-btn">
			 <a rel="nofollow" class="'.$staff_picks.'" href="'.str_replace( '%7E', '~', $request_uri).'?predefined_query=STAFF_PICKS">Recommended Deals</a>
			</li>
			
			<li class="savings-com-page-tab savings-category-btn">
			 <a rel="nofollow" class="'.$newest.'" href="'.str_replace( '%7E', '~', $request_uri).'?predefined_query=NEWEST">New Deals</a>
			</li>
			

			
			';
	  
		//header section	
	  $html .= '<ul>
		</div>';}
		if($keyword){	
		 $html .= '<br style="clear:both"/><h3 class="savings-com">Your Search Results: "'.$search_term.'"</h3>';
		} 
		
		if($category_name){	
		 $html .= '<br style="clear:both"/><h3 class="savings-com">'.$category_name.'</h3>';
		} 
		
		if($merchant_id){	
		 $html .= '<br style="clear:both"/><h3 class="savings-com"><a rel="nofollow"  target="_blank" href="'.$merchant->displayUrl.'">'.$merchant->name.' Deals</a><img src="'.$merchant->imageUrl.'"/></h3> ';
		} 
		
		if($browse_store && !$store_letter){	
		 $html .= '<br style="clear:both"/><h3 class="savings-com">Browse Stores by Letter</h3>';
		} 
		
		if($browse_store && $store_letter){	
		 $html .= '<br style="clear:both"/><h3 class="savings-com">Browse Stores by Letter: '.$store_letter.'</h3>';
		} 
		//end header section
		
	if(!$browse_store){	
    if(!$merchant_id){
			$html .= '<div id="savings-com-page-list" class="savings-posts">';
	  } else {
	    $html .= '<div id="savings-com-page-list" class="savings-posts merchant-page">';
	  }
		$codes_array = array();
		
		if(empty($deals)){
		  unset($deals);
		}
		if(isset($deals)) {
		$i=1; $now = getdate(time());
		
		if(!$merchant_id) {
		
		  if(is_array($deals)) {shuffle($deals);}
		  }
		  else {
			$final_params['predefined_query']='TOP_DEAL';
			$deals_data_second = savings_com_get_data( 'getDeals' , $final_params );
			$deals_second = $deals_data_second->out->deals;
			if(!is_array($deals_second)) {
				$deals_second = array();
			}
			$deals = $deals_second + $deals;
		  }
		foreach($deals as $deal){ //var_dump($deal->couponCode);
		  $deal->expireDate = (is_object($deal->expireDate))?"0":$deal->expireDate;
		  $date = explode('+',$deal->expireDate); $date= getdate(strtotime($date[0])); 
		    $uk = ($enpoint_info['base'] =='uk')?'_uk':'';
		    $coupon_code = is_object($deal->couponCode) ? '<img src="'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/shop'.$uk.'.png"/>' : '<img src="'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/Savings_get_code_0'.$i.$uk.'.png"/>';
		    $i++; if($i==5) {$i=1;}
		    
		    $code_tooltip = is_object($deal->couponCode) ? 'Click to Activate Coupon & Open Site' : 'Click to Copy Code & Open Site';
		    $class_tooltip = is_object($deal->couponCode) ? 'shop' : 'buy';
		    $codes_array[(string) $deal->id] = is_object($deal->couponCode) ? '' : $deal->couponCode;
			  $code = is_object($deal->couponCode) ? '' : $deal->couponCode;
		    $html .= '<div class="savings-com-deal-item savings-post">';
		    if(!$merchant_id){
        $html .= '<div class="savings-com-deal-merchant">
				    <a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?merchant_ids='.$deal->merchantId.'&merchant_name='.$deal->merchantName.'">
					<img width="120" src="'.$deal->merchantImageUrl.'?height=30&amp;width=120" alt="'.$deal->merchantName.' coupons"><p style="margin-bottom:0px">'.$deal->merchantName.'</p>
				    </a>
				</div>';
				}
				$html .= '<div class="savings-com-deal-title" title="'.$code_tooltip.'" id="deal-title-'.$deal->id.'">
				    <a rel="nofollow" target="_blank" class="savings-com-deal-title" title="'.$code_tooltip.'" target="_blank" href="'.$deal->dealUrl.'">'.$deal->title.'</a>
				</div>
				<a rel="nofollow" target="_blank" href="'.$deal->dealUrl.'" class="savings-com-deal-code" title="'.$code_tooltip.'" id="'.$deal->id.'">
				
				'. $coupon_code .'
				
				</a><br style="clear:both"/>';
				if(!$merchant_id){
				  $html .='<a rel="nofollow" class="details""><img class="more-details" src="'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/more-details.png"></a>';
				} else {
				  $html .='<a rel="nofollow" class="merchant-details"><img class="more-details" src="'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/merchant-more-details.png"></a>';
				}
				
				$html .= '<p class="savings-com-expires">';
				if($date['mon'] == $now['mon'] && $date['mday'] == $now['mday'] && $date['year'] == $now['year']) {
				  $html .= '<span class="today">EXPIRES TODAY!</span>';
				} elseif ($date['mon'] == $now['mon'] && $date['mday'] == ($now['mday']+1) && $date['year'] == $now['year']) {
				  $html .= '<span class="tommorrow">Expires Tomorrow</span>';
				} elseif ($date['mon'] == 1 && $date['mday'] == 1 && $date['year'] == 1970) {
				  $html .= '';
				} else {
				  $html .= '<span class="normal">Deal Expires ';
				   
				      if($endpoint_info['base'] == 'uk') {
				        $html .= $date['mday']."/".$date['mon']."/".$date['year'];
				      } else {
				        $html .= $date['mon']."/".$date['mday']."/".$date['year'];
				      }
				    
				  $html .='</span>';
				}
				
				$html.='</p>
				
				</div>
				
				<div class="savings-com-deal-footer" style="display:none">
				    <p class="savings-com-deal-description"}>'.$deal->description.'</p>
				</div>
			    ';
			    $html .= "<script>
			    jQuery(document).ready(function() {
			    
			      jQuery('#".$deal->id."').tipTip({defaultPosition: 'right', maxWidth:'auto', classname:'".$class_tooltip."'});
			      jQuery('#".$deal->id." img').tipTip({defaultPosition: 'right', maxWidth:'auto', classname:'".$class_tooltip."'});
			      jQuery('#deal-title-".$deal->id."').tipTip({defaultPosition: 'left', maxWidth:'auto', classname:'".$class_tooltip."'});
			    
			      jQuery('#deal-title-".$deal->id."').zclip({
            path:'".get_site_url()."/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf',
            copy:'".$code."',
            afterCopy:function(){
                window.open(jQuery('#".$deal->id."').attr('href'),'_blank');
            }
            });
            
            jQuery('#".$deal->id."').zclip({
              path:'".get_site_url()."/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf',
              copy:'".$code."',
              afterCopy:function(){
                  window.open(jQuery(this).attr('href'),'_blank');
              }
            }); 
            
           /* jQuery('#".$deal->id." img').load(function() {  
              jQuery('#".$deal->id."').zclip({
              path:'".get_site_url()."/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf',
              copy:'".$code."',
              afterCopy:function(){
                  window.open(jQuery(this).attr('href'),'_blank');
              }
              });
            });   */
			    });
			    
			    </script>";
		}
		$html .= '<div id=savings-com-pagination>';
		//Define deals pagination parameters
		$current = $_REQUEST['pagination'] > 1 ? $_REQUEST['pagination'] : $current = 1;
		
		$pagination = array(
			'base' => @add_query_arg('pagination','%#%'),
			'format' => '',
			'total' => $deals_data->out->totalRecordsAvailable / $final_params['page_size'],
			'current' => $current,
			'show_all' => false,
			'type' => 'plain'
			);
		//print pagination
		$html .= paginate_links( $pagination ).'</div>
		
		
		<div class="savings-com-footer"><p>
		<a  '; if($browse_store || $store_letter !='' || $merchant_id || $keyword || $category_name ) { $html .= ' rel="nofollow" '; }; $html .=' href="'.$enpoint_info['about_link'].'" target="_blank">About Savings.com</a>
		<a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1">Browse by Store</a>
		<a class="mailto" href="mailto:'.$enpoint_info['email'].'">Report Problem</a>
		</p>
		</div>
		
		</div></div>';
		$codes_json = json_encode($codes_array);
		$html .= '<script type="text/javascript">
			    var codes = '.$codes_json.';
			    /*jQuery(".savings-com-deal-code").tipTip({defaultPosition: "right", maxWidth:"auto"});
			    jQuery(".savings-com-deal-title").tipTip({defaultPosition: "left", maxWidth:"auto"});*/
			    jQuery("a.details").click(function() { jQuery(this).parent().next().slideToggle(); });
			    
			    jQuery("a.details").click(function() { 
			      old = jQuery(this).find("img").attr("src");
			      if (old=="'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/more-details.png") {
			        jQuery(this).find("img").attr("src","'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/view-more-open.png");
			      } else {
			        jQuery(this).find("img").attr("src","'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/more-details.png");
			      }
			    
			    
			     });
			     jQuery("a.merchant-details").click(function() { jQuery(this).parent().next().slideToggle(); });
			     jQuery("a.merchant-details").click(function() { 
			      old = jQuery(this).find("img").attr("src");
			      if (old=="'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/merchant-more-details.png") {
			        jQuery(this).find("img").attr("src","'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/merchant-more-details-open.png");
			      } else {
			        jQuery(this).find("img").attr("src","'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/merchant-more-details.png");
			      }
			    
			    
			     });
			    
			  </script>';
			  } else {
			  
			    if($merchant_id){	
			      $html .= '<div id="whoops">
			    <h3>Sorry,  we couldn’t find any deals at this time.</h3>
 <br/><br/>
<b>Follow instructions below:</b><br/><br/>
 <ul>  
    <li>Step 1.  Take a deep breath.</li>
    <li>Step 2.  Think of something else to put into that search bar.</li>
    <li>Step 3.  Search for it!</li>
    <li>Step 4.  Forget that you were ever here.</li>
 </ul><br/>
			    <br/><br/>
			    <p style="text-align:center"><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'">Return to Main Deals Page</a><br/><br/><br/>
			    
			    <a rel="nofollow" href="'.$enpoint_info['home_link'].'" target="_blank"><img src="'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/Savings_logo_footer_search.png"/></a>
			    </p>
			    </div>
			      <div id=savings-com-pagination></div>
	 <div class="savings-com-footer"><p>
		<a  '; if($browse_store || $store_letter !='' || $merchant_id || $keyword || $category_name ) { $html .= ' rel="nofollow" '; }; $html .=' href="'.$enpoint_info['about_link'].'" target="_blank">About Savings.com</a>
		<a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1">Browse by Store</a>
		<a class="mailto" href="mailto:'.$enpoint_info['email'].'">Report Problem</a>
		</p>
		</div></div></div>';
			    } else {
			    
			    
			  
			    $html .= '<div id="whoops">
			    <h3>Whoops! You broke the Internet?</h3>
<br/>
Actually you didn’t but we really couldn’t find anything that matches your search.
 <br/><br/>
<b>Perhaps try one of these suggestions:</b><br/><br/>
 <ul>  
    <li>- Check your spelling - it\'s probably misspeled :)</li>
    <li>- Try searching for similar items or something that\'s actually popular</li>
    <li>- Search for a store that might carry the item you\'re looking for</li>
    <li>- Search for a store by domain name (eg: apple.com)</li>
    <li>- Use a shorter phrase   </li>
 </ul><br/>
Oh come on - don\'t stop now, go up there and try it again.
			    <br/><br/>
			    <p style="text-align:center"><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'">Return to Main Deals Page</a><br/><br/><br/>
			    
			    <a rel="nofollow" href="'.$enpoint_info['home_link'].'" target="_blank"><img src="'.get_site_url().'/wp-content/plugins/savingscom-coupon-plugin-and-widget/images/Savings_logo_footer_search.png"/></a>
			    </p>
			    </div>
			    
			    
			    <div id=savings-com-pagination></div>
	 <div class="savings-com-footer"><p>
		<a '; if($browse_store || $store_letter !='' || $merchant_id || $keyword || $category_name ) { $html .= ' rel="nofollow" '; }; $html .='  href="'.$enpoint_info['about_link'].'" target="_blank">About Savings.com</a>
		<a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1">Browse by Store</a>
		<a class="mailto" href="mailto:'.$enpoint_info['email'].'">Report Problem</a>
		</p>
		</div></div></div>';
			    }
			  }
	} else {
	//THIS IS THE BROWSE MERCHANT PAGE
	if($store_letter != '') {
	 include_once dirname( __FILE__ ) . '/savings-merchants/'.strtolower($store_letter).'.php';
	 $i = 1; $col1 =''; $col2='';
	 foreach($wp_savings_merchants as $merchant) {
	    if($i == '1') {
	      $col1 .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?&merchant_ids='.$merchant["id"].'&merchant_name='.urlencode($merchant["name"]).'">'.$merchant["name"].'</a></li>';
	    } else {
	      $col2 .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?&merchant_ids='.$merchant["id"].'&merchant_name='.urlencode($merchant["name"]).'">'.$merchant["name"].'</a></li>';
	    }
	    
	    $i++; $i = ($i==3)?1:$i;
	 }$html .= '<div class="merchant-name-lists"><ul class="half">'.$col1.'</ul><ul class="half">'.$col2.'</ul></div><br style="clear:both"/>
	 <div id=savings-com-pagination></div>
	 <div class="savings-com-footer"><p>
		<a '; if($browse_store || $store_letter !='' || $merchant_id || $keyword || $category_name ) { $html .= ' rel="nofollow" '; }; $html .='  href="'.$enpoint_info['about_link'].'" target="_blank">About Savings.com</a>
		<a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1">Browse by Store</a>
		<a class="mailto" href="mailto:'.$enpoint_info['email'].'">Report Problem</a>
		</p>
		</div></div>
	 ';
	} else {
	  $html .= '<div id="savings-com-page-merchants">
			<ul id="savings-com-page-merchants-list" class="half">';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=A">A</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=B">B</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=C">C</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=D">D</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=E">E</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=F">F</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=G">G</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=H">H</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=I">I</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=J">J</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=K">K</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=L">L</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=M">M</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=N">N</a></li>';
	  $html .= '</ul><ul id="savings-com-page-merchants-list 2" class="half">';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=O">O</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=P">P</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=Q">Q</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=R">R</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=S">S</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=T">T</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=U">U</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=V">V</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=W">W</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=X">X</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=Y">Y</a></li>';
	  $html .= '<li><a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=Z">Z</a></li>';
		$html .= '<li><a rel="nofollow"  href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1&store_letter=OTHER">#</a></li>';	
			
	  $html .= '</ul><br style="clear:both"/></div><div id=savings-com-pagination></div>
	 <div class="savings-com-footer"><p>
		<a '; if($browse_store || $store_letter !='' || $merchant_id || $keyword || $category_name ) { $html .= ' rel="nofollow" '; }; $html .='   href="'.$enpoint_info['about_link'].'" target="_blank">About Savings.com</a>
		<a rel="nofollow" href="'.str_replace( '%7E', '~', $request_uri).'?browse_store=1">Browse by Store</a>
		<a class="mailto" href="mailto:'.$enpoint_info['email'].'">Report Problem</a>
		</p>
		</div></div>';
	}
	
	}
	return $html;
}
