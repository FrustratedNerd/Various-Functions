<?php
/*
Plugin Name: Various Functions
Description: A collection of functions. 
Author: Frustrated Nerd
Author URI: http://frustratednerd.com
Version: 1.0
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

// Add Stylesheet
    add_action('wp_enqueue_scripts', 'add_my_stylesheet');
    function add_my_stylesheet() {
        $myStyleUrl = plugins_url('style.css', __FILE__);
        $myStyleFile = WP_PLUGIN_DIR . '/various-functions/style.css';
        if ( file_exists($myStyleFile) ) {
            wp_register_style('myStyleSheets', $myStyleUrl);
            wp_enqueue_style( 'myStyleSheets');
        }
    }

// Remove HTML attributes

add_filter('comment_form_defaults', 'remove_comment_styling_prompt');

function remove_comment_styling_prompt($defaults) {
	$defaults['comment_notes_after'] = '';
	return $defaults;
}

// Disable Comments On Pages

function ncop_comments_open_filter($open, $post_id=null)
{
    $post = get_post($post_id);
    return $open && $post->post_type !== 'page';
}

function ncop_comments_template_filter($file)
{
    return is_page() ? dirname(__FILE__).'/empty' : $file;
}

add_filter('comments_open', 'ncop_comments_open_filter', 10, 2);
add_filter('comments_template', 'ncop_comments_template_filter', 10, 1);

// Add Thumbnails to RSS Feeds

function rss_post_thumbnail($content) {
       global $post;
       if(has_post_thumbnail($post->ID)) {
       $content = '<p>' . get_the_post_thumbnail($post->ID) .
       '</p>' . get_the_content();
       }
       return $content;
       }
add_filter('the_excerpt_rss', 'rss_post_thumbnail');
add_filter('the_content_feed', 'rss_post_thumbnail');

// Remove Default Widgets

function unregister_default_wp_widgets() {
unregister_widget('WP_Widget_Calendar');
unregister_widget('WP_Widget_Archives');
unregister_widget('WP_Widget_Links');
unregister_widget('WP_Widget_Meta');
unregister_widget('WP_Widget_Recent_Comments');
unregister_widget('WP_Widget_RSS');
unregister_widget('WP_Widget_Tag_Cloud');
}

add_action('widgets_init', 'unregister_default_wp_widgets', 1);

// Remove Header Items

remove_action('wp_head', 'wp_generator'); // kill the wordpress version number
remove_action('wp_head', 'wlwmanifest_link'); // kill the WLW link
remove_action('wp_head', 'rsd_link'); // kill the RSD link

// Auto-Generate Thumbnails

function autoset_featured() {
          global $post;
          $already_has_thumb = has_post_thumbnail($post->ID);
              if (!$already_has_thumb)  {
              $attached_image = get_children( "post_parent=$post->ID&post_type=attachment&post_mime_type=image&numberposts=1" );
                          if ($attached_image) {
                                foreach ($attached_image as $attachment_id => $attachment) {
                                set_post_thumbnail($post->ID, $attachment_id);
                                }
                           }
                        }
      }
add_action('the_post', 'autoset_featured');
add_action('save_post', 'autoset_featured');
add_action('draft_to_publish', 'autoset_featured');
add_action('new_to_publish', 'autoset_featured');
add_action('pending_to_publish', 'autoset_featured');
add_action('future_to_publish', 'autoset_featured');

// Contact Form

class Designmodo_contact_form {

    private $form_errors = array();

    function __construct() {
        // Register a new shortcode: [dm_contact_form]
        add_shortcode('dm_contact_form', array($this, 'shortcode'));
    }

    static public function form() {
        echo '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
        echo '<p>';
        echo 'Your Name (required) <br/>';
        echo '<input type="text" name="your-name" value="' . $_POST["your-name"] . '" size="40" />';
        echo '</p>';
        echo '<p>';
        echo 'Your Email (required) <br/>';
        echo '<input type="text" name="your-email" value="' . $_POST["your-email"] . '" size="40" />';
        echo '</p>';
        echo '<p>';
        echo 'Subject (required) <br/>';
        echo '<input type="text" name="your-subject" value="' . $_POST["your-subject"] . '" size="40" />';
        echo '</p>';
        echo '<p>';
        echo 'Your Message (required) <br/>';
        echo '<textarea rows="10" cols="35" name="your-message">' . $_POST["your-message"] . '</textarea>';
        echo '</p>';
        echo '<p><input type="submit" name="form-submitted" value="Send"></p>';
		echo '</form>';
    }

    public function validate_form( $name, $email, $subject, $message ) {
    	
        // If any field is left empty, add the error message to the error array
        if ( empty($name) || empty($email) || empty($subject) || empty($message) ) {
            array_push( $this->form_errors, 'No field should be left empty' );
        }
		
        // if the name field isn't alphabetic, add the error message
        if ( strlen($name) < 4 ) {
            array_push( $this->form_errors, 'Name should be at least 4 characters' );
        }

        // Check if the email is valid
        if ( !is_email($email) ) {
            array_push( $this->form_errors, 'Email is not valid' );
        }
    }

    public function send_email($name, $email, $subject, $message) {
        	
        // Ensure the error array ($form_errors) contain no error
        if ( count($this->form_errors) < 1 ) {

            // sanitize form values
            $name = sanitize_text_field($name);
            $email = sanitize_email($email);
            $subject = sanitize_text_field($subject);
            $message = esc_textarea($message);
            
			// get the blog administrator's email address
            $to = get_option('admin_email');
			
            $headers = "From: $name <$email>" . "\r\n";

            // If email has been process for sending, display a success message
            if ( wp_mail($to, $subject, $message, $headers) )
                echo '<div style="background: #3b5998; color:#fff; padding:2px;margin:2px">';
                echo 'Thanks for contacting me, expect a response soon.';
                echo '</div>';
        }
    }

    public function process_functions() {
        if ( isset($_POST['form-submitted']) ) {
			
			// call validate_form() to validate the form values
            $this->validate_form($_POST['your-name'], $_POST['your-email'], $_POST['your-subject'], $_POST['your-message']);
            
            // display form error if it exist
            if (is_array($this->form_errors)) {
                foreach ($this->form_errors as $error) {
                    echo '<div>';
                    echo '<strong>ERROR</strong>:';
                    echo $error . '<br/>';
                    echo '</div>';
                }
            }
        }

        $this->send_email( $_POST['your-name'], $_POST['your-email'], $_POST['your-subject'], $_POST['your-message'] );

        self::form();
    }

    public function shortcode() {
        ob_start();
        $this->process_functions();
        return ob_get_clean();
    }

}

new Designmodo_contact_form;