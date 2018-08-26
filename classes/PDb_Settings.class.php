<?php
/**
 * plugin settings class for members-database plugin
 *
 *
 * this uses the generic plugin settings class to build the settings specific to
 * the plugin
 * 
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    1.6
 * @link       http://xnau.com/wordpress-plugins/
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_Settings extends xnau_Plugin_Settings {

  function __construct()
  {

    $this->setup_plugin_options();
    $this->add_settings_filters();

    /*
     * define the settings sections
     */
    $this->sections = array(
        'pdb-main' => __( 'General Settings', 'members-database' ),
        'pdb-signup' => __( 'Signup Form Settings', 'members-database' ),
        'pdb-record' => __( 'Record Form Settings', 'members-database' ),
        'pdb-list' => __( 'List Display Settings', 'members-database' ),
        'pdb-resend' => __( 'Resend Link Settings', 'members-database' ),
        'pdb-admin' => __( 'Admin Settings', 'members-database' ),
        'pdb-advanced' => __( 'Advanced Settings', 'members-database' ),
        'pdb-css' => __( 'Custom CSS', 'members-database' ),
    );

    $this->section_description = array(
        'pdb-record' => __( 'Settings for the [pdb_record] shortcode, which is used to show a user-editable form on the website.', 'members-database' ),
        'pdb-list' => __( 'Settings for the [pdb_list] shortcode, which is used to show a list of records from the database.', 'members-database' ),
        'pdb-signup' => __( 'Settings for the [pdb_signup] shortcode, which is used to show a signup or registration form on the website.', 'members-database' ),
        'pdb-resend' => __( 'Settings for the lost private link resend function.', 'members-database' ),
        'pdb-advanced' => __( 'Settings for special configurations.', 'members-database' ),
        'pdb-admin' => __( 'Settings for the plugin backend.', 'members-database' ),
        'pdb-css' => __( 'User CSS rules for styling plugin displays.</h4><p>If you\'re new to CSS, try this tutorial to help you get started: <a target="_blank" href="http://shrsl.com/?dnf8">Use Firebug for Editing WordPress Themes.</a></p>', 'members-database' ),
    );
    // determine the type of text-area elements to use for email body settings
    $this->textarea_type = Participants_Db::$plugin_options['html_email'] == '1' ? 'rich-text' : 'text-area';


    // run the parent class initialization to finish setting up the class 
    parent::__construct( __CLASS__ );

    $this->submit_button = __( 'Save Plugin Settings', 'members-database' );
    
    
    // this is waiting on more complete implementation. #1634
//    add_action( 'admin_init', array( $this, 'check_settings' ), 50 );
    
    // filters to condition saved values for display
    add_filter( 'pdb-settings_page_setting_value', function( $value, $input ) {
      switch ($input['name']) {
        case 'image_upload_limit': 
        $value = preg_replace( '/\D/', '', $value );
        break;
      }
      return $value;
    }, 10, 2 );
    
  }
  
  /**
   * checks the settings for problems and notifies the admin
   * 
   * run on the admin_init hook
   * 
   * 
   */
  public function check_settings()
  {
    $settings = Participants_Db::$plugin_options;
    

    
    /*
     * check registration (particiant record) page settings
     * unused at this point because not all users need to have this configured
     */
//    $post = get_post( $settings['registration_page'] );
//    if ( $post->post_status !== 'publish' || !$post ) {
//      $notices->warning( 'The Participant Record Page setting (Record Form Settings tab) does not point to a valid page.' );
//    }
//    if ( stripos( $post->post_content, '[pdb_record') === false ) {
//      $notices->warning( 'The Participant Record Page must include the [pdb_record] shortcode to show the editable record.' );
//    }
  }

  /**
   * sets up the plugin options array
   */
  private function setup_plugin_options()
  {
    $this->WP_setting = Participants_Db::$participants_db_options;

    $default_options = get_option( Participants_Db::$default_options );

    if ( !is_array( $default_options ) || empty( $default_options ) ) {

      add_filter( 'plugins_loaded', array($this, 'save_default_options'), 20 );

    }

    Participants_Db::$plugin_options = array_merge( (array) $default_options, (array) get_option( $this->WP_setting ) );
  }

  /**
   * saves the default options option
   */
  public function save_default_options()
  {
    $default_options = $this->get_default_options();

    update_option( Participants_Db::$default_options, $default_options, '', false );
  }

  /**
   * get all options w/defaults
   * 
   * this is used to overload the options
   * 
   * @return array name => defualt
   */
  public function get_default_options()
  {
    $defaults = array();
    foreach ( $this->plugin_settings as $setting ) {
      $defaults[$setting['name']] = isset( $setting['options']['value'] ) ? $setting['options']['value'] : '';
    }
    return $defaults;
  }

  /**
   * defines the individual settings for the plugin
   *
   * @return null
   */
  protected function _define_settings()
  {

    /*     * ****************************************************
     *
     *   general settings
     *
     * **************************************************** */

    $this->plugin_settings[] = array(
        'name' => 'image_upload_location',
        'title' => __( 'File Upload Location', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => sprintf( __( "This defines where the uploaded files will go, relative to the %s. The default location is '/wp-content/uploads/members-database'<br />Don't put it in the plugin folder, the images and files could get deleted when the plugin is updated.", 'members-database' ), $this->files_base_label() ) . $this->settings_help( 'File-Upload-Location' ),
            'value' => 'wp-content/uploads/' . Participants_Db::PLUGIN_NAME . '/',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'image_upload_limit',
        'title' => __( 'File Upload Limit', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'numeric',
            'help_text' => __( 'the maximum allowed file size (in kilobytes) for an uploaded file', 'members-database' ),
            'value' => '100',
            'attributes' => array('style' => 'width:5em','min' => '10', 'step' => '10','data-after' => 'K '),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'allowed_file_types',
        'title' => __( 'Allowed File Types', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'list of allowed file types by file extension. Please be aware that there are security risks in allowing file uploads.', 'members-database' ),
            'value' => 'txt,pdf,mp3,mp4a,ogg,doc,docx,odt,rtf,zip,jpg,jpeg,gif,png',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'default_image',
        'title' => __( 'Default Image', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => sprintf( __( "Path (relative to the %s) of an image file to show if no image has been defined for an image field. Leave blank for no default image.", 'members-database' ), $this->files_base_label() ),
            'value' => '/wp-content/plugins/members-database/ui/no-image.png',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'image_link',
        'title' => __( 'Link Image to Fullsize', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'place a link to the full-size image on images. This link will work with most "lightbox" plugins.', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'file_delete',
        'title' => __( 'Allow File Delete', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'if checked, allows uploaded files and images to be deleted from storage when deleted from a record by a user', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );
    

    $this->plugin_settings[] = array(
        'name' => 'allow_tags',
        'title' => __( 'Allow HTML Tags in Text Fields', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'if checked, limited HTML tags are allowed in "text-line" fields.', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'make_links',
        'title' => __( 'Make Links Clickable', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'if a "text-line" field looks like a link (begins with "http" or is an email address) make it clickable', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'email_protect',
        'title' => __( 'Protect Email Addresses', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'protect email addresses in text-line fields with Javascript', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'empty_field_message',
        'title' => __( 'Missing Field Error Message', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'the message shown when a field is required, but left empty (the %s is replaced by the name of the field)', 'members-database' ),
            'value' => __( 'The %s field is required.', 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'invalid_field_message',
        'title' => __( 'Invalid Field Error Message', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __( "the message shown when a field's value does not pass the validation test", 'members-database' ),
            'value' => __( 'The %s field appears to be incorrect.', 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'nonmatching_field_message',
        'title' => __( 'Non-Matching Field Error Message', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __( "the message shown when a field's value does match the value of another field. The first %s will show the name of the field, the second will show the name of the field it must match.", 'members-database' ),
            'value' => __( 'The %s field must match the %s field.', 'members-database' ),
        )
    );


    $this->plugin_settings[] = array(
        'name' => 'captcha_field_message',
        'title' => __( 'Failed CAPTCHA Message', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __( "the message shown when the CAPTCHA (verify human) test failed", 'members-database' ),
            'value' => __( 'Pleast try the %s question again.', 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'field_error_style',
        'title' => __( 'Field Error Style', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'the CSS style applied to an input or text field that is missing or has not passed validation. This must be a valid CSS rule', 'members-database' ),
            'value' => 'border: 1px solid red',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'mark_required_fields',
        'title' => __( 'Mark Required Fields', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'mark the title of required fields?', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'required_field_marker',
        'title' => __( 'Required Field Marker', 'members-database' ),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text-area',
            'help_text' => __( 'html added to field title for required fields if selected above (the %s is replaced by the title of the field)', 'members-database' ),
            'value' => '%s<span class="reqd">*</span>',
        )
    );

//    $this->plugin_settings[] = array(
//        'name' => 'search_collation',
//        'title' => __('Search Collation', 'members-database'),
//        'group' => 'pdb-main',
//        'options' => array(
//            'type' => 'dropdown',
//            'help_text' => sprintf(__('sets the database collation map <a href="%s">(more info)</a> to use for list searches. Set this to your language if your non-English searches are not working correctly. You may have to experiment with different settings. The default is "UTF-8 Unicode."', 'members-database'), 'http://dev.mysql.com/doc/refman/5.0/en/charset-general.html'),
//            'value' => $this->get_collation(),
//            'options' => $this->get_collation_list(),
//        )
//    );

    /*     * ****************************************************
     *
     *   signup form settings
     *
     * **************************************************** */

    $this->plugin_settings[] = array(
        'name' => 'signup_button_text',
        'title' => __( 'Signup Button Text', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'text shown on the button to sign up', 'members-database' ),
            'value' => _x( 'Sign Up', 'the text on a button to submit a signup form', 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'signup_thanks_page',
        'title' => __( 'Signup Thanks Page', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'dropdown-other',
            'help_text' => __( 'after they register, send them to this page for a thank you message. This page is where you put the [pdb_signup_thanks] shortcode, but you don&#39;t have to do that if you have them go back to the same page. You can also use a Post ID for posts and custom post types.', 'members-database' ),
            'options' => $this->_get_pagelist( true ),
            'attributes' => array('other' => 'Post ID'),
            'value' => 'none',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'signup_show_group_descriptions',
        'title' => __( 'Show Field Groups', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'Show groups and group descriptions in the signup form.', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'unique_field',
        'title' => __( 'Duplicate Record Check Field', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array
            (
            'type' => 'dropdown',
            'help_text' => __( 'when a signup is submitted or CSV record is imported, this field is checked for a duplicate', 'members-database' ),
            'options' => self::_get_identifier_columns( false ),
            'value' => 'email',
        )
    );
    $this->plugin_settings[] = array(
        'name' => 'unique_email',
        'title' => __( 'Duplicate Record Preference', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array
            (
            'type' => 'dropdown',
            'help_text' => __( 'when the submission matches the Duplicate Record Check Field of an existing record. This also applies to importing records from a CSV file.', 'members-database' ),
            'value' => 1,
            'options' => array(
                __( 'Create a new record with the submission', 'members-database' ) => 0,
                __( 'Overwrite matching record with new data', 'members-database' ) => 1,
                __( 'Show a validation error message', 'members-database' ) => 2,
                'null_select' => false,
            ),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'duplicate_field_message',
        'title' => __( 'Duplicate Record Error Message', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text-area',
            'help_text' => __( 'If "Show a validation error message" is selected above, this message will be shown if a signup is made with a "check field" that matches an existing record.', 'members-database' ),
            'value' => __( 'A record with that %s already exists. Please choose another.', 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'send_signup_receipt_email',
        'title' => __( 'Send Signup Response Email', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'checkbox',
            'help_text' => __( 'Send a receipt email to people who sign up', 'members-database' ),
            'value' => 1,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'receipt_from_address',
        'title' => __( 'Signup Email From Address', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'the "From" address on signup receipt emails. If the recipient hits "reply", their reply will go to this address. It is a good idea to use an email address from the same domain as this website.', 'members-database' ),
            'value' => get_bloginfo( 'admin_email' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'receipt_from_name',
        'title' => __( 'Signup Email From Name', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'the "From" name on signup receipt emails.', 'members-database' ),
            'value' => get_bloginfo( 'name' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'signup_receipt_email_subject',
        'title' => __( 'Signup Response Email Subject', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'subject line for the signup response email; placeholder tags can be used (see below)', 'members-database' ),
            'value' => sprintf( __( "You've just signed up on %s", 'members-database' ), get_bloginfo( 'name' ) ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'signup_receipt_email_body',
        'title' => __( 'Signup Response Email', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => $this->textarea_type,
            'help_text' => __( 'Body of the email a visitor gets when they sign up. It includes a link ([record_link]) back to their record so they can fill it out. Can include HTML, placeholders:[first_name],[last_name],[email],[record_link]. You can only use placeholders for fields that are present in the signup form, including hidden fields.', 'members-database' ),
            /* translators: the %s will be the name of the website */
            'value' => sprintf( __( '<p>Thank you, [first_name], for signing up with %s.</p><p>You may complete your registration with additional information or update your information by visiting this private link at any time: <a href="[record_link]">[record_link]</a>.</p>', 'members-database' ), get_bloginfo( 'name' ) ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'signup_thanks',
        'title' => __( 'Signup Thanks Message', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'rich-text',
            'help_text' => __( 'Note to display on the web page after someone has submitted a signup form. Can include HTML and placeholders (see above)', 'members-database' ),
            'value' => __( '<p>Thank you, [first_name] for signing up!</p><p>You will receive an email acknowledgement shortly. You may complete your registration with additional information or update your information by visiting the link provided in the email.</p>', 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'send_signup_notify_email',
        'title' => __( 'Send Signup Notification Email', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'Send an email notification that a signup has occurred.', 'members-database' ),
            'value' => 1,
            'options' => array(1, 0),
        )
    );


    $this->plugin_settings[] = array(
        'name' => 'email_signup_notify_addresses',
        'title' => __( 'Signup Notification Recipients', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'comma-separated list of email addresses to send signup notifications to', 'members-database' ),
            'value' => get_bloginfo( 'admin_email' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'email_signup_notify_subject',
        'title' => __( 'Signup Notification Email Subject', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'subject of the notification email; placeholder tags can be used (see above)', 'members-database' ),
            /* translators: the %s will be the name of the website */
            'value' => sprintf( __( 'New signup on %s', 'members-database' ), get_bloginfo( 'name' ) ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'email_signup_notify_body',
        'title' => __( 'Signup Notification Email', 'members-database' ),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => $this->textarea_type,
            'help_text' => __( 'notification email body. The [admin_record_link] tag will supply the URL for editing the record in the admin.', 'members-database' ),
            'value' => __( '<p>A new signup has been submitted</p><ul><li>Name: [first_name] [last_name]</li><li>Email: [email]</li></ul><p>Edit this new record here: <a href="[admin_record_link]">[admin_record_link]</a></p>', 'members-database' ),
        )
    );

    /*
      $this->plugin_settings[] = array(
      'name' => 'no_cookie_message',
      'title' => __('No User Cookie Message', 'members-database'),
      'group' => 'pdb-signup',
      'options' => array(
      'type' => 'text',
      'help_text' => __('this plugin doesn\'t work if the user has cookies disabled. Show this message in place of the signup form if the user has cookies disabled.', 'members-database'),
      'value' => __('Please enable cookies in your browser to use this feature.', 'members-database'),
      )
      );
     */

    /*     * ****************************************************
     *
     *   record form settings
     *
     * **************************************************** */

    $this->plugin_settings[] = array(
        'name' => 'registration_page',
        'title' => __( 'Participant Record Page', 'members-database' ),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'dropdown-other',
            'help_text' => __( 'The page where your participant record ([pdb_record] shortcode) is displayed. You can use a Post ID for posts and custom post types.', 'members-database' ),
            'options' => $this->_get_pagelist( false, true ),
            'attributes' => array('other' => 'Post ID'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'show_group_descriptions',
        'title' => __( 'Show Groups', 'members-database' ),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'Show the group and description (if defined) under each group title in the record form.', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'save_changes_label',
        'title' => __( 'Save Changes Label', 'members-database' ),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'text',
            'help_text' => __( 'label for the save changes button on the record form', 'members-database' ),
            'value' => __( 'Save Your Changes', 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'save_changes_button',
        'title' => __( 'Save Button Text', 'members-database' ),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'text',
            'help_text' => __( 'text on the "save" button', 'members-database' ),
            'value' => _x( 'Save', 'a label for a button to save a form', 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'record_updated_message',
        'title' => __( 'Record Updated Message', 'members-database' ),
        'group' => 'pdb-record',
        'options' => array(
            'type' => 'text',
            'help_text' => __( "the message shown when a record form has been successfully submitted", 'members-database' ),
            'value' => __( 'Your information has been updated', 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'no_record_error_message',
        'title' => __( 'Record Not Found Error Message', 'members-database' ),
        'group' => 'pdb-record',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'message to show if the record page was accessed without a valid identifier. Leave this empty if you want nothing at all to show.', 'members-database' ),
            'value' => sprintf( __( "No record was found.", 'members-database' ), get_bloginfo( 'name' ) ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'no_record_use_template',
        'title' => __( 'Use Template for No Record Message', 'members-database' ),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'If checked, use the record template to show the "Record Not Found" message. If unchecked, the message is shown without using the template.', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'send_record_update_notify_email',
        'title' => __( 'Send Record Form Update Notification Email', 'members-database' ),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'Send an email notification that a record has been updated. These will be sent to the email addresses listed in the "Signup Notification Recipients" setting.', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'record_update_email_subject',
        'title' => __( 'Record Update Email Subject', 'members-database' ),
        'group' => 'pdb-record',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'subject line for the record update notification email; placeholders can be used.', 'members-database' ),
            'value' => sprintf( __( "A record has just been updated on %s", 'members-database' ), get_bloginfo( 'name' ) ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'record_update_email_body',
        'title' => __( 'Record Update Notification Email', 'members-database' ),
        'group' => 'pdb-record',
        'options' => array(
            'type' => $this->textarea_type,
            /* translators: [date] and [admin_record_link] must not be translated, they must be used literally. The rest of the words enclosed in brackets can be defined by the user, they are used here only as examples. */
            'help_text' => __( 'Body of the the email sent when a user updates their record. Any field from the form can be included by using a replacement code of the form: [field_name]. For instance: [last_name],[address],[email] etc. (The field name is under the "name" column on the "Manage Database Fields" page.)  Also available is [date] which will show the date and time of the update and [admin_record_link] tag for a link to edit the record in the admin.', 'members-database' ),
            'value' => __( '<p>The following record was updated on [date]:</p><ul><li>Name: [first_name] [last_name]</li><li>Address: [address]</li><li>[city], [state], [country]</li><li>Phone: [phone]</li><li>Email: [email]</li></ul><p>Edit this record <a href="[admin_record_link]">here.</a></p>', 'members-database' ),
        )
    );

//    $this->plugin_settings[] = array(
//        'name' => 'prevent_duplicate_on_update',
//        'title' => __( 'Prevent Duplicate Field Values', 'members-database' ),
//        'group' => 'pdb-record',
//        'options' => array
//            (
//            'type' => 'checkbox',
//            'help_text' => __( 'When checked, the "duplicate field" settings for the signup form will be used to prevent a duplicate field value when the record is updated.', 'members-database' )  . $this->settings_help( 'File-and-Image-Uploads-Use-WP-'),
//            'value' => 0,
//            'options' => array(1, 0),
//        )
//    );


    /*     * ****************************************************
     *
     *   list display settings
     *
     * **************************************************** */

    $this->plugin_settings[] = array(
        'name' => 'list_limit',
        'title' => __( 'Records per Page', 'members-database' ),
        'group' => 'pdb-list',
        'options' => array(
            'type' => 'numeric',
            'help_text' => __( 'the number of records to show on each page', 'members-database' ),
            'attributes' => array('style' => 'width:4em','step' => '1', 'min' => '-1'),
            'value' => 10,
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'single_record_link_field',
        'title' => __( 'Single Record Link Field', 'members-database' ),
        'group' => 'pdb-list',
        'options' => array
            (
            'type' => 'dropdown',
            'help_text' => __( 'select the field on which to put a link to the [pdb_single] shortcode. Leave blank or set to "none" for no link.', 'members-database' ),
            'options' => $this->_get_display_columns(),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'single_record_page',
        'title' => __( 'Single Record Page', 'members-database' ),
        'group' => 'pdb-list',
        'options' => array
            (
            'attributes' => array('other' => 'Post ID'),
            'type' => 'dropdown-other',
            'help_text' => __( 'this is the page where the [pdb_single] shortcode is located. If you want to assign a post or custom post type, select "Post ID" and enter the post ID in the "other" box.', 'members-database' ),
            'options' => $this->_get_pagelist( false, true ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'no_records_message',
        'title' => __( 'No Records Message', 'members-database' ),
        'group' => 'pdb-list',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'Message shown when no records are found.', 'members-database' ),
            'value' => __( 'No Records Found', 'members-database' ),
        )
    );
    $this->plugin_settings[] = array(
        'name' => 'show_count',
        'title' => __( 'Show Count', 'members-database' ),
        'group' => 'pdb-list',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( "Show the list count on list displays. Can also be set in the shortcode.", 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'count_template',
        'title' => __( 'List Count Template', 'members-database' ),
        'group' => 'pdb-list',
        'options' => array(
            'type' => 'text-area',
            'help_text' => sprintf( __( 'template for displaying the list count. %1$s - total number of records found, %2$s - number of records shown per page, %3$s - starting record number, %4$s - ending record number, %5$s - the current page number', 'members-database' ), '<br /><strong>%1$s</strong>', '<strong>%2$s</strong>', '<strong>%3$s</strong>', '<strong>%4$s</strong>', '<strong>%5$s</strong>' ),
            'value' => __( 'Total Records Found: %1$s, showing %2$s per page', 'members-database' ),
            'attributes' => array(
                'style' => 'height: 4em;'
            ),
        ),
    );


    $this->plugin_settings[] = array(
        'name' => 'list_default_sort',
        'title' => __( 'List Default Sort', 'members-database' ),
        'group' => 'pdb-list',
        'options' => array
            (
            'type' => 'dropdown',
            'value' => 'date_updated',
            'help_text' => __( 'The record list shown by the list shortcode will be sorted by this field by default. (Field must be checked "sortable.")', 'members-database' ),
            'options' => $this->_get_sort_columns(),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'list_default_sort_order',
        'title' => __( 'List Default Sort Order', 'members-database' ),
        'group' => 'pdb-list',
        'options' => array(
            'type' => 'dropdown',
            'help_text' => __( 'Sets the default order of the records shown by the list shortcode.', 'members-database' ),
            'value' => 'desc',
            'options' => array(__( 'Ascending', 'members-database' ) => 'asc', __( 'Descending', 'members-database' ) => 'desc', 'null_select' => false),
        )
    );


    $this->plugin_settings[] = array(
        'name' => 'empty_search',
        'title' => __( 'Allow Empty Search', 'members-database' ),
        'group' => 'pdb-list',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( "This allows frontend searches to find records with missing or blank data.", 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );
    
    

    $this->plugin_settings[] = array(
        'name' => 'strict_search',
        'title' => __( 'Strict User Searching', 'members-database' ),
        'group' => 'pdb-list',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'When checked, the frontend list search must match the whole field exactly. If unchecked, the search will match if the search term is found in part of the field. Searches are not case-sensitive either way.', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    /*     * *****************************************************
     * 
     * LINK RECOVERY SETTINGS
     * 
     * ***************************************************** */

    $this->plugin_settings[] = array(
        'name' => 'show_retrieve_link',
        'title' => __( 'Enable Lost Private Link', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'Show a link on the signup form allowing users to have their private link emailed to them.', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_text',
        'title' => __( 'Lost Private Link Text', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'clickable text shown in the signup form', 'members-database' ),
            'value' => __( 'Forget your private link? Click here to have it emailed to you.', 'members-database' ),
        )
    );
    $this->plugin_settings[] = array(
        'name' => 'link_retrieval_page',
        'title' => __( 'Lost Private Link Page', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array
            (
            'type' => 'dropdown-other',
            'help_text' => __( 'send people to this page to request their private link.', 'members-database' ),
            'options' => $this->_get_pagelist( true ),
            'attributes' => array('other' => 'Post ID'),
            'value' => 'none',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_identifier',
        'title' => __( 'Lost Private Link ID Field', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'dropdown',
            'help_text' => __( 'The field used to identify the user&#39;s account. This must be a unique identifier for the record', 'members-database' ),
            'options' => self::_get_identifier_columns( false ),
            'value' => 'email',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'id_field_prompt',
        'title' => __( 'ID Field Help Text', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'help text for the record identification field', 'members-database' ),
            'value' => __( "Type in your %s, your private link will be emailed to you.", 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_email_subject',
        'title' => __( 'Lost Private Link Email Subject', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'subject line for the lost private link email', 'members-database' ),
            'value' => sprintf( __( "Here is your private link on %s", 'members-database' ), get_bloginfo( 'name' ) ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_email_body',
        'title' => __( 'Lost Private Link Email', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => $this->textarea_type,
            'help_text' => __( 'Body of the email sent when a lost private link is requested.', 'members-database' ),
            /* translators: the %s will be the name of the website */
            'value' => '<p>' . sprintf( __( 'Here is the private link you requested from %s:', 'members-database' ), get_bloginfo( 'name' ) ) . '</p><p><a href="[record_link]">[record_link]</a>.</p>',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'send_retrieve_link_notify_email',
        'title' => __( 'Send Lost Private Link Notification Email', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'Send an email notification that a lost private link has been requested. This email will go to the "Signup Notification Recipients."', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_notify_subject',
        'title' => __( 'Lost Private Link Notification Email Subject', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'text',
            'help_text' => __( 'subject of the notification email; placeholder tags can be used (see above)', 'members-database' ),
            /* translators: the %s will be the name of the website */
            'value' => sprintf( __( 'A Lost Private Link has been requested on %s', 'members-database' ), get_bloginfo( 'name' ) ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_notify_body',
        'title' => __( 'Lost Private Link Notification Email', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => $this->textarea_type,
            'help_text' => __( 'notification email body', 'members-database' ),
            'value' => __( '<p>A lost private link has been requested by:</p><ul><li>Name: [first_name] [last_name]</li><li>Email: [email]</li></ul>', 'members-database' ),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'identifier_field_message',
        'title' => __( 'Retrieve Private Link Error Message', 'members-database' ),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'text-area',
            'help_text' => __( 'Message shown when a record matching the retrieve link idenifier cannot be found', 'members-database' ),
            'value' => __( 'A record matching that %s cannot be found.', 'members-database' ),
        )
    );



    /*     * ****************************************************
     *
     *   ADVANCED SETTINGS
     *
     * **************************************************** */

    $this->plugin_settings[] = array(
        'name' => 'use_plugin_css',
        'title' => __( 'Use the Plugin CSS', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'use the plugin\'s CSS to style the output of shortcodes', 'members-database' ),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'rich_text_editor',
        'title' => __( 'Use Rich Text Editor', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'enable the rich text editor on "rich-text" fields for front-end users. If deselected, "rich-text" fields will appear as text-area fields. This does not affect admin users, who always have the use of the rich-text editor.', 'members-database' ),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );

    /**
     * @version 1.7.3.1
     * 
     * modified this setting for multiple modes
     */
    $this->plugin_settings[] = array(
        'name' => 'enable_wpautop',
        'title' => __( 'Use WordPress Auto Formatting', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'dropdown',
            'help_text' => __( 'Select the filter mode for all rich text outputs, including HTML emails.', 'members-database' ),
            'value' => 'the_content',
            'options' => array( 
                __( 'global content filter', 'members-database' ) . ' (the_content)' =>'the_content', 
                __( 'WordPress auto paragraphs', 'members-database' ) . ' (wpautop)' => 'wpautop', 
                __( 'auto paragraphs + shortcodes', 'members-database' ) => 'wpautop+shortcodes', 
                __( 'none', 'members-database' ) => 'none' 
                ),
        ),
    );

    /**
     * @version 1.6
     */
    $this->plugin_settings[] = array(
        'name' => 'strip_linebreaks',
        'title' => __( 'Remove Line Breaks', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'Remove line breaks from all plugin shortcode ouput.', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );


    /**
     * @version 1.7.0.3
     */
    $this->plugin_settings[] = array(
        'name' => 'use_pagination_scroll_anchor',
        'title' => __( 'Use Pagination Scroll Anchors', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'Uncheck this if your theme prevents pagination links with scroll anchors from working.', 'members-database' ),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'primary_email_address_field',
        'title' => __( 'Primary Email Address Field', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'dropdown',
            'help_text' => __( 'this field is the primary email address for the record', 'members-database' ),
            'value' => 'email',
            'options' => self::_get_identifier_columns( false ),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'html_email',
        'title' => __( 'Send HTML Email', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'use rich text in plugin emails? If you turn this off, be sure to remove all HTML tags from the email body settings for the plugin.', 'members-database' ),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );

    PDb_Date_Display::reassert_timezone();
    $this->plugin_settings[] = array(
        'name' => 'strict_dates',
        'title' => __( 'Strict Date Format', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => sprintf(
                    __( 'This forces date inputs to be interpreted strictly according to the "Input Date Format" setting. You should tell your users what format you are expecting them to use. This also applies to date values used in [pdb_list] shortcode filters. The date with your current setting looks like this: <strong>%s</strong> %s', 'members-database' ), strftime( xnau_Date_Format_String::to_strftime( Participants_Db::plugin_setting( 'input_date_format', get_option( 'date_format' ) ) ) ), (function_exists( 'date_create' ) ? '' : '<strong>(' . __( 'Your current PHP installation does not support this setting.', 'members-database' ) . ' )</strong>' )
            ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'input_date_format',
        'title' => __( 'Input Date Format', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'text-line',
            'help_text' => __( 'date formatting string for all plugin date inputs when "Strict Date Format" is enabled. You should use this for all localized (non-American English) date formats.', 'members-database' ),
            'value' => get_option( 'date_format' ),
        ),
    );
    
    $this->plugin_settings[] = array(
        'name' => 'ajax_search',
        'title' => __( 'Enable AJAX Search Functionality', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( "This enables list search results that are updated without reloading the page. It requires Javascript, but search will still work if Javascript is disabled in the user's browser.", 'members-database' ),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'use_php_sessions',
        'title' => __( 'Use PHP Sessions', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'check this to use PHP sessions instead of database sessions.', 'members-database' ) . $this->settings_help( 'usephpsessions'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'cookie_name',
        'title' => __( 'Cookie Name', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'text-line',
            'help_text' => __( 'Change the name of the cookie for compatibility with some web hosting setups.', 'members-database' ),
            'value' => 'pdb_wp_session',
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'disable_live_notifications',
        'title' => __( 'Disable Backend Developer Ads', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'select this to disable developer ads in the admin.', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'files_use_content_base_path',
        'title' => __( 'File and Image Uploads Use WP Content Path', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'when selected, the base path for file and image uploads will be the site\'s content directory.', 'members-database' ) . $this->settings_help( 'File-and-Image-Uploads-Use-WP-'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'allow_record_timestamp_edit',
        'title' => __( 'Allow Record Timestamps to be Edited', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'normally, record timestamps (date_recorded, date_updated, last_accessed) are not editable, checking this allows them to be edited.', 'members-database' ) . $this->settings_help( 'allow-record-timestamps-to-be-edited'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );
    

    $this->plugin_settings[] = array(
        'name' => 'pdb_debug',
        'title' => __( 'Enable Debugging', 'members-database' ),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'dropdown',
            'help_text' => sprintf(__( 'this will enable writing to the %s debugging log.', 'members-database' ), Participants_Db::$plugin_title ) . $this->settings_help( 'enable-debugging'),
            'value' => 0,
            'options' => array( 
                __('off', 'members-database') => 0, 
                __('plugin debug', 'members-database') => 1,
                __('all errors', 'members-database') => 2,
                ),
        ),
    );

    /*     * ****************************************************
     *
     *   admin section settings
     *
     * **************************************************** */


    $this->plugin_settings[] = array(
        'name' => 'admin_default_sort',
        'title' => __( 'Admin List Default Sort', 'members-database' ),
        'group' => 'pdb-admin',
        'options' => array
            (
            'type' => 'dropdown',
            'value' => 'date_updated',
            'help_text' => __( 'The record list shown in the admin section will be sorted by this field by default. (Field must be checked "sortable.")', 'members-database' ),
            'options' => $this->_get_sort_columns(),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'admin_default_sort_order',
        'title' => __( 'Admin List Default Sort Order', 'members-database' ),
        'group' => 'pdb-admin',
        'options' => array(
            'type' => 'dropdown',
            'help_text' => __( 'Sets the default order of the record list in the admin.', 'members-database' ),
            'value' => 'desc',
            'options' => array(__( 'Ascending', 'members-database' ) => 'asc', __( 'Descending', 'members-database' ) => 'desc', 'null_select' => false),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'admin_thumbnails',
        'title' => __( 'Show Image Thumbnails in Admin List', 'members-database' ),
        'group' => 'pdb-admin',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => '',
            'value' => 0,
            'options' => array(1, 0),
        ),
    );



    $this->plugin_settings[] = array(
        'name' => 'admin_horiz_scroll',
        'title' => __( 'Plugin Admin Horizontal Scrolling', 'members-database' ),
        'group' => 'pdb-admin',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'use horizontal scrolling on list and fields management screens', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'record_edit_capability',
        'title' => __( 'Record Edit Access Level', 'members-database' ),
        'group' => 'pdb-admin',
        'options' => array(
            'type' => 'dropdown',
            'help_text' => __( 'sets the user access level for adding, editing and listing records.', 'members-database' ),
            'value' => 'edit_others_posts',
            'options' => $this->get_role_select(),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'plugin_admin_capability',
        'title' => __( 'Plugin Admin Access Level', 'members-database' ),
        'group' => 'pdb-admin',
        'options' => array(
            'type' => 'dropdown',
            'help_text' => __( 'sets the user access level for fields management, plugin settings, deleting records and CSV operations.', 'members-database' ),
            'value' => 'manage_options',
            'options' => $this->get_role_select(),
        )
    );
    
    $this->plugin_settings[] = array(
        'name' => 'admin_edits_validated',
        'title' => __( 'Admin Record Edits are Validated', 'members-database' ),
        'group' => 'pdb-admin',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'When checked, admin record edits and new records must pass validation.', 'members-database' ) ,
            'value' => 0,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'editor_allowed_csv_export',
        'title' => __( 'Editor can Export CSV Files', 'members-database' ),
        'group' => 'pdb-admin',
        'options' => array(
            'type' => 'checkbox',
            'help_text' => __( 'If checked, users with the plugin editor role can export a CSV.', 'members-database' ),
            'value' => '0',
            'options' => array(1, 0),
        )
    );
    

    $this->plugin_settings[] = array(
        'name' => 'show_time',
        'title' => __( 'Show Timestamp Time', 'members-database' ),
        'group' => 'pdb-admin',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __( 'Show time with timestamp dates', 'members-database' ),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );
    

    $this->plugin_settings[] = array(
        'name' => 'clear_pdb_notices',
        'title' => __( 'Clear Plugin Admin Notices', 'members-database' ),
        'group' => 'pdb-admin',
        'options' => array(
            'type' => 'checkbox',
            'help_text' => __( 'If checked, all plugin-generated admin notices will be cleared.', 'members-database' ),
            'value' => '0',
            'options' => array(1, 0),
        )
    );


    /*     * ****************************************************
     *
     *   custom CSS setting
     *
     * **************************************************** */
    $this->plugin_settings[] = array(
        'name' => 'custom_css',
        'title' => __( 'Custom Plugin Stylesheet', 'members-database' ),
        'group' => 'pdb-css',
        'options' => array(
            'type' => 'text-area',
            'value' => '',
            'help_text' => __( 'use this to add your own styles or override styles applied to the output of all plugin shortcodes', 'members-database' ),
            'attributes' => array(
                'style' => "height:20em;width:90%;max-width:400px;",
                'class' => 'code',
                'lang' => 'CSS'
            ),
        )
    );
    $this->plugin_settings[] = array(
        'name' => 'print_css',
        'title' => __( 'Plugin Print Stylesheet', 'members-database' ),
        'group' => 'pdb-css',
        'options' => array(
            'type' => 'text-area',
            'value' => "/* this prevents the search controls from printing */\r.pdb-searchform {\r\tdisplay:none;\r}",
            'help_text' => __( 'use this to format the printed output of all plugin shortcodes', 'members-database' ),
            'attributes' => array(
                'style' => "height:20em;width:90%;max-width:400px;",
                'class' => 'code',
                'lang' => 'CSS'
            ),
        )
    );
    $this->plugin_settings[] = array(
        'name' => 'custom_admin_css',
        'title' => __( 'Custom Admin Stylesheet', 'members-database' ),
        'group' => 'pdb-css',
        'options' => array(
            'type' => 'text-area',
            'value' => '',
            'help_text' => __( 'use this to add or modify CSS rules that are applied on all plugin pages in the WordPress backend.', 'members-database' ),
            'attributes' => array(
                'style' => "height:20em;width:90%;max-width:400px;",
                'class' => 'code',
                'lang' => 'CSS'
            ),
        )
    );
  }

  private function _get_pagelist( $with_none = false, $with_blank = false )
  {


    $key = ($with_none ? '1' : '0') . ($with_blank ? '1' : '0');
    $pagelist = wp_cache_get( $key, 'get_pagelist' );

    if ( $pagelist === false ) {

      if ( $with_blank )
        $pagelist['null_select'] = '';

      if ( $with_none )
        $pagelist[__( 'Same Page', 'members-database' )] = 'none';

      $pages = wp_cache_get( 'pagelist_posts' );

      if ( $pages === false ) {
        $pages = get_posts( array('post_type' => 'page', 'posts_per_page' => -1) );
        wp_cache_set( 'pagelist_posts', $pages );
      }

      foreach ( $pages as $page ) {
        $pagelist[Participants_Db::apply_filters( 'translate_string', $page->post_title )] = $page->ID;
      }

      /*
       * if you wish to include posts in the list of pages where the shortcode can be found, uncomment this block of code
       */
      /*

        $posts = get_posts( array( 'numberposts' => -1 ) );

        foreach( $posts as $post ) {

        $pagelist[ $post->post_title ] = $post->ID;

        }
       */
      wp_cache_set( $key, $pagelist, 'get_pagelist' );
    }

    return $pagelist;
  }

  private function _get_display_columns()
  {

    $columnlist = array(__( 'None', 'members-database' ) => 'none', 'null_select' => false);

    $columns = Participants_Db::get_column_atts( 'all' );

    $linkable = array();

    foreach ( $columns as $column ) {
      if ( PDb_FormElement::field_is_linkable( $column ) ) {
        $linkable[] = $column;
      }
    }

    return self::column_dropdown_options( $linkable, $columnlist );
  }

  /**
   * this provides a set of fields that can be used to identify a record
   * 
   * @param bool $null if true include a null value
   * @global object $wpdb
   * @return array of fields as $title => $value
   */
  public static function _get_identifier_columns( $null = true )
  {
    $columnlist = wp_cache_get( 'id_columns' );

    if ( $columnlist === false ) {

      global $wpdb;

      $columnlist = $null ? array('null_select' => '') : array('null_select' => false);

      /**
       * @version 1.7.0.7
       * 
       * we exclude array-type and other inappropriate field types instead of explicitly including a list of types
       */
      $sql = '
SELECT v.name, v.title 
FROM ' . Participants_Db::$fields_table . ' v 
  INNER JOIN ' . Participants_Db::$groups_table . ' g 
    ON v.group = g.name 
      WHERE v.form_element NOT IN ("rich-text", "multi-checkbox","multi-dropdown","multi-select-other", "link", "image-upload", "file-upload", "password", "placeholder", "timestamp")
ORDER BY g.order, v.order';

      $columns = $wpdb->get_results( $sql, OBJECT_K );

      $columnlist = self::column_dropdown_options( $columns, $columnlist );

      wp_cache_set( 'id_columns', $columnlist );
    }

    return $columnlist;
  }

  private function _get_sort_columns()
  {

    $columnlist = array('null_select' => false);

    $columns = Participants_Db::get_column_atts( 'sortable' );

    return self::column_dropdown_options( $columns, $columnlist );
  }

  /**
   * builds a column list dropdown from an array of column objects
   * 
   * @param array $columns array of column objects
   * @param array $columnlist the array to build on: this is primarily so a default 
   *                          or null select value can be added to the resulting array
   * @return array [title (name)] => name
   */
  public static function column_dropdown_options( $columns, $columnlist = array() )
  {
    foreach ( $columns as $column ) {
      $columnlist[Participants_Db::title_key( $column->title, $column->name )] = $column->name;
    }
    return $columnlist;
  }

  /**
   * get the user roles in a form suitable for populating a dropdown
   * 
   * for custom roles, this looks for a unique capability assigned to the custom 
   * role. If there is no unique capability assigned to the custom role, it will 
   * not be offered as a choice in the setting
   * 
   * @return array all defined roles with a key capability
   */
  public static function get_role_select()
  {
    // these are the standard roles we will include
    $role_select = array(
        __( 'Contributor', 'members-database' ) => 'edit_posts',
        __( 'Author', 'members-database' ) => 'edit_published_posts',
        __( 'Editor', 'members-database' ) => 'edit_others_posts',
        __( 'Admin', 'members-database' ) => 'manage_options',
        'null_select' => false,
    );
    global $wp_roles;
    if ( !is_object( $wp_roles ) ) {
      return $role_select;
    }
    $roles = $wp_roles->roles;
    //error_log(__METHOD__.' roles:'.print_r($roles,1));
    $caps = array();
    // collect all standard capabilities and remove standard roles
    foreach ( array('administrator', 'editor', 'author', 'contributor', 'subscriber') as $role ) {
      if ( $role !== 'administrator' && is_array( $roles[$role]['capabilities'] ) )
        $caps += array_keys( $roles[$role]['capabilities'] );
      unset( $roles[$role] );
    }
    // add any custom roles
    if ( count( $roles ) > 0 ) {
      foreach ( $roles as $role ) {
        $new_caps = '';
        if ( is_array( $role['capabilities'] ) ) {
          $new_caps = array_diff( array_keys( $role['capabilities'] ), $caps );
        }
        if ( !empty( $new_caps ) ) {
          // we grab the first unique capability and use it to typify the role
          $role_select[$role['name']] = current( $new_caps );
        }
      }
    }
    return $role_select;
  }

  /**
   * grabs a list of available collations
   */
  public static function get_collation_list()
  {
    global $wpdb;
    $character_sets = $wpdb->get_results( 'SHOW CHARACTER SET' );

    $return = array('null_select' => false);
    foreach ( $character_sets as $set ) {
      $return[$set->Description] = $set->{'Default collation'};
    }
    return $return;
  }

  /**
   * gets the collation of the database
   */
  public static function get_collation()
  {
    global $wpdb;
    $character_set = $wpdb->get_row( 'SHOW TABLE STATUS WHERE `name` = "' . $wpdb->prefix . 'participants_database"' );

    return $character_set->Collation;
  }

  /**
   * displays a settings page form using the WP Settings API
   *
   * this function is called by the plugin on it's settings page
   *
   * @return null
   */
  public function show_settings_form()
  {
    $submit_button_args = array(
        'type' => 'submit',
        'class' => $this->submit_class,
        'value' => $this->submit_button,
        'name' => 'submit',
    );
    $news = PDb_Live_Notification_Handler::latest_news();
    $has_news_class = $news ? 'has-news-panel' : '';
    ?>
    <div class="wrap participants_db settings-class <?= $has_news_class ?>">
      <?php Participants_Db::admin_page_heading( Participants_Db::$plugin_title . ' ' . __( 'Settings', 'members-database' ) ) ?>
      <?php settings_errors(); ?>
      <form action="options.php" method="post" >
        <div class="ui-tabs">
          <ul class="ui-tabs-nav">
            <?php
            foreach ( $this->sections as $id => $title )
              printf( '<li><a href="#%s">%s</a></li>', Participants_Db::make_anchor( $id ), $title );
            ?>
          </ul>
          <?php
          settings_fields( $this->WP_setting );

          do_settings_sections( $this->settings_page );
          ?>
        </div>
        <?php printf( '<p class="submit">%s</p>', PDb_FormElement::get_element( $submit_button_args ) ); ?>

      </form>

    </div>
    <?php /**
     * @version 1.6.3
     * @filter pdb-show_live_notifications
     * 
     */ ?>
    <?php if ( $news && Participants_Db::apply_filters( 'show_live_notifications', true ) ) : ?>
      <div class="pdb-news-panel pdb-live-notification postbox">
        <?php echo wpautop( $news ); ?>
      </div>
    <?php endif ?>
    <?php
  }

  /**
   * displays a section subheader
   *
   * note: the header is displayed by WP; this is only what would go under that
   */
  public function options_section( $section )
  {

    $parts = explode( '_', $section['id'] );
    $name = Participants_db::make_anchor( end( $parts ) );

    printf( '<a id="%1$s" name="%1$s" class="%2$s" ></a>', $name, Participants_Db::$prefix . 'anchor' );

    if ( isset( $this->section_description[$name] ) )
      printf( '<div class="section-description" ><h4>%s</h4></div>', $this->section_description[$name] );
  }

  /**
   * validate settings
   * 
   * we define a number of validation tests for submitted settings
   * 
   * @param array $settings an array of all the settings andtheir submitted values
   * @return array the sanitized array
   */
  public function validate( $settings )
  {

    $this->increment_option_version();

    foreach ( $settings as $name => $value ) {

      switch ( $name ) {

        case 'empty_field_message':
        case 'invalid_field_message':
        case 'nonmatching_field_message':

          $settings[$name] = strip_tags( $value );
          break;

        case 'field_error_style':

          // test for CSS rule consistency
          $value = rtrim( $value, ';' ) . ';';
          $num_matches = preg_match_all( "%[ ]?([^:<> ]+\:[^:;<>]+;)%", $value, $matches );
          if ( $num_matches > 0 ) {
            $settings[$name] = implode( '', $matches[1] );
          } else
            $settings[$name] = '';
          break;
        case 'list_limit':
          if ( intval( $value ) < -1 ) {
            add_settings_error( $name, $name, sprintf( __( 'Only numeric values can be used for the "%s" setting.', 'members-database' ), $this->get_option_title( $name ) ), 'error' );
            $settings[$name] = $this->get_default_value( $name );
          }
          break;
        case 'image_upload_limit':
          if ( intval( $value ) < 1 ) {
            add_settings_error( $name, $name, sprintf( __( 'Only numeric values can be used for the "%s" setting.', 'members-database' ), $this->get_option_title( $name ) ), 'error' );
            $settings[$name] = $this->get_default_value( $name );
          }
          break;
        case 'clear_pdb_notices':
          // this gets reset every time
          $settings['clear_pdb_notices'] = '0';
          break;
      }
    }
    return $settings;
  }
  
  /**
   * supplies a settings help link
   * 
   * @param string $anchor the anchor string
   * @return string 
   */
  public function settings_help( $anchor )
  {
    $href = 'https://xnau.com/members-database-settings-help/';
    return '<a class="settings-help-icon" href="' . $href . '#' . $anchor . '" target="_blank"><span class="dashicons dashicons-editor-help"></span></a>';
  }
  
  /**
   * sets up filters for allowing access to settings values
   */
  private function add_settings_filters()
  { 
    add_filter( Participants_Db::$prefix . 'disable_live_notifications', function(){
      return Participants_Db::plugin_setting('disable_live_notifications', '0' ) == '1';
    });
    add_filter( Participants_Db::$prefix . 'files_use_content_base_path', function(){
      return Participants_Db::plugin_setting('files_use_content_base_path', '0' ) == '1';
    });
    add_filter( Participants_Db::$prefix . 'edit_record_timestamps', function(){
      return Participants_Db::plugin_setting('allow_record_timestamp_edit', '0' ) == '1';
    });
    
  }
  
  /**
   * provides the name of the base used for file and image uploads
   * 
   * @return string
   */
  private function files_base_label()
  {
    return Participants_Db::apply_filters( 'files_use_content_base_path', false ) ? __( 'Content Directory', 'members-database' ) : __( 'WordPress root', 'members-database' );
  }

}
