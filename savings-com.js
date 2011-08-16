jQuery(document).ready(function(){
    jQuery('.logo-link img').load(function() { 
    
     jQuery('p.savings-search input[type="text"]').width(jQuery('p.savings-search').width() - jQuery('p.savings-search input[type="image"]').width() - jQuery('a.logo-link').width() - 20);
    });
   
    
              
	jQuery('#savings-com-page .savings-com-deal-code').one('click', function() {
            jQuery(this).addClass('revealed');
            var code_index = jQuery(this).attr("id");
            var code = codes[code_index];
                if(code != ''){
                    jQuery('img', this).replaceWith('<span class="savings-com-code-revealed">'+code+'</span>');
                } else {
                    jQuery('img', this).replaceWith('<span class="savings-com-code-noneed">No code needed!</span>');
                }
            }); 
            
   jQuery('div.savings-com-deal-title').one('click', function() {
            jQuery(this).addClass('revealed');
            var code_index = jQuery(this).next().attr("id");
            var code = codes[code_index];
                if(code != ''){
                    jQuery('img', jQuery(this).next()).replaceWith('<span class="savings-com-code-revealed">'+code+'</span>');
                } else {
                    jQuery('img', jQuery(this).next()).replaceWith('<span class="savings-com-code-noneed">No code needed!</span>');
                }
            });
            
   jQuery('li.savings-com-widget-item a.savings-com-deal-merchant').one('click', function() {
            jQuery(this).next().addClass('revealed');
            var code_index = jQuery(this).next().attr("id");
            var code_index_split = code_index.split('widget-');
            code_index = code_index_split['1'];
            var code = widget_codes[code_index];
                if(code != ''){
                    jQuery('span', jQuery(this).next()).replaceWith('<span class="savings-com-code-revealed">'+code+'</span>');
                } else {
                    jQuery('span', jQuery(this).next()).replaceWith('<span class="savings-com-code-noneed">No code needed!</span>');
                }
            });
            
   jQuery('li.savings-com-widget-item a.savings-com-deal-title').one('click', function() {
            jQuery(this).prev().prev().addClass('revealed');
            var code_index = jQuery(this).prev().prev().attr("id");
            var code_index_split = code_index.split('widget-');
            code_index = code_index_split['1'];
            var code = widget_codes[code_index];
                if(code != ''){
                    jQuery('span', jQuery(this).prev().prev()).replaceWith('<span class="savings-com-code-revealed">'+code+'</span>');
                } else {
                    jQuery('span', jQuery(this).prev().prev()).replaceWith('<span class="savings-com-code-noneed">No code needed!</span>');
                }
            });
            
   jQuery('li.savings-com-widget-item a.savings-com-deal-code').one('click', function() {
            jQuery(this).addClass('revealed');
            var code_index = jQuery(this).attr("id");
            var code_index_split = code_index.split('widget-');
            code_index = code_index_split['1'];
            var code = widget_codes[code_index];
                if(code != ''){
                    jQuery('span', this).replaceWith('<span class="savings-com-code-revealed">'+code+'</span>');
                } else {
                    jQuery('span', this).replaceWith('<span class="savings-com-code-noneed">No code needed!</span>');
                }
            }); 
});

function ajaxrefresh() {
  jQuery('li.savings-com-widget-item a.savings-com-deal-merchant').one('click', function() {
            jQuery(this).next().addClass('revealed');
            var code_index = jQuery(this).next().attr("id");
            var code_index_split = code_index.split('widget-');
            code_index = code_index_split['1'];
            var code = widget_codes[code_index];
                if(code != ''){
                    jQuery('span', jQuery(this).next()).replaceWith('<span class="savings-com-code-revealed">'+code+'</span>');
                } else {
                    jQuery('span', jQuery(this).next()).replaceWith('<span class="savings-com-code-noneed">No code needed!</span>');
                }
            });
            
   jQuery('li.savings-com-widget-item a.savings-com-deal-title').one('click', function() {
            jQuery(this).prev().prev().addClass('revealed');
            var code_index = jQuery(this).prev().prev().attr("id");

            var code_index_split = code_index.split('widget-');
            code_index = code_index_split['1'];
            var code = widget_codes[code_index];
                if(code != ''){
                    jQuery('span', jQuery(this).prev().prev()).replaceWith('<span class="savings-com-code-revealed">'+code+'</span>');
                } else {
                    jQuery('span', jQuery(this).prev().prev()).replaceWith('<span class="savings-com-code-noneed">No code needed!</span>');
                }
            });
            
   jQuery('li.savings-com-widget-item a.savings-com-deal-code').one('click', function() {
            jQuery(this).addClass('revealed');
            var code_index = jQuery(this).attr("id");
            var code_index_split = code_index.split('widget-');
            code_index = code_index_split['1'];
            var code = widget_codes[code_index];
                if(code != ''){
                    jQuery('span', this).replaceWith('<span class="savings-com-code-revealed">'+code+'</span>');
                } else {
                    jQuery('span', this).replaceWith('<span class="savings-com-code-noneed">No code needed!</span>');
                }
            }); 

}
