/**
 * JS for HTML Email templates Plugin
 */
/**
 * Load the SLider
 * @param {type} param
 */
jQuery( document ).ready( function($) {
    /**
     * Add the templat name to load button, on clicking over a template
     */
    jQuery('body').on('click', '.template-holder.slick-slide a', function(e){
        e.preventDefault();

        $load_button = jQuery('#load_template');

        jQuery('.template-holder').removeClass('active-theme');
        jQuery(this).parent(). addClass('active-theme');

        $theme_name = jQuery(this).attr('href');
        $theme_name = $theme_name.substr($theme_name.indexOf("#") + 1);

        $load_button.removeClass('disabled').attr('href', '#' + $theme_name );
        $load_button.find('span.template-name').html( ' ' + $theme_name);
    });
    /**
     * On click of load template button get template default data and add it to textarea
     */
    jQuery('body').on('click', '#load_template', function(e){
        e.preventDefault();
        jQuery('.placeholders-list-wrapper').remove();
        if( ! confirm("All the unsaved template changes will be lost, you want to continue?") ){
            return false;
        }
        //Display reference link for placeholder list table
        jQuery('.list-ref').show();

        $theme_name = jQuery(this).attr('href');
        $theme_name = $theme_name.substr($theme_name.indexOf("#") + 1);
        jQuery.get( ajaxurl, { 'action': 'htmlemail_get_template_data', 'theme': $theme_name }, function(res){
            $textarea = jQuery('#template-content');
            //Append the template content
            $textarea.val(res.data.content);

            //Append the table
            $textarea.after(res.data.placeholders);

        });
    });
    /**
     * On clicking over preview button, Show a preview dialog
     * 
     */
    jQuery('body').on('click', '#preview_template', function(e){
       e.preventDefault();
       $this = jQuery(this);
       //Show content in popup iframe
        title = $this.attr('title'),
        href = $this.attr('href');
        //Show loading gif
        jQuery('.action-wrapper .spinner').css('display', 'inline-block');
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
            //Show loading gif
            jQuery('.action-wrapper .spinner').hide();
        }, 'json');
        
    });
    /**
     * On click over save, check if template contains 'MESSAGE', otherwise alert and return
     */
    jQuery('body').on('click', 'input[name="save_html_email_options"]', function(e) {
        $template_content = jQuery('#template-content').val();
        $message = $template_content.indexOf("MESSAGE");
        if( !$message || $message == -1 ){
            alert('You need to place MESSAGE somewhere in the template, preferably a main content section.');
            return false;
        }
        return true;
    });
    /**
     * On click toggle templates
     */
    jQuery('.template-toggle').click ( function( e ){
        e.preventDefault();
        $this = jQuery(this);
        $templates_wrapper = $this.attr('href');
        jQuery($templates_wrapper).dequeue().stop().toggle('slow', '', function(){
            if( jQuery('#template-wrapper').css('display') == 'none' ){
                $this.find('span').html('+')
            }else{
                $this.find('span').html('-')
            }
            load_templates_slider();
        });
    });
    /**
     * Show email address input field
     * @returns {undefined}
     */
    jQuery('.specify_email').click( function() {
       jQuery('.preview-email').toggle();
    });
});
function load_templates_slider(){
    jQuery('.email-templates').slick({
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
}