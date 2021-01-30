<?php
/**
 * Plugin Name: Learndash Sell Lesson
 * Version: 1.0
 * Description: This add-on allows you to sell learnDash lessons thorugh woocommerce porducts.
 * Author: Farooq Abdullah
 * Author URI: https://www.fiverr.com/farooq14162
 * Plugin URI: https://www.fiverr.com/farooq14162
 * Text Domain: learndash-sell-lesson
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Learndash_Sell_Lesson
 */
class Learndash_Sell_Lesson {

    const VERSION = '1.0';

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof Learndash_Sell_Lesson ) ) {
            self::$instance = new self;

            self::$instance->setup_constants();
            self::$instance->includes();
            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * defining constants for plugin
     */
    public function setup_constants() {

        /**
         * Directory
         */
        define( 'SLUW_DIR', plugin_dir_path ( __FILE__ ) );
        define( 'SLUW_DIR_FILE', SLUW_DIR . basename ( __FILE__ ) );
        define( 'SLUW_INCLUDES_DIR', trailingslashit ( SLUW_DIR . 'includes' ) );
        define( 'SLUW_TEMPLATES_DIR', trailingslashit ( SLUW_DIR . 'templates' ) );
        define( 'SLUW_BASE_DIR', plugin_basename(__FILE__));

        /**
         * URLs
         */
        define( 'SLUW_URL', trailingslashit ( plugins_url ( '', __FILE__ ) ) );
        define( 'SLUW_ASSETS_URL', trailingslashit ( SLUW_URL . 'assets/' ) );

        /**
         * Text Domain
         */
        define( 'SLUW_TEXT_DOMAIN', 'learndash-sell-lesson' );
    }

    /**
     * Includes
     */
    public function includes() {
    }

    /**
     * Plugin Hooks
     */
    public function hooks() {
        add_filter( 'woocommerce_product_data_tabs', [ $this, 'sluw_new_product_tab' ], 10, 1 );
        add_action( 'woocommerce_product_data_panels', [ $this, 'sluw_new_product_tab_content' ] );
        add_action( 'save_post', [ $this, 'sluw_update_course_name' ] );
        add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'sluw_display_lesson' ], 10 );
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'sluw_add_lesson_to_cart_item' ], 10, 3 );
        add_filter( 'woocommerce_get_item_data', [ $this, 'sluw_display_lesson_cart_page' ], 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'sluw_add_lesson_order_item' ], 10, 4 );
        add_filter( 'product_type_selector', [ $this, 'sluw_add_product_type' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'sluw_admin_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'sluw_theme_scripts' ] );
        add_action( 'woocommerce_order_status_completed', [ $this, 'sluw_order_status_completed' ], 10, 2 );
        add_filter( 'the_content', [ $this, 'sluw_display_lesson_content' ] );        
        add_action( 'wp', [ $this, 'sluw_submit_purchase_form' ] );

        add_action( 'learndash-lesson-row-title-before', [ $this, 'sluw_display_lesson_purchase_button' ], 10, 3 );
        add_action( 'admin_menu', [ $this, 'sluw_menu_page' ] );
        add_action( 'admin_post_sluw_submit_action', [ $this, 'sluw_save_settings' ] );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'sluw_update_coures_product_price' ], 10, 1);
        add_action( 'woocommerce_after_order_itemmeta', [ $this, 'sluw_show_lessons_on_order_details' ], 10, 3 );
        add_action( 'woocommerce_order_item_meta_end', [ $this, 'sluw_show_lessons_on_order_details' ], 10, 4 );
    }

    /**
     * Show lessons on order details
     */
    public function sluw_show_lessons_on_order_details( $item_id, $item, $product ) {
        if( $item->get_meta( 'course_lessons' ) != '' ) {
            $lessons = [];
            $lesson_ids = $item->get_meta( 'course_lessons' );

            if( stripos( $lesson_ids, ',' ) !== false ) {
                $lesson_ids = explode( ',', $item->get_meta( 'course_lessons' ) );
                foreach( $lesson_ids as $lesson_id ) {
                    if( $lesson_id != '' ) {
                        $lessons[] = ucwords( get_the_title( $lesson_id ) );
                    }  
                }    
            } else {
                $lessons[] = ucwords( get_the_title( str_replace( ',', '', $lesson_ids ) ) );
            }
            ?>
            <style>
                .woocommerce_order_items_wrapper.wc-order-items-editable #order_line_items .display_meta,
                .woocommerce-order-details-wrapper .wc-item-meta {
                    display: none;
                }
                .sluw-meta-data-row {
                    margin-bottom: 10px;
                } 
            </style>
            <div class="sluw-meta-data-row"><?php echo _e( '<b>Lesson(s):</b> ', SLUW_TEXT_DOMAIN ) . implode( ', ', $lessons ) ?></div>
            <?php
        }
    }

    /**
     * Update course product price
     */
    public function sluw_update_coures_product_price ( $cart ) {
        foreach ( $cart->get_cart() as $cart_item ) {

            $selected_lessons_count = 0;
            if( ! is_null( $cart_item['save-lesson'] ) && stripos( $cart_item['save-lesson'], ',' ) !== false ) {
                $selected_lessons = explode( ',', $cart_item['save-lesson'] );
                if( $selected_lessons ) {
                    foreach( $selected_lessons as $lesson_id ) {
                        if( ! empty( $lesson_id ) && $lesson_id != '' ) {
                            $selected_lessons_count++;
                        }
                    }
                }
            } else {
                $selected_lessons_count++;
            }

            $current_product_id = $cart_item['product_id'];
            $product_price = ( float ) $cart_item['data']->get_price();
            $course_id = ( int )get_post_meta( $current_product_id, 'sluw_course_name', true );
            $product_lessons = learndash_get_lesson_list( $course_id );
            $lessons_count = $product_lessons ? count( $product_lessons ) : 1;
            $opts = get_option( 'sluw_settings' );
            $percentage = isset( $opts['percentage'] ) ? ( float ) $opts['percentage'] : 15;
            $product_percentage = ( $product_price / 100 ) * $percentage;
            $updated_product_price = ( $product_price / $lessons_count );
            $updated_product_price = ( $updated_product_price + $product_percentage ) * $selected_lessons_count;
            $cart_item['data']->set_price( $updated_product_price );

        }
    }

    /**
     * Save plugin settings
     */
    public function sluw_save_settings() {
        if( isset( $_POST['sluw_submit'] ) && check_admin_referer( 'sluw_field', 'sluw_check_field' ) && current_user_can( 'manage_options' ) ) {
            $opts = [];
            if( isset( $_POST['sluw_restriction_msg'] ) ) {
                $opts['restriction_msg'] = $_POST['sluw_restriction_msg'];
            }
            if( isset( $_POST['sluw_percentage'] ) ) {
                $opts['percentage'] = $_POST['sluw_percentage'];
            }
            update_option( 'sluw_settings', $opts );
        }
        wp_safe_redirect( $_POST['_wp_http_referer'] );
        exit();
    }

    /**
     * Display lesson purcahse button on course lesson listing  
     */
    public function sluw_display_lesson_purchase_button( $lesson_id, $course_id, $user_id ) {
        $course_product = wc_get_products( array( 'meta_key' => 'sluw_course_name', 'meta_value' => $course_id ) );
        if( ! $course_product ) return;
        $lesson_ids = get_user_meta( $user_id, 'purchased_lessons_' . $course_id, true );
        $lesson_ids = $lesson_ids ? explode( ',', $lesson_ids ) : [];
        ?>
        <div class="sluw-lesson-purchase-btn">
        <?php
        if( ! in_array( $lesson_id, $lesson_ids ) ) { ?>
            <style>
                .ld-lesson-item-<?php echo $lesson_id; ?> .ld-status-icon {
                    display: none;
                }
                .ld-item-list-item-preview {
                    position: relative !important;
                }
            </style>
                <form method="post">
                  <input type='hidden' name='course_id' value="<?php echo $course_id; ?>">
                  <input type='hidden' name='lesson_id' value="<?php echo $lesson_id; ?>">
                  <input type='hidden' name='product_id' value="<?php echo isset( $course_product[0] ) ? $course_product[0]->get_id() : 0; ?>">
                  <input type='submit' name='sluw_submit' id='sluw_lesson_btn' value='<?php _e( 'Purchase', SLUW_TEXT_DOMAIN ) ?>'>
                </form>
                <span class="dashicons dashicons-lock sluw-lock-icon"></span>
        <?php
        } else { ?>
            <span class="dashicons dashicons-unlock sluw-lock-icon sluw-unlock-icon"></span>
        <?php
        } ?>
        </div>
        <?php
    }

    /**
     * Handle submit purchase form 
     */
    public function sluw_submit_purchase_form() {
        if( isset( $_POST['sluw_submit'] ) && class_exists( 'WC_Cart' ) && isset( $_POST['product_id'] ) ) {
            $sluw_course_id = $_POST['course_id'];
            $sluw_lesson_id = $_POST['lesson_id'];
            $cart = new WC_Cart;
            $cart->add_to_cart( $_POST['product_id'], 1, '', [], array( 'course_lessons' => $sluw_lesson_id ) );
            wp_safe_redirect( wc_get_checkout_url() );
            exit();
        }
    }

    /**
     * add script file
     */
    public function sluw_admin_scripts() {
        wp_enqueue_style( 'sluw-admin-css', SLUW_ASSETS_URL . 'css/sluw-admin.css' );
        wp_enqueue_script( 'sluw-admin-js', SLUW_ASSETS_URL. 'js/sluw-admin.js', ['jquery'], self::VERSION, true );
        global $post;

        $product_type = '';
        if( $post ) {
            $product_type = get_post_meta( $post->ID, 'sluw_product_type', true );
        }
        wp_localize_script( 'sluw-admin-js', 'SLUW_ADMIN', array(
            'productType' => $product_type 
        ) );
    }

    /**
     * Add admin script file
     */
    public function sluw_theme_scripts() {
        wp_enqueue_style( 'sluw-front-css', SLUW_ASSETS_URL . 'css/sluw-front.css' );
        wp_enqueue_script( 'dwp-front-js', SLUW_ASSETS_URL. 'js/sluw-front2.js', ['jquery'], self::VERSION, true );
    }

    /**
     * Add new product type
     */
    public function sluw_add_product_type( $types  ) {
        $types[ 'sluw_course' ] = __( 'LearnDash Courses', SLUW_TEXT_DOMAIN );
        return $types;
    }

    /**
     * Add new tab in woocommerce product edit page
     */
    public function sluw_new_product_tab( $tabs ) {
        
        $tabs['course-tab'] = array(
            'label'     => __( 'LearnDash Course', SLUW_TEXT_DOMAIN ),
            'target'    => 'sluw_course_tab',
            'priority'  => 60,
            'class'     => []
        );

        return $tabs;
    }

    /**
     * add dropdown list for select course
     */
    public function sluw_new_product_tab_content() {
        global $post;
    ?>
        <div id="sluw_course_tab" class="panel woocommerce_options_panel">
            <?php $selected_course_name = get_post_meta( $post->ID, 'sluw_course_name', true ); 
            $sluw_courses = get_posts( array(
                'post_type'         => 'sfwd-courses',
                'posts_per_page'    => -1,
                'post_status'       => 'publish'
            ) ); 
            ?>

            <label for="sluw_courses_list" id="sluw_course_label">
                <h4><?php echo __( 'Select a Course :', SLUW_TEXT_DOMAIN ); ?></h4>
            </label>
            <select id="sluw_courses_list" name="sluw_select">
                <option selected="selected"><?php echo __( 'Choose one', SLUW_TEXT_DOMAIN ); ?></option>
                <?php
                if( $sluw_courses ) {
                    foreach( $sluw_courses as $sluw_course ) {
                    ?>
                    <option value="<?php echo $sluw_course->ID; ?>" <?php echo $selected_course_name ==  $sluw_course->ID ? 'selected="selected"' : ''; ?>> <?php echo $sluw_course->post_name; ?> </option>
                    <?php
                    }
                }
                ?>
            </select>   
        </div>
        <?php       
    }

    /**
     * update course name
     */
    public function sluw_update_course_name( $post_id ) {

        if( get_post_type( $post_id ) == 'product' ) {
            $course = isset( $_POST['sluw_select'] ) ? sanitize_text_field( $_POST['sluw_select'] ) : ''; 

            if( !empty( $course ) ) {
                update_post_meta( $post_id, 'sluw_course_name', $course );
            }
        }

        /**
         * Save product type
         */
        if( isset( $_POST['product-type'] ) ) {
            update_post_meta( $post_id, 'sluw_product_type', $_POST['product-type'] );
        }
    }

    /**
     * display lesson of selected course
     */
    public function sluw_display_lesson() {
        global $post;
        $id = ( int )get_post_meta( $post->ID, 'sluw_course_name', true );

        $lesson_list = learndash_get_lesson_list( $id );
            foreach( $lesson_list as $list ){?>
                <label><input type="checkbox" id="sluw_save_lesson" name="save-lesson[]" class="sluw-lessons-cb" value="<?php echo $list->ID; ?>" size="100" required><?php echo $list->post_title . '<br>'; ?></label>
                <?php   
            }
    }

    /**
     * add value in cart item 
     */
    public function sluw_add_lesson_to_cart_item( $cart_item_data, $product_id, $variation_id ) {

        $lesson_value = $_POST['save-lesson'];
        
        if( ! $lesson_value ) {
            return $cart_item_data;
        }
        
        $lesson_name = '';

        foreach( $lesson_value as $l_value ) {
            $lesson_name .= $l_value. ',';
        }        
        $cart_item_data['save-lesson'] = $lesson_name;
        return $cart_item_data;
    }

    /**
     * display selected lesson in the cart
     */
    public function sluw_display_lesson_cart_page( $item_data, $cart_item ) {
        $meta_lessons = []; 
        if( isset( $cart_item['save-lesson'] ) ) {
            $meta_lessons = explode( ',', $cart_item['save-lesson'] );
        } elseif( isset( $cart_item['course_lessons'] ) ) {
            $meta_lessons = [ $cart_item['course_lessons'] ];
        }

        if( empty( $meta_lessons ) ) return $item_data;

        $lessons_to_display = [];
        if( $meta_lessons ) {
            foreach( $meta_lessons as $m_lesson ) {
                if( empty( $m_lesson ) ) {
                    continue;
                }
                $lessons_to_display[] = get_the_title( $m_lesson );
            }
        }
        
        $item_data[] = array(
            'key'     => __( 'Selected Lessons:', SLUW_TEXT_DOMAIN ),
            'value'   => implode( ', ', $lessons_to_display ),
            'display' => '',
        ); 

        return $item_data;
    }

    /**
     * add lesson in order item
     */
    public function sluw_add_lesson_order_item( $item, $cart_item_key, $values, $order ) {
        if( isset( $values['course_lessons'] )  ) {
            $item->add_meta_data( 'course_lessons', $values['course_lessons'] );
        }
        if( isset( $values['save-lesson'] )  ) {
            $item->add_meta_data( 'course_lessons', $values['save-lesson'] );
        }
    }

    /**
     * working on order status completed
     */
    public function sluw_order_status_completed( $order_id, $item ) {
          
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }
        $items = $order->get_items();
        $sluw_product_id = '';

        foreach( $items as $key => $item ) {
            $sluw_product_id = $item->get_product_id();
            $sluw_user_id = $order->get_user_id();
            $sluw_course_id = get_post_meta( $sluw_product_id, 'sluw_course_name', true );
    
            $custom_field = get_post_meta( $key, 'course_lessons', true);
      
            $lessons_ids = $item->get_meta( 'course_lessons' );
            
            if( $lessons_ids ) {

                $p_l_meta_key = 'purchased_lessons_' . $sluw_course_id;
            
                $already_purchased = get_user_meta( $sluw_user_id, $p_l_meta_key, true );
                if( empty( $already_purchased ) ) {
                    update_user_meta( $sluw_user_id, $p_l_meta_key, $lessons_ids );
                } else {
                    $updated_lessons = $already_purchased. ',' . $lessons_ids;
                    update_user_meta( $sluw_user_id, $p_l_meta_key, $updated_lessons );
                }   
            }
        }
        ld_update_course_access( $sluw_user_id, $sluw_course_id );
    }

    /**
     * display content of selected lesson
     */
    public function sluw_display_lesson_content( $content ) {

        $lesson_id = get_the_ID();
        $get_selected_course_id = learndash_get_course_id( $lesson_id );

        if( get_post_type() == 'sfwd-lessons' && is_user_logged_in() ) {
            $user_id = get_current_user_id();

            $selected_lesson_key = 'purchased_lessons_' . $get_selected_course_id;
            
            $get_selected_lesson_id = get_user_meta( $user_id, $selected_lesson_key, true );
            $convert_string_into_array = explode( ',', $get_selected_lesson_id );

            if( ! in_array( $lesson_id, $convert_string_into_array ) ) {
                    $args = array(
                        'post_type'    => 'product',
                        'post_status'  => 'publish',
                        'meta_key'     => 'sluw_course_name',
                        'meta_value'   => $get_selected_course_id,
                        'meta_compare' => '=='
                    );
                    
                    $products = new WP_Query( $args );
                    $product_ID = '';
                    if( $products->have_posts() ) {
                        while ( $products->have_posts() ) {
                            $products->the_post();
                            $product_ID = get_the_ID();
                        }
                    }
                    if( ! $product_ID || $product_ID == '' ) {
                        return $content;
                    }
                    $restriction_msg_array = get_option( 'sluw_settings' );
                    $restriction_msg = $restriction_msg_array['restriction_msg'];
                    $sluw_form = "<form method='post' id='restrict-form'>
                                  <input type='hidden' name='course_id' value='". $get_selected_course_id ."'>
                                  <input type='hidden' name='lesson_id' value='". $lesson_id ."'>
                                  <input type='hidden' name='product_id' value='".$product_ID."'>
                                  <input type='submit' name='sluw_submit' id='restrick-submit' value='".__( 'Purchase', SLUW_TEXT_DOMAIN )."'>
                                  </form>"; 
                    $restriction_msg = str_replace( '{purchase_button}', $sluw_form, $restriction_msg );
                echo "<div class='restrict-msg'>"; 
                return $restriction_msg;
                echo "<div>";
            }
        }
        return $content;
    }

    /**
     * add menu page 
     */
    public function sluw_menu_page() {
        add_submenu_page( 'learndash-lms', __( 'Lesson Sell Settings', SLUW_TEXT_DOMAIN ), __( 'Lesson Sell Settings', SLUW_TEXT_DOMAIN ), 'manage_options', 'sluw-options', [ $this, 'sluw_options_callback' ], $icon_url = '', $position = null );
    }

    /**
     * menu page callback function 
     */
    public function sluw_options_callback() { 
        $opts = get_option( 'sluw_settings' );
        $restriction_msg = isset( $opts['restriction_msg'] ) ? $opts['restriction_msg'] : '';
        $percentage = isset( $opts['percentage'] ) ? $opts['percentage'] : '';
    ?>
        <div class="sluw-settings-form">
            <h2><?php _e( 'Learndash Sell Lesson Settings', SLUW_TEXT_DOMAIN ); ?></h2>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <div class="sluw-row">
                    <div class="sluw-title">
                        <label class="sluw-label"><?php _e( 'Restriction Message', SLUW_TEXT_DOMAIN ); ?></label>
                    </div>
                    <div class="sluw-data">
                        <?php
                        wp_editor( $restriction_msg, 'sluw_restriction_msg', array(
                            'textarea_rows' => 5
                        ) );
                        ?> 
                        <div class="sluw-row"><code>{purchase_button}</code><?php _e( 'Shortcode to display purchase button.' ); ?></div>
                    </div>  
                </div>
                
                <div class="sluw-row">
                    <div class="sluw-title">
                        <label for="percentage" class="sluw-label"><?php _e( 'Lesson Sell Percentage', SLUW_TEXT_DOMAIN ); ?></label>
                    </div>
                    <div class="sluw-data">
                       <input type="text" name="sluw_percentage" placeholder="<?php echo __( 'Percentage', SLUW_TEXT_DOMAIN ) ?>" id="slue_percentage" value="<?php echo $percentage; ?>">
                    </div>  
                </div>
                <?php wp_nonce_field( 'sluw_field', 'sluw_check_field' ); ?>
                <input type="hidden" name="action" value="sluw_submit_action" />
                <input type="submit" name="sluw_submit" class="button button-primary" value="<?php echo __( 'Save changes', SLUW_TEXT_DOMAIN ) ?>" id="sluw_submit">
            </form>
        </div>
        <?php
    }
}

/**
 * Display admin notifications if dependency not found.
 */
function sluw_ready() {
    if( !is_admin() ) {
        return;
    }

    if( ! class_exists( 'SFWD_LMS' ) || ! class_exists( 'WooCommerce' )  ) {
        deactivate_plugins ( plugin_basename ( __FILE__ ), true );
        $class = 'notice is-dismissible error';
        $message = __( 'Learndash Sell Lesson add-on requires learndash and woocommerce plugins are to be activated', 'learndash-sell-lesson' );
        printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
    }
}

/**
 * @return bool
 */
function SLUW() {
    if ( ! class_exists( 'SFWD_LMS' ) || ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'sluw_ready' );
        return false;
    }

    return Learndash_Sell_Lesson::instance();
}
add_action( 'plugins_loaded', 'SLUW' );
