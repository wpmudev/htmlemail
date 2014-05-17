/**
 * JS for HTML Email templates Plugin
 */
jQuery( document ).ready( function($) {
   
    $('.email-templates').slick({
      infinite: false,
      speed: 300,
      slidesToShow: 4,
      slidesToScroll: 1,
      arrows: true,
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 600,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 480,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1
          }
        }
      ]
    });
    jQuery('body').on('click', '.template-holder.slick-slide a', function(e){
        e.preventDefault();
        jQuery('.template-holder.slick-slide a').removeClass('active-theme');
        jQuery(this).addClass('active-theme');
        $theme_name = jQuery(this).attr('href');
        $theme_name = $theme_name.substr($theme_name.indexOf("#") + 1);
        //Get template content
        jQuery.get( ajaxurl, { 'action': 'htmlemail_get_template_data', 'theme': $theme_name }, function(res){
            jQuery('#template-content').val(res.data);
        });
    });
    jQuery('body').on('click', '#preview_template', function(e){
       e.preventDefault();
       $this = jQuery(this);
       //Show content in popup iframe
        title = $this.attr('title'),
        href = $this.attr('href');

        // Open TB
        tb_show(title, href);
        var $previewIframe = $('#TB_iframeContent');

        if( !$previewIframe.length )
                return;

        $template = jQuery('#template-content').val();
        //Replace placeholders
        jQuery.post( ajaxurl, { 'action': 'get_preview_data', 'content': $template }, function(res){
            $template = res.data;
            
            $previewIframe = $previewIframe[$previewIframe.length - 1].contentWindow || frame[$previewIframe.length - 1];
            $previewIframe.document.open();
            $previewIframe.document.write( $template );
            $previewIframe.document.close();
        }, 'json');
        
    });
});


