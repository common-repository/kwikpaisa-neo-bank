<?php
/*
* Plugin Name: KwikPaisa NEO Bank PG 
* Plugin URI: https://www.kwikpaisa.com
* Description: Payment gateway plugin by KwikPaisa for Woocommerce sites
* Version: 1.4.6
* Author: Jangras Corporation
* Author URI: https://www.jangras.co
* WC requires at least: 5.0
* WC tested up to: 6.6.1
*/
 
// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'woocommerce_kwikpaisa_init', 0 );
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'kwikpaisa_action_links' );

function kwikpaisa_action_links( $links ) {
   $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout') ) .'">Setup</a>';
   return $links;
}

function woocommerce_kwikpaisa_init() {
  // If the parent WC_Payment_Gateway class doesn't exist
  // it means WooCommerce is not installed on the site
  // so do nothing
  if ( ! class_exists( 'WC_Payment_Gateway' ) ) return; 
   
  // If we made it this far, then include our Gateway Class
  class WC_Gateway_kwikpaisa extends WC_Payment_Gateway {
    
	    private $api_base_url;
        private $app_id;
        private $secret_key;
        private $environment;
	
	
	
    // Setup our Gateway's id, description and other values
    function __construct() {
      global $woocommerce;
      global $wpdb;
      $this->id = "kwikpaisa";
	  $this->order_button_text = __( 'Proceed to KwikPaisa', 'wc_gateway_kwikpaisa' );
      $this->icon = 'https://www.kwikpaisa.com/assets/logos/logo-black.png';
      $this->method_title = __( "KwikPaisa", 'wc_gateway_kwikpaisa' );
      $this->method_description = "KwikPaisa payment gateway redirects customers to checkout page to fill in their payment details and complete the payment";
      $this->title = __( "KwikPaisa", 'wc_gateway_kwikpaisa' ); 
      $this->has_fields = false;
      $this->init_form_fields();
      $this->init_settings();     
      $this->environment         = $this->settings['environment'];
	  
	  $this->api_base_url = $this->environment === 'sandbox' ? 'https://uat.api.kwikpaisa.com' : 'https://api.kwikpaisa.com';
	  
	  
	  
      $this->app_id     = $this->settings['app_id'];
      $this->secret_key     = $this->settings['secret_key'];
      $this->description    = $this->settings['description'];
      $this->title = $this->settings['title'];

      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	  add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_kwikpaisa_response' ) );		
	  add_action('woocommerce_blocks_payment_gateways', array($this, 'register_block_gateway'));
      //if ( isset( $_GET['kwikpaisa_callback'])) {		  
		//       $this->check_kwikpaisa_response();
      //  }
    }
  
  public function register_block_gateway($gateways) {
        $gateways[] = array(
            'id' => $this->id,
            'title' => $this->method_title,
            'description' => $this->method_description,
            'icon' => $this->icon,
        );
        return $gateways;
    }
  // Build the administration fields for this specific Gateway
    public function init_form_fields() {
      $this->form_fields = array(
                'enabled' => array(
                    'title'         => __('Enable/Disable', 'wc_gateway_kwikpaisa'),
                    'type'             => 'checkbox',
                    'label'         => __('Enable KwikPaisa payment gateway.', 'wc_gateway_kwikpaisa'),
                    'default'         => 'no',
                    'description'     => 'Show in the Payment List as a payment option'
                ),
                  'title' => array(
                    'title'         => __('Title:', 'wc_gateway_kwikpaisa'),
                    'type'            => 'text',
                    'default'         => __('KwikPaisa', 'wc_gateway_kwikpaisa'),
                    'description'     => __('This controls the title which the user sees during checkout.', 'wc_gateway_kwikpaisa'),
                    'desc_tip'         => true
                ),
                'description' => array(
                    'title'         => __('Description:', 'wc_gateway_kwikpaisa'),
                    'type'             => 'textarea',
                    'default'         => __("Pay securely via Card/Net Banking/Wallet via KwikPaisa."),
                    'description'     => __('This controls the description which the user sees during checkout.', 'wc_gateway_kwikpaisa'),
                    'desc_tip'         => true
                ),
                'environment' => array (
                    'type' => 'select',
                    'options' => array (
                        'sandbox' => __ ( 'Test Mode', 'wc_gateway_kwikpaisa' ),
                        'production' => __ ( 'Live Mode', 'wc_gateway_kwikpaisa' ) 
                    ),
                    'default' => 'sandbox',
                    'title' => __ ( 'Active Environment', 'wc_gateway_kwikpaisa' ),
                    'class' => array (
                        'wc_gateway_kwikpaisa-active-environment' 
                    ),
                    'tool_tip' => true,
                    'description' => __ ( 'You can enable Test mode or Live mode with this setting. When testing the plugin, enable Test mode and you can run test transactions using your KwikPaisa account.
                      When you are ready to go live, enable Live mode.', 'wc_gateway_kwikpaisa' ) 
                ),
                'app_id' => array(
                    'title'         => __('MID Key', 'wc_gateway_kwikpaisa'),
                    'type'             => 'text',
                    'description'     => __('Copy from your dashboard or contact KwikPaisa Team', 'wc_gateway_kwikpaisa'),
                    'desc_tip'         => true
                ),
                'secret_key' => array(
                    'title'         => __('MID Secret Key', 'wc_gateway_kwikpaisa'),
                    'type'             => 'password',
                    'description'     => __('Copy from your dashboard or contact KwikPaisa Team', 'wc_gateway_kwikpaisa'),
                    'desc_tip'         => true
                ),                
            );
    }

   

   public function getEnvironment()
  {
    $environment = $this->get_option( 'environment' ) === 'sandbox' ? 'sandbox' : 'production';
    return $environment;
  }
   
    function showMessage ($content) {
       return '<div class="woocommerce"><div class="'.$this->msg['class'].'">'.$this->msg['message'].'</div></div>'.$content;
    }
	

	
	
	
	
	
	
	    // Handle the return response
// Handle the return response
public function check_kwikpaisa_response() {
    if (isset($_GET['order_id'])) {
        $order_id_full = sanitize_text_field($_GET['order_id']);
        
        // Extract the actual order ID (part before the underscore)
        $order_id_parts = explode('_', $order_id_full);
        $actual_order_id = $order_id_parts[0];
        
        // Log the actual order ID
        error_log('KwikPaisa return response - Actual Order ID: ' . $actual_order_id);
        
        // Prepare data for status API request
        $api_base_url = $this->api_base_url . '/status';
        $postData = array(
            'order_id' => $order_id_full
        );
        
        // Make the API request
        $response = wp_remote_post($api_base_url, array(
            'method'    => 'POST',
            'body'      => json_encode($postData),
            'headers'   => array(
                'Content-Type'      => 'application/json',
                'x-client-id'       => $this->app_id,
                'x-client-secret'   => $this->secret_key,
                'order-source'      => 'rest-api'
            ),
        ));
        
        if (is_wp_error($response)) {
            wc_add_notice(__('Connection error.', 'woocommerce'), 'error');
            return;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($body['code'] === '200' && $body['status'] === 'success') {
            $order_data = $body['order_data'];
            $order = wc_get_order($actual_order_id);
            
            if ($order) {
                if ($order_data['order_status'] === 'PAID') {
                    // Mark order as completed
                    $order->payment_complete();
                    // Set transaction ID
                    $order->set_transaction_id($order_data['payment_data']['kwikX_payment_id']);
                    // Add order note
                    $order->add_order_note(
                        sprintf(
                            'Your payment is successful with KwikPaisa NEO Bank PG. Payment ID: %s, Bank Transaction ID: %s, Payment Method: %s',
                            $order_data['payment_data']['kwikX_payment_id'],
                            $order_data['payment_data']['bank_refrance_number'],
                            $order_data['payment_data']['payment_method']
                        )
                    );
                    wc_add_notice(__('Payment received. Thank you!', 'woocommerce'), 'success');
                    wp_redirect($this->get_return_url($order));
                    exit;
                } elseif ($order_data['order_status'] === 'UN_PAID') {
                    // Add order note for failed payment
                    $order->add_order_note(
                        sprintf(
                            'Payment failed. Payment Status: %s',
                            $order_data['payment_data']['payment_status']
                        )
                    );
                    // Redirect back to checkout page with failure message
                    wc_add_notice(__('Payment failed. Please try again.', 'woocommerce'), 'error');
                    wp_redirect(wc_get_checkout_url());
                    exit;
                }
            } else {
                wc_add_notice(__('Payment error: Order not found.', 'woocommerce'), 'error');
                wp_redirect(wc_get_checkout_url());
                exit;
            }
        } else {
            wc_add_notice(__('Payment error: API response error.', 'woocommerce'), 'error');
            wp_redirect(wc_get_checkout_url());
            exit;
        }
    } else {
        wc_add_notice(__('Payment error: Invalid return URL.', 'woocommerce'), 'error');
        wp_redirect(wc_get_checkout_url());
        exit;
    }
}

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
            public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $customerPhone = preg_replace('/[^0-9]/', '', $order->get_billing_phone());
            $customerEmail = $order->get_billing_email();
            $customerName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $amount = $order->get_total();
            $currencyCode = get_woocommerce_currency();
            $orderid = $order_id.'_'.time();
            $returnUrl = get_site_url() . '/wc-api/' . get_class($this) . '?order_id=' . $orderid;

            $postData = array(
                "app_id" => $this->app_id,
                "order_id" => $orderid,
                "order_amount" => $amount,
                "order_currency" => $currencyCode,
                "order_note" => "Additional order info",
                "service_type" => "DIGITAL",
                "customer_name" => $customerName,
                "customer_email" => $customerEmail,
                "customer_phone" => $customerPhone,
                "customer_address_line1" => $order->get_billing_address_1(),
                "customer_address_line2" => $order->get_billing_address_2(),
                "customer_address_city" => $order->get_billing_city(),
                "customer_address_state" => $order->get_billing_state(),
                "customer_address_country" => $order->get_billing_country(),
                "customer_address_postal_code" => $order->get_billing_postcode(),
                "return_url" => $returnUrl
            );
			
            // Generate signature
            ksort($postData);
            $signatureData = "";
            foreach ($postData as $key => $value) {
                $signatureData .= $key . $value;
            }

            $signature = hash_hmac('sha256', $signatureData, $this->secret_key, true);
            $signature = base64_encode($signature);

            // Add the signature to the post data
            $postData['order_checksum'] = $signature;
			

            // Make the API request
            $response = wp_remote_post($this->api_base_url . '/order', array(
                'method'    => 'POST',
                'body'      => json_encode($postData),
                'headers'   => array(
                    'Content-Type'      => 'application/json',
                    'x-client-id'       => $this->app_id,
                    'x-client-secret'   => $this->secret_key,
                    'order-source'      => 'reset-api'
                ),
            ));

            if (is_wp_error($response)) {
                wc_add_notice(__('Connection error.', 'woocommerce'), 'error');
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($body['status'] === 'success') {
                return array(
                    'result'   => 'success',
                    'redirect' => $body['return_data']['payment_link']
                );
            } else {
                wc_add_notice(__('Payment error: ', 'woocommerce') . (isset($body['return_data']['description']) ? $body['return_data']['description'] : 'Unknown error'), 'error');
                return;
            }
        }    // Handle the return response

  }
  
  add_filter( 'woocommerce_payment_gateways', 'add_kwikpaisa_gateway' );
  
  function add_kwikpaisa_gateway( $methods ) {
    $methods[] = 'WC_Gateway_kwikpaisa';
    return $methods;
  }
}
