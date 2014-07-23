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
    jQuery('body').on('click', '.template-holder.slick-slide a.template-selector', function(e){
        e.preventDefault();

        //Remove active class from other templates
        jQuery('.template-holder').removeClass('active-theme');

        //Add active class to current theme
        jQuery(this).parent(). addClass('active-theme');

        //hide all other load template buttons
        jQuery('.load_template').addClass('disabled');

        $theme_name = jQuery(this).attr('href');
        //remove space from theme name
        $theme_name = $theme_name.replace(/\s+/g, '');
        $theme_name = $theme_name.substr($theme_name.indexOf("#") + 1);

        $load_button = jQuery('#load_template_' + $theme_name );

        $load_button.removeClass('disabled').attr('href', '#' + $theme_name).css('display', 'inline-block');
    });
    /**
     * On click of load template button get template default data and add it to textarea
     */
    jQuery('body').on('click', '.load_template', function(e){
        e.preventDefault();
        if( jQuery(this).hasClass('disabled') ){
            return false;
        }
        $textarea = jQuery('#template-content');
//        if there is already some template content as for confirmation to override the remplate
        if(  $textarea.val() != '' ){
            if( ! confirm( htmlemail_text['load_template'] ) ) {
                return false;
            }
        }

        $theme_name = jQuery(this).attr('href');
        $theme_name = $theme_name.substr($theme_name.indexOf("#") + 1);
        jQuery.get( ajaxurl, { 'action': 'htmlemail_get_template_data', 'theme': $theme_name }, function(res){

            //Append the template content
            $textarea.val(res.data);

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
    jQuery('body').on('click', 'input[name="save_html_email_options"]', function (e) {
        $template_content = jQuery('#template-content').val();
        if( $template_content == '' ){
            return true;
        }
        $message = $template_content.indexOf("{MESSAGE}");
        if (!$message || $message == -1) {
            alert(htmlemail_text['message_missing']);
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
        $wrapper_div = $this.attr('href');
        jQuery($wrapper_div).dequeue().stop().toggle('slow', '', function () {
            if (jQuery($wrapper_div).css('display') == 'none') {
                $this.find('span').html('+')
            } else {
                $this.find('span').html('-')
            }
            if ($wrapper_div == '#template-wrapper') {
                load_templates_slider();
            }
        });
    });
    /**
     * Show email address input field
     * @returns {undefined}
     */
    jQuery('.specify_email').click( function() {
       jQuery('.preview-email').toggle();
    });
    //Send previe email using ajax
    jQuery('input[name="preview_html_email"]').on( 'click', function(e){

       //Do not submit form
        e.preventDefault();

        //Remove previous message
        jQuery('.preview-email-status').remove();

        $preview_html_email_address = jQuery('input[name="preview_html_email_address"]').val();
        if($preview_html_email_address == '' ){
            return false;
        }
        var preview_nonce = jQuery('#preview_email').val();
        var param = {
            action: 'preview_email',
            _ajax_nonce: preview_nonce,
            preview_html_email_address : $preview_html_email_address
        };

        jQuery.post(ajaxurl, param, function (res) {
            $message = res.data;
            if (res.success == true) {
                jQuery('.preview-email').append('<div class="updated preview-email-status"><p>' + $message + '</p></div>');
            } else {
                jQuery('.preview-email').append('<div class="error preview-email-status"><p>' + $message + '</p></div>');
            }

        });
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