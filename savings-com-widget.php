<?php
/**
 * @package Savings_com
 */

add_action('init', 'widget_savings_com_register');

if (!function_exists('get_site_url')) {
    function get_site_url() {
      return get_option( 'siteurl' );
    }
} 

function widget_savings_com_register() {
	if ( function_exists('register_sidebar_widget') ) :
	function widget_savings_com($args) {
		extract($args);
		$options = get_option('widget_savings_com');
		$params = array(
	        'start_index' => '0',
		  'page_size' => $options['limit'],
           'predefined_query' => 'TOP_7DAYS',
             'creator_filter' => 'ALL',
                    'keyword' => '',
                       'tags' => '',
               'category_ids' => $options['category_ids'],
               'merchant_ids' => '',
      'excluded_merchant_ids' => '',
          'monetizeable_only' => ''
				);
				
				if($params['category_ids'] == '') {
				  $params['category_ids'] = array(20235, 29059, 20729);
				}
		
		$config = get_option('savings_com_config');
    $enpoint_info = get_variables($config['endpoint']);
		
		$deals_data = savings_com_get_data( 'getDeals' , $params );
		$deals = $deals_data->out->deals;
		?>
			<?php echo $before_widget; ?>
				<?php echo $before_title . $options['title'] . $after_title; ?>
			<div id="savings-com-widget-wrap">
				<p>
				  <label for="savings-com-search">Search for Deals!</label>
				  <form id="widget-keyword-search">
				  <input type="text" value="" name="savings-com-search" id="savings-com-search" style="float:left"/><a id="savings-com-search-click">Click</a>
				  <input type="submit" style="display:none"/>
				  </form>
				  
				</p>
				<script>jQuery(document).ready(function(){
  jQuery("#savings-com-widget-wrap label").inFieldLabels()}); </script>
				<select id="savings-com-category-select">
				  <?php $categories_data = savings_com_get_data( 'getCategories' , $final_params );
	        $categories = $categories_data->categories;
	        
	        
	        $html .=  '<option value="0">Categories</option>';
		      foreach($categories as $cat) {
		        $html .=  '<option value="'.$cat->id.'">'.$cat->name.'</option>';
		      }
	        echo $html;
	        ?>
	
				</select><br style="clear:both"/>
				
				<script>
				jQuery('#savings-com-search-click').click(function() {    
				    jQuery('#savings-com-search-click').addClass('loading');
				    jQuery.post(
        '<?php echo  get_admin_url();?>admin-ajax.php',
        {
            action : 'savings_com',
            term : jQuery('#savings-com-search').val()
        },
        function( response ) {
            jQuery('ul.savings-com-widget-list').html(response.substring(0, response.length-1));
            jQuery('#savings-com-search-click').removeClass('loading');
            //console.log( response );
        });
        });
        
        jQuery('#widget-keyword-search').submit(function(event) {
          event.preventDefault();
          jQuery('#savings-com-search-click').addClass('loading');
          jQuery.post(
          '<?php echo  get_admin_url();?>admin-ajax.php',
          {
              action : 'savings_com',
              term : jQuery('#savings-com-search').val()
          },
          function( response ) {
              jQuery('ul.savings-com-widget-list').html(response.substring(0, response.length-1));
              jQuery('#savings-com-search-click').removeClass('loading');
              //console.log( response );
          });
        });
        
        
        jQuery('#savings-com-category-select').change(function() {
           jQuery.post(
        '<?php echo  get_admin_url();?>admin-ajax.php',
        {
            action : 'savings_com',
            category : jQuery('#savings-com-category-select').val()
        },
        function( response ) {
            jQuery('ul.savings-com-widget-list').html(response.substring(0, response.length-1));
            //console.log( response );
        });
        
        });
           
      
				
				
				</script>
				<?php $codes_array = array();?>
				<div id="savings-com-widget-content">
					<ul class="savings-com-widget-list">
						<?php foreach($deals as $deal): ?>
						<?php $coupon_code = is_object($deal->couponCode) ? 'Shop Now!' : 'Get Code!';
						$code_tooltip = is_object($deal->couponCode) ? 'Click to Activate Coupon & Open Site' : 'Click to Copy Code & Open Site';
						$class_tooltip = is_object($deal->couponCode) ? 'shop' : 'buy';
		    
						$codes_array[(string) $deal->id] = is_object($deal->couponCode) ? '' : $deal->couponCode;
						$code = is_object($deal->couponCode) ? '' : $deal->couponCode;
						$uk = ($enpoint_info['base'] =='uk')?'uk':'';
						?>
							<li class="savings-com-widget-item" style="position:relative">
								<!--<a class="savings-com-deal-title" href="<?php echo $deal->dealUrl; ?>"><?php echo $deal->title; ?></a>-->
								      
				        <a target="_blank" class="savings-com-deal-merchant" id="widget-deal-merchant-<?php echo $deal->id?>"  title="<?php echo $code_tooltip;?>" href="<?php echo $deal->dealUrl ?>">
					      <img width="120" src="<?php echo $deal->merchantImageUrl?>?width=120" alt="<?php echo $deal->merchantName?> coupons"><br/>
					      <div class="merchant-name"><?php echo $deal->merchantName?></div>
				          </a>
				     <a target="_blank" href="<?php echo $deal->dealUrl?>" class="savings-com-deal-code <?php echo $enpoint_info['base']?> <?php echo $coupon_code?>" title="<?php echo $code_tooltip;?>" id="widget-<?php echo $deal->id?>"><span class="<?php echo $enpoint_info['base']?>"><?php echo $coupon_code?></span></a>
				      <br style="clear:both"/>
				          <a target="_blank" class="savings-com-deal-title" id="widget-deal-title-<?php echo $deal->id?>" title="<?php echo $code_tooltip;?>" href="<?php echo $deal->dealUrl?>"><?php echo $deal->title?></a>
				<script>
				jQuery('#widget-<?php echo $deal->id ?>').tipTip({defaultPosition: 'left', maxWidth:'auto', classname:'<?php echo $class_tooltip?>'});
			  jQuery('#widget-deal-merchant-<?php echo $deal->id?>').tipTip({defaultPosition: 'right', maxWidth:'auto', classname:'<?php echo $class_tooltip?>'});
			  jQuery('#widget-deal-title-<?php echo $deal->id ?>').tipTip({defaultPosition: 'right', maxWidth:'auto', classname:'<?php echo $class_tooltip?>'});
				
				jQuery('#widget-<?php echo $deal->id ?>').zclip({
        path:'<?php echo get_site_url()?>/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf',
        copy:'<?php echo $code?>',
        afterCopy:function(){
            window.open(jQuery(this).attr('href'),'_blank');
        }
        }); 
        jQuery('#widget-deal-merchant-<?php echo $deal->id ?>').zclip({
        path:'<?php echo get_site_url()?>/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf',
        copy:'<?php echo $code?>',
        afterCopy:function(){
            window.open(jQuery(this).attr('href'),'_blank');
        }
        }); 
        jQuery('#widget-deal-title-<?php echo $deal->id ?>').zclip({
        path:'<?php echo get_site_url()?>/wp-content/plugins/savingscom-coupon-plugin-and-widget/ZeroClipboard.swf',
        copy:'<?php echo $code?>',
        afterCopy:function(){
            window.open(jQuery('#widget-<?php echo $deal->id?>').attr('href'),'_blank');
        }
        });
        </script>
				      
							</li>
						<?php endforeach; ?>
					</ul>
					<?php $codes_json = json_encode($codes_array);
		echo '<script type="text/javascript">var widget_codes = '.$codes_json.';
		/*jQuery("#savings-com-widget-content .savings-com-deal-code").tipTip({defaultPosition: "left", maxWidth: "auto"});
		jQuery("#savings-com-widget-content .savings-com-deal-merchant").tipTip({defaultPosition: "right", maxWidth: "auto"});
		jQuery("#savings-com-widget-content .savings-com-deal-title").tipTip({defaultPosition: "right", maxWidth: "auto"});*/
		jQuery(document).ready(function(){ 
		jQuery("li.savings-com-widget-item a.savings-com-deal-merchant").one("click", function() {
            jQuery(this).next().addClass("revealed");
            var code_index = jQuery(this).next().attr("id");
            var code_index_split = code_index.split("widget-");
            code_index = code_index_split["1"];
            var code = widget_codes[code_index];
                if(code != ""){
                    jQuery("span", jQuery(this).next()).replaceWith("<span class=\"savings-com-code-revealed\">"+code+"</span>");
                } else {
                    jQuery("span", jQuery(this).next()).replaceWith("<span class=\"savings-com-code-noneed\">No code needed!</span>");
                }
            });
            
   jQuery("li.savings-com-widget-item a.savings-com-deal-title").one("click", function() {
            jQuery(this).prev().prev().addClass("revealed");
            var code_index = jQuery(this).prev().prev().attr("id");
            var code_index_split = code_index.split("widget-");
            code_index = code_index_split["1"];
            var code = widget_codes[code_index];
                if(code != ""){
                    jQuery("span", jQuery(this).prev().prev()).replaceWith("<span class=\"savings-com-code-revealed\">"+code+"</span>");
                } else {
                    jQuery("span", jQuery(this).prev().prev()).replaceWith("<span class=\"savings-com-code-noneed\">No code needed!</span>");
                }
            });
            
   jQuery("li.savings-com-widget-item a.savings-com-deal-code").one("click", function() {
            jQuery(this).addClass("revealed");
            var code_index = jQuery(this).attr("id");
            var code_index_split = code_index.split("widget-");
            code_index = code_index_split["1"];
            var code = widget_codes[code_index];
            
                if(code != ""){
                    jQuery("span", this).replaceWith("<span class=\"savings-com-code-revealed\">"+code+"</span>");
                } else {
                    jQuery("span", this).replaceWith("<span class=\"savings-com-code-noneed\">No code needed!</span>");
                }
            }); 
		
		});
		
		
		
		
		</script>';?>
				</div>
				
				<a target="_blank" rel="nofollow" id="savings-com-widget-home<?php echo $enpoint_info['base']?>" href="<?php echo $enpoint_info['home_link']?>?placementid=<?php echo get_option('savings_com_placementid')?>">Savings.com</a>
				 <a rel="nofollow" target="_blank" id="savings-com-widget-deals" href="<?php echo $enpoint_info['home_link']?>?placementid=<?php echo get_option('savings_com_placementid')?>">See More Deals >></a><br style="clear:both"/>
			</div> 
			<?php echo $after_widget; 
	}
	
	function widget_savings_com_control() {
		$options = $newoptions = get_option('widget_savings_com') ? get_option('widget_savings_com') : add_option('widget_savings_com');
		//var_dump($options);
		if ( isset( $_POST['savings-com-submit'] ) && $_POST["savings-com-submit"] ) {
			$newoptions['title'] = strip_tags(stripslashes($_POST["savings-com-title"]));
			$newoptions['limit'] = strip_tags(stripslashes($_POST["savings-com-limit"]));
			$newoptions['category_ids'] = $_POST['category_ids'] ? $_POST['category_ids'] : '';
			if ( empty($newoptions['title']) ) $newoptions['title'] = __('');
			if ( empty($newoptions['limit']) ) $newoptions['limit'] = '5';
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_savings_com', $options);
		}
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$limit = $options['limit'];
		
		
		
		$categories_data = savings_com_get_data( 'getCategories' , $final_params );
		$categories = $categories_data->categories;
	?>
	
				<p><label for="savings-com-title"><?php _e('Title:'); ?> <input style="width: 225px;" id="savings-com-title" name="savings-com-title" type="text" value="<?php echo $title; ?>" /></label></p>
				
				<p><label for="savings-com-title"><?php _e('Category:'); ?> 
				<select id="cmb_widget_category_ids" name="category_ids" style="width: 225px;">
				<option value="" <?php if(!$options['category_ids'] || $options['category_ids'] == '' ): ?>selected="selected"<?php endif; ?>>Savings.com Default</option>
				<?php foreach($categories as $cat): ?>
                                    <?php if($cat->id == $options['category_ids']): ?>
                                        <option value="<?php echo $cat->id; ?>" selected="selected"><?php echo $cat->name; ?></option>
                                        <?php else: ?>
                                        <option value="<?php echo $cat->id; ?>"><?php echo $cat->name; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
				</select>
				</p>
				<p><label for="savings-com-limit"><?php _e('Number of items:'); ?> <input style="width: 225px;" id="savings-com-limit" name="savings-com-limit" type="text" value="<?php echo $limit; ?>" /></label></p>
				<input type="hidden" id="savings-com-submit" name="savings-com-submit" value="1" />
	<?php
	}
	
	if ( function_exists( 'wp_register_sidebar_widget' ) ) {
		wp_register_sidebar_widget( 'savings-com', 'Savings.com', 'widget_savings_com', null, 'savings-com');
		wp_register_widget_control( 'savings-com', 'Savings.com', 'widget_savings_com_control', null, 75, 'savings-com');
	} else {
		register_sidebar_widget('Savings.com', 'widget_savings_com', null, 'savings-com');
		register_widget_control('Savings.com', 'widget_savings_com_control', null, 75, 'savings-com');
	}
	endif;
}
?>
