<?php
/**
 * Plugin Name:  Contact Form 7 Redirection
 * Description:  Contact Form 7 Add-on - Redirect after mail sent.
 * Version:      1.0.2
 * Author:       Query Solutions
 * Contributors: querysolutions, yuvalsabar
 * Requires at least: 4.0
 * Tested up to: 4.8.1
 *
 * Text Domain: wpcf7-redirect
 * Domain Path: /languages/
 */

class CF7_Redirect {
    public function __construct() {
        $this->plugin_url       = plugin_dir_url( __FILE__ );
        $this->plugin_path      = plugin_dir_path( __FILE__ );
        $this->version          = '1.0.2';
        $this->add_actions();
    }

    /**
     * All actions
     */
    private function add_actions() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
        add_action( 'wpcf7_editor_panels', array( $this, 'add_panel' ) );
        add_action( 'wpcf7_after_save', array( $this, 'store_meta' ) );
        add_action( 'wpcf7_after_create', array( $this, 'duplicate_form_support' ) );
        add_action( 'admin_notices', array( $this, 'admin_notice' ) );
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'wpcf7-redirect', false, basename( dirname( __FILE__ ) ) . '/languages' );
    }

    /**
     * Enqueue theme styles and scripts - back-end
     */
    public function enqueue_backend() {
        wp_enqueue_style( 'wpcf7-redirect-admin-style', $this->plugin_url . 'admin/wpcf7-redirect-admin-style.css' );
        wp_enqueue_script( 'wpcf7-redirect-admin-script', $this->plugin_url . 'admin/wpcf7-redirect-admin-script.js', array(), null, true );
    }

    /**
     * Enqueue theme styles and scripts - front-end
     */
    public function enqueue_frontend() {
        wp_enqueue_script( 'wpcf7-redirect-script', $this->plugin_url . 'js/wpcf7-redirect-script.js', array(), null, true );
        wp_localize_script( 'wpcf7-redirect-script', 'wpcf7_redirect_forms', $this->get_forms() );
    }

    /**
     * Adds a tab to the editor on the form edit page
     */
    public function add_panel( $panels ) {
        $panels['redirect-panel'] = array( 
            'title'     => __( 'Redirect Settings', 'wpcf7-redirect' ), 
            'callback'  => array( $this , 'create_panel_inputs' )
        );
        return $panels;
    }

    /**
     * Create the panel inputs
     */
    public function create_panel_inputs( $post ) {
        wp_nonce_field( 'wpcf7_redirect_page_metaboxes', 'wpcf7_redirect_page_metaboxes_nonce' );
        $wpcf7_redirect_page                = get_post_meta( $post->id(), '_wpcf7_redirect_page_id', true );
        $wpcf7_redirect_external_url        = get_post_meta( $post->id(), '_wpcf7_redirect_external_url', true );
        $wpcf7_redirect_use_external_url    = get_post_meta( $post->id(), '_wpcf7_redirect_use_external_url', true );
        $wpcf7_redirect_open_in_new_tab     = get_post_meta( $post->id(), '_wpcf7_redirect_open_in_new_tab', true );

        // The meta box content
        $dropdown_options = array (
                'echo'              => 0,
                'name'              => 'wpcf7-redirect-page-id', 
                'show_option_none'  => __( 'Choose Page', 'wpcf7-redirect' ),
                'option_none_value' => '0',
                'selected'          => $wpcf7_redirect_page
            );
        ?>
        
        <h3>
            <?php esc_html_e( 'Redirect Settings', 'wpcf7-redirect' );?>
        </h3>
        <fieldset>
            <legend>
                <?php esc_html_e( 'Select a page to redirect to on successful form submission.', 'wpcf7-redirect' );?>      
            </legend>

            <div class="field-wrap">
                <?php echo wp_dropdown_pages( $dropdown_options );?>        
            </div>

            <div class="field-wrap">
                <input type="url" placeholder="<?php esc_html_e( 'External URL', 'wpcf7-redirect' );?>" name="wpcf7-redirect-external-url" value="<?php echo $wpcf7_redirect_external_url;?>">
            </div>

            <div class="field-wrap">
                <input type="checkbox" name="wpcf7-redirect-use-external-url" <?php checked( $wpcf7_redirect_use_external_url, 'on', true ); ?>/>
                <label for="wpcf7-redirect-use-external-url"><?php esc_html_e( 'Use external URL', 'wpcf7-redirect' );?></label>
            </div>

            <div class="field-wrap">
                <input type="checkbox" name="wpcf7-redirect-open-in-new-tab" <?php checked( $wpcf7_redirect_open_in_new_tab, 'on', true ); ?>/>
                <label for="wpcf7-redirect-open-in-new-tab"><?php esc_html_e( 'Open page in a new tab', 'wpcf7-redirect' );?></label>
                <div class="field-notice field-notice-alert field-notice-hidden">
                    <strong><?php esc_html_e( 'Notice!', 'wpcf7-redirect' );?></strong>
                    <?php esc_html_e( 'This option might not work as expected, since browsers often block popup windows. This option depends on the browser settings.', 'wpcf7-redirect' );?>
                </div>
            </div>
        </fieldset>
        
        <?php
    }

    /**
     * Store Form Data
     */
    public function store_meta( $contact_form ) {
        $contact_form_id = $contact_form->id();

        if ( ! isset( $_POST ) || empty( $_POST ) ) {
            return;
        }
        else {
            // Verify that the nonce is valid.
            if ( ! wp_verify_nonce( $_POST['wpcf7_redirect_page_metaboxes_nonce'], 'wpcf7_redirect_page_metaboxes' ) ) {
                return;
            }

            // Validation and sanitize
            $page_id            = isset( $_POST['wpcf7-redirect-page-id'] ) ? intval( $_POST['wpcf7-redirect-page-id'] ) : '';
            $external_url       = isset( $_POST['wpcf7-redirect-external-url'] ) ? esc_url( filter_var( $_POST['wpcf7-redirect-external-url'], FILTER_SANITIZE_URL ) ) : '';
            $use_external_url   = isset( $_POST['wpcf7-redirect-use-external-url'] ) ? sanitize_text_field( $_POST['wpcf7-redirect-use-external-url'] ) : '';
            $use_external_url   = ( $external_url && $use_external_url ) ? 'on' : '';
            $open_in_new_tab    = isset( $_POST['wpcf7-redirect-open-in-new-tab'] ) ? sanitize_text_field( $_POST['wpcf7-redirect-open-in-new-tab'] ) : '';
            $open_in_new_tab    = $open_in_new_tab ? 'on' : '';

            // Update the stored value
            update_post_meta( $contact_form_id, '_wpcf7_redirect_page_id', $page_id );
            update_post_meta( $contact_form_id, '_wpcf7_redirect_external_url', $external_url );
            update_post_meta( $contact_form_id, '_wpcf7_redirect_use_external_url', $use_external_url );
            update_post_meta( $contact_form_id, '_wpcf7_redirect_open_in_new_tab', $open_in_new_tab );
        }
    }

    /**
     * Get CF7 Forms ID's and it's Thank You Page.
     * @return array
     */
    public function get_forms() {
        $args = array(
            'post_type' => 'wpcf7_contact_form',
            'posts_per_page' => -1,
        );
        $query = new WP_Query( $args );

        if ( $query->have_posts() ) :

            while ( $query->have_posts() ) : $query->the_post();

                $page_id           = get_post_meta( get_the_ID(), '_wpcf7_redirect_page_id', true );
                $thankyou_page     = $page_id ? get_permalink( $page_id ) : '';
                $external_url      = get_post_meta( get_the_ID(), '_wpcf7_redirect_external_url', true );
                $use_external_url  = get_post_meta( get_the_ID(), '_wpcf7_redirect_use_external_url', true );
                $open_in_new_tab   = get_post_meta( get_the_ID(), '_wpcf7_redirect_open_in_new_tab', true );

                $forms[ get_the_ID() ] = array(
                    'thankyou_page_url' =>  $thankyou_page,
                    'external_url'      =>  $external_url,
                    'use_external_url'  =>  $use_external_url,
                    'open_in_new_tab'  =>  $open_in_new_tab
                );

            endwhile; wp_reset_query();

        endif;

        return $forms;
    }

    /**
     * Copy Redirect page key and assign it to duplicate form
     */
    public function duplicate_form_support( $contact_form ) {
        $contact_form_id = $contact_form->id();

        // Get the old form ID.
        if ( ! empty( $_REQUEST['post'] ) && ! empty( $_REQUEST['_wpnonce'] ) ) {
            $post                  = intval( $_REQUEST['post'] );
            $old_page_id           = get_post_meta( $post, '_wpcf7_redirect_page_id', true );
            $old_external_url      = get_post_meta( $post, '_wpcf7_redirect_external_url', true );
            $old_use_external_url  = get_post_meta( $post, '_wpcf7_redirect_use_external_url', true );
            $old_open_in_new_tab   = get_post_meta( $post, '_wpcf7_redirect_open_in_new_tab', true );
        }
        // Update the duplicated form.
        update_post_meta( $contact_form_id, '_wpcf7_redirect_page_id', $old_page_id );
        update_post_meta( $contact_form_id, '_wpcf7_redirect_external_url', $old_external_url );
        update_post_meta( $contact_form_id, '_wpcf7_redirect_use_external_url', $old_use_external_url );
        update_post_meta( $contact_form_id, '_wpcf7_redirect_open_in_new_tab', $old_open_in_new_tab );
    }

    /**
     * Verify CF7 dependencies.
     */
    public function admin_notice() {
        if ( is_plugin_active('contact-form-7/wp-contact-form-7.php') ) {
            $wpcf7_path = plugin_dir_path( dirname( __FILE__ ) ) . 'contact-form-7/wp-contact-form-7.php';
            $wpcf7_data = get_plugin_data( $wpcf7_path, false, false);

            // If CF7 version is < 4.2.0.
            if ( $wpcf7_data['Version'] < 4.2 ) {
                ?>

                <div class="error notice">
                    <p>
                        <?php esc_html_e( 'Error: Please update Contact Form 7.', 'wpcf7-redirect' );?>
                    </p>
                </div>

                <?php
            }
        }

        // If CF7 isn't installed and activated, throw an error
        else {
            $wpcf7_path = plugin_dir_path( dirname(__FILE__) ) . 'contact-form-7/wp-contact-form-7.php';
            $wpcf7_data = get_plugin_data( $wpcf7_path, false, false);
            ?>

            <div class="error notice">
                <p>
                    <?php esc_html_e( 'Error: Please install and activate Contact Form 7.', 'wpcf7-redirect' );?>
                </p>
            </div>

            <?php
        }
    }
}

$cf7_redirect = new CF7_Redirect();