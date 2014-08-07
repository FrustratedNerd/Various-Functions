<?php

/*
  Plugin Name: Designmodo Contact Form
  Plugin URI: http://designmodo.com
  Description: Simple Contact form plugin that just work
  Version: 1.0
  Author: Agbonghama Collins
  Author URI: http://tech4sky.com
 */

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
