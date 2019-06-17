<?php
add_action( 'wp_enqueue_scripts', 'storefront_child_enqueue_styles' );

function storefront_child_enqueue_styles() {
    $parent_style = 'parent-style';
    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );
}

/* Add the webpack script  - bundle.js - */
add_action('wp_enqueue_scripts', 'storefront_child_scripts');

function storefront_child_scripts() {
    wp_enqueue_script( 'theme_js', get_stylesheet_directory_uri() . '/public/js/bundle.js', array('jquery'), '', true );
}

/*
 * The action add_birthday_field_to_checkout will create a new text field in the checkout form
 * of woocommerce, I will this field to get the user's date of Birthday 
 */
add_action('woocommerce_before_checkout_billing_form', 'add_birthday_field_to_checkout');

function add_birthday_field_to_checkout($checkout) {
    woocommerce_form_field( 'billing_customer_birthday', array(
        'type'     => 'text',
        'label'    => __( 'Birthday (DD/MM/YYYY)' ),
        'required' => true
    ), $checkout->get_value('billing_customer_birthday') );
    ?>
    <script type="text/javascript">
    
    /* While the user is typing something inside the Birthday fields this script will check if
    the value inserted in the field corresponds to the date format DD/MM/YYYY, it checks if the
    value match with the regular expression of the date, if not it will display an error message,
    if yes it extract the year inserted by the user from the value, it get the current year and
    calculate the age of the user, if this age is < 18 it will display an error to the user, if
    the age is >= 18 it will display a success message */

    jQuery( "<p class='birthday_check'></p>" ).insertAfter( "#billing_customer_birthday" );
    jQuery('#billing_customer_birthday').keyup(function(){	
        value = jQuery('#billing_customer_birthday').val();
        var re = /([0-9]{2})\/([0-9]{2})\/([0-9]{4})/;
        if(!re.test(value)){
        	jQuery('.birthday_check').addClass("birthday_check_error");
        	jQuery('.birthday_check').removeClass("birthday_check_success");
        	jQuery('.birthday_check').text("Wrong date format");
        }else{
        	var data = value.split("/");
        	var year = data[2];
        	var currentyear = new Date().getFullYear();
        	if(currentyear - year<18){
            	jQuery('.birthday_check').addClass("birthday_check_error");
            	jQuery('.birthday_check').removeClass("birthday_check_success");
        		jQuery('.birthday_check').text("You are not allowed to continue the purchase, you must be older than 18 years");
        	} else{
            	jQuery('.birthday_check').removeClass("birthday_check_error");
            	jQuery('.birthday_check').addClass("birthday_check_success");
        		jQuery('.birthday_check').text("Date Format OK");
        	}
        } 
    });
    </script>
    <?php
}


/*
 * The action add_gender_field_to_checkout will create a new text field in the checkout form
 * of woocommerce, I will this field to get the user's gender
 */
add_action('woocommerce_before_checkout_billing_form', 'add_gender_field_to_checkout');

function add_gender_field_to_checkout($checkout) {
    woocommerce_form_field( 'billing_customer_gender', array(
        'type'     => 'text',
        'label'    => __( 'Gender (m/f/x)' ),
        'required' => true
    ), $checkout->get_value('billing_customer_gender') );
    ?>    
    <script type="text/javascript">

    /* The method simply check if the value inserted by the user corresponds to one of the characters: m,f,x
     * if yes it display a success message, if not it display an error
     */
     
    jQuery( "<p class='gender_check'></p>" ).insertAfter( "#billing_customer_gender" );
    jQuery('#billing_customer_gender').keyup(function(){	
        value = jQuery('#billing_customer_gender').val();
        if ( (value=="m")||(value=="f")||(value=="x")){
        	jQuery('.gender_check').removeClass("gender_check_error");
        	jQuery('.gender_check').addClass("gender_check_success");
        	jQuery('.gender_check').text("Gender Field OK");
        	
        }else{
        	jQuery('.gender_check').removeClass("gender_check_success");
        	jQuery('.gender_check').addClass("gender_check_error");
        	jQuery('.gender_check').text("Wrong value for the Gender Field");
        }   
    });
    </script>
    <?php
}

/*
 * Now we have to disable the possibility to proceed with the checkout for users who don't have
 * inserted the right data inside the fields and also for users who put the correct data format
 * but are younger than 18 years. In order to do this we use these two actions gender_field_validation
 * and birthday_field_validation.
 * The action birthday_field_validation will do the same check done by the jQuery function above
 * in case of errors it will display woocommerce NoticeGroup and will not allow to the user to 
 * proceed with the checkout. The same behaviour will be applied by the gender_field_validation action. 
 */
add_action( 'woocommerce_checkout_process', 'birthday_field_validation' );

function birthday_field_validation() {
    if ( isset( $_POST['billing_customer_birthday'] ) && empty( $_POST['billing_customer_birthday'] ) ){
        wc_add_notice( __( 'Please insert your birthday', 'woocommerce' ), 'error' );
    }else{
        $regex="/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/";
        $check = preg_match($regex,$_POST['billing_customer_birthday']);
        if (!$check) {
            wc_add_notice( __( 'Wrong date format: '.$_POST['billing_customer_birthday'], 'woocommerce' ), 'error' );
        }else{
            $customerdata = explode("/", $_POST['billing_customer_birthday']);
            $customeryear = intval($customerdata[2]);
            $currentyear = intval(date("Y"));
            if($currentyear-$customeryear<18){
                wc_add_notice( __( 'You are not allowed to continue the purchase, you must be older than 18 years', 'woocommerce' ), 'error' );
            }
        }
    }
}

add_action( 'woocommerce_checkout_process', 'gender_field_validation' );

function gender_field_validation() {
    $gender=$_POST['billing_customer_gender'];
    $test1 = strcmp($gender,"m");
    $test2 = strcmp($gender,"f");
    $test3 = strcmp($gender,"x");
    if ( ($test1==0)||($test2==0)||($test3==0)){
        /* The field is fine */
    }else{
        wc_add_notice( __( 'Please insert a correct value for the Gender field', 'woocommerce' ), 'error' );
    }
}