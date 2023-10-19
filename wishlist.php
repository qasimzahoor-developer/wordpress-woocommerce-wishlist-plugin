<?php
/**
* Plugin Name: WooCommerce Wishlist
* Plugin URI: https://github.com/qasimzahoor-developer/wordpress-woocommerce-wishlist-plugin
* Description: A customized plugin for multi wishlists.
* Version: 1.0
* Author: Qasim Zahoor
* Author URI: https://github.com/qasimzahoor-developer/
**/

if ( ! defined( 'ABSPATH' ) ) {
     exit; // Exit if accessed directly
}
/*
    1. Add wishlist to product
    2. Wishlist table shortcode
    3. Wishlist option in the user profile
    4. Extend rest API for products
*/

add_action('init','plugin_init');
function plugin_init(){
    if (class_exists("Woocommerce")) {
        function wishlist_plugin_scripts_styles(){
            wp_enqueue_style( 'wishlist-style', plugins_url('/css/style.css', __FILE__ ), array(), '1.0.0' );
            // wp_enqueue_script( 'wishlist-main', plugins_url('/js/main.js', __FILE__ ), array('jquery'), '', true);
            wp_register_script( 'wishlist-main', plugins_url('/js/main.js', __FILE__ ), array('jquery'), '', true);
            wp_enqueue_script( 'wishlist-main' );
            wp_localize_script(
                'wishlist-main',
                'opt',
                array(
                    'ajaxUrl'        => admin_url('admin-ajax.php'),
                    'ajaxPost'       => admin_url('admin-post.php'),
                    'restUrl'        => rest_url('wp/v2/product'),
                    'shopName'       => sanitize_title_with_dashes(sanitize_title_with_dashes(get_bloginfo('name'))),
                    'inWishlist'     => esc_html__("Already in wishlist","text-domain"),
                    'removeWishlist' => esc_html__("Remove from wishlist","text-domain"),
                    'buttonText'     => esc_html__("Details","text-domain"),
                    'cartbuttonText' => esc_html__("Add to Cart","text-domain"),
                    'error'          => esc_html__("Something went wrong, could not add to wishlist","text-domain"),
                    'noWishlist'     => esc_html__("No wishlist found","text-domain"),
                )
            );
        }
        add_action( 'wp_enqueue_scripts', 'wishlist_plugin_scripts_styles' );

        // Get current user data
        function fetch_user_data() {
            if (is_user_logged_in()){
                $current_user = wp_get_current_user();
                
                if(!empty($_POST['list_key']))
                {
                    $current_user_wishlist = get_user_meta( $current_user->ID, 'wishlist_'.$_POST['list_key'],true);
                }
                else
                {
                    $current_user_wishlist = get_user_meta( $current_user->ID, 'wishlist_1',true);
                }
    			echo json_encode(array('user_id' => $current_user->ID,'wishlist' => $current_user_wishlist));
            }
            die();
        } 
        add_action( 'wp_ajax_fetch_user_data', 'fetch_user_data' );
        add_action( 'wp_ajax_nopriv_fetch_user_data', 'fetch_user_data' );

        // Add wishlist to product
        add_action('woocommerce_before_shop_loop_item_title','wishlist_toggle',15);
        add_action('woocommerce_single_product_summary','wishlist_toggle',25);
        function wishlist_toggle(){

            global $product;
            // echo '<span class="wishlist-title">'.esc_attr__("Add to wishlist","text-domain").'</span><a class="wishlist-toggle" data-product="'.esc_attr($product->get_id()).'" href="#" title="'.esc_attr__("Add to wishlist","text-domain").'">'.file_get_contents(plugins_url( 'images/icon.svg', __FILE__ )).'</a>';
            echo '<span class="wishlist-title">'.esc_attr__("Add to wishlist","text-domain").'</span><a class="wishlist-toggle" onclick="$(\'#product_id\').val('.esc_attr($product->get_id()).')" data-toggle="modal" data-target="#showlist" href="#" title="'.esc_attr__("Add to wishlist","text-domain").'">'.file_get_contents(plugins_url( 'images/icon.svg', __FILE__ )).'</a>';
            // <a class="wishlist-toggle" data-product="'.esc_attr($product->get_id()).'" href="#" title="'.esc_attr__("Add to wishlist","text-domain").'">'.file_get_contents(plugins_url( 'images/icon.svg', __FILE__ )).'</a>
        
        }

        // Popup for Products

        add_action('wp_footer', 'footer_model'); 
        function footer_model() { 
            $model = '';
            $model .= 
            '
            <div class="modal fade" id="showlist" tabindex="-1000" role="dialog" aria-labelledby="showlistLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="showlistLabel">Select Wishlist to add Item</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <input type="hidden" id="product_id" name="product_id">
                  <div class="form-group">
                  <select style="width:200px;" id="list_key" name="list_key" class="form-control">
            ';
	        $qz_product_points = json_decode(get_user_meta(get_current_user_id(), '_qz_wishlist', true), true);
            if(is_array($qz_product_points) || is_object($qz_product_points))
            {
                foreach($qz_product_points as $key => $row)
                {
                    $model .= '<option value="'.$key.'">'.$row.'</option>';
                }    
            }
            $model .=
                '
                  </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="woocommerce-Button button" data-dismiss="modal">Cancel</button>
                  <button type="button" class="woocommerce-Button button" id="add_to_wishlist" name="add_to_wishlist">Add</button>
                </div>
              </div>
            </div>
          </div>              
            ';
            echo $model;     
        }
        

        // Wishlist option in the user profile
        add_action( 'show_user_profile', 'wishlist_user_profile_field' );
        add_action( 'edit_user_profile', 'wishlist_user_profile_field' );
        function wishlist_user_profile_field( $user ) { ?>
            <table class="form-table wishlist-data">
                <tr>
                    <th><?php echo esc_attr__("Wishlist","text-domain"); ?></th>
                    <td>
                        <input type="text" name="wishlist" id="wishlist" value="<?php echo esc_attr( get_the_author_meta( 'wishlist', $user->ID ) ); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
        <?php }

        add_action( 'personal_options_update', 'save_wishlist_user_profile_field' );
        add_action( 'edit_user_profile_update', 'save_wishlist_user_profile_field' );
        function save_wishlist_user_profile_field( $user_id ) {
            if ( !current_user_can( 'edit_user', $user_id ) ) {
                return false;
            }
              update_user_meta( $user_id, 'wishlist', $_POST['wishlist'] );
        }

        function update_wishlist_ajax(){
            if (isset($_POST["user_id"]) && !empty($_POST["user_id"])) {
                
                $user_id   = $_POST["user_id"];
                $user_obj = get_user_by('id', $user_id);

                $qz_product_points = json_decode(get_user_meta($user_id, '_qz_wishlist', true), true);
                if(is_array($qz_product_points) && (count($qz_product_points) == 0))
                {
                    $user_id = get_current_user_id();
                    $qz_product_points[] = 'Default';
                    update_user_meta( $user_id, '_qz_wishlist', json_encode($qz_product_points));     
                }
        
                if (!is_wp_error($user_obj) && is_object($user_obj)) {
                    update_user_meta( $user_id, 'wishlist_'.$_POST['list_key'], $_POST["wishlist"]);
                }
            }
            die();
        }
       // add_action('admin_post_nopriv_user_wishlist_update', 'update_wishlist_ajax');
        add_action('admin_post_user_wishlist_update', 'update_wishlist_ajax');

        // Wishlist table shortcode
        add_shortcode('wishlist', 'wishlist');
        function wishlist( $atts, $content = null ) {
            
        $user_id = get_current_user_id();
        // echo $user_id;
        if($user_id == 0)
        {
            wp_redirect( "https://shopglocally.co.uk");
            exit;
        }
        $qz_product_points = json_decode(get_user_meta($user_id, '_qz_wishlist', true), true);
        
        if(is_array($qz_product_points) && (count($qz_product_points) == 0))
        {
            $qz_product_points[1] = 'Default';
            update_user_meta( $user_id, '_qz_wishlist', json_encode($qz_product_points));     
        }
        if(isset($_POST['new_wishlist']) && !empty($_POST['new_wishlist']))
        {
            $qz_product_points = json_decode(get_user_meta($user_id, '_qz_wishlist', true), true);
            
            end($qz_product_points);
            $key = key($qz_product_points)+1;
            if($key <= 5)
            {
                $qz_product_points[$key] = $_POST['new_wishlist'];
                update_user_meta( $user_id, '_qz_wishlist', json_encode($qz_product_points));     
            }
            else
            {
                $error_add_wishlist = 'You have least numbers of wishlist added!';
            }
        }
        // if(isset($_POST['delete-wishlist']) && !empty($_POST['list_items']))
        // {
        //     $explode = explode('.',$_POST['list_items']);
        //     print_r($explode[1]);

	    //     $qz_product_points = json_decode(get_user_meta($user_id, '_qz_wishlist', true), true);
        //     print_r($qz_product_points);
        // }
            extract(shortcode_atts(array(), $atts));
            $qz_product_points = json_decode(get_user_meta($user_id, '_qz_wishlist', true), true);
	    
	   if(isset($error_add_wishlist))
	   {
	       echo '<span style="color:red;">'.$error_add_wishlist.'</span>';
	   }
            $show = '';
            $show .= '
                    <div class="col-lg-7" style="float:left;">
                    <form method="post" action="">
                        <input type="text" name="new_wishlist" placeholder="Enter wishlist name" class="woocommerce-Input woocommerce-Input--text input-text">
                        <input type="submit" name="add-wishlist" value="Add New Wishlist" class="dokan-btn dokan-btn-them">
                    </form>
                    </div>
                    <div class="col-lg-3">
                        <select style="width:200px;height:45px;" name="list_items" id="list_items" class="dokan-form-control">
                    ';
        if(is_array($qz_product_points) || is_object($qz_product_points))
        {
            foreach($qz_product_points as $key => $row)
            {
                $show .= '<option value="'.$key.'">'.$row.'</option>';
            }
    
        }            
            $show .=  '
                        </select>
                    </div>
                    <div class="col-lg-2" style="float:right;">
                    <form>
                        <input type="button" name="delete_wishlist" id="delete_wishlist" value="Delete Wishlist" class="woocommerce-Button button" style="float: left;">
                    </form>    
                    </div><br><br>
                    <div class="col-lg-12">
                    <table style="margin-bottom: -1px;position: relative;">
                        <tr>
                            <th width="43%"><!-- Left for image --></th>
                            <th width="10.5%">'.esc_html__("Name","text-domain").'</th>
                            <th width="7.35%">'.esc_html__("Price","text-domain").'</th>
                            <th width="11.9%">'.esc_html__("Stock","text-domain").'</th>
                            <th width="14%">'.esc_html__("Add to Cart","text-domain").'</th>
                            <th width="14%"><!-- Left for button --></th>
                        </tr>
                    </table>                    
                    <table class="wishlist-table loading">

                    </table>
                    </div>
                    ';
	        return $show;
        }

        //  delete wishlist 

        // do_action('wp_delete_wishlist');

        // add_action('wp_delete_wishlist','delete_wishlist');

        function wishlist_delete(){
            $user_id = get_current_user_id();
            
            $list_key = $_POST['list_key']; 

            $qz_product_points = json_decode(get_user_meta($user_id, '_qz_wishlist', true), true); 

            unset($qz_product_points[$list_key]);

            update_user_meta( $user_id, '_qz_wishlist', json_encode($qz_product_points));

            delete_metadata( $user_id, $user_id, 'wishlist_'.$list_key, $meta_value = '');        
        }


        add_action( 'wp_ajax_wishlist_delete', 'wishlist_delete' );
        // add_action( 'wp_ajax_nopriv_wishlist_delete', 'wishlist_delete' );

        // Extend REST API
        function rest_register_fields(){

            register_rest_field('product',
                'price',
                array(
                    'get_callback'    => 'rest_price',
                    'update_callback' => null,
                    'schema'          => null
                )
            );

            register_rest_field('product',
                'stock',
                array(
                    'get_callback'    => 'rest_stock',
                    'update_callback' => null,
                    'schema'          => null
                )
            );

            register_rest_field('product',
                'image',
                array(
                    'get_callback'    => 'rest_img',
                    'update_callback' => null,
                    'schema'          => null
                )
            );
        }
        add_action('rest_api_init','rest_register_fields');

        function rest_price($object,$field_name,$request){

            global $product;

            $id = $product->get_id();

            if ($id == $object['id']) {
                return $product->get_price();
            }

        }

        function rest_stock($object,$field_name,$request){

            global $product;

            $id = $product->get_id();

            if ($id == $object['id']) {
                return $product->get_stock_status();
            }

        }

        function rest_img($object,$field_name,$request){

            global $product;

            $id = $product->get_id();

            if ($id == $object['id']) {
                return $product->get_image();
            }

        }

        function maximum_api_filter($query_params) {
            $query_params['per_page']["maximum"]=100;
            return $query_params;
        }
        add_filter('rest_product_collection_params', 'maximum_api_filter');
    }
}
?>
