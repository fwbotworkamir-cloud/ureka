<?php

    // ThemeForest customers update through the Envato Market plugin. The Freemius
    // updater writes to the same update_themes transient key at priority 10 and
    // overwrites (or unsets) the entry Envato Market added at priority 1, so it has
    // to be unhooked entirely rather than filtered afterwards.
    add_action('admin_init', 'kingster_freemius_disable_update');
    function kingster_freemius_disable_update(){
        if( !function_exists('kingster_fs') || !class_exists('FS_Plugin_Updater') ){
            return;
        }

        $purchase_code = get_option('envato_purchase_code_' . KINGSTER_ITEM_ID, '');
        if( empty($purchase_code) ){
            return;
        }

        $kingster_fs = kingster_fs();
        if( is_object($kingster_fs) && method_exists($kingster_fs, 'has_active_valid_license') && $kingster_fs->has_active_valid_license() ){
            return;
        }

        $updater = FS_Plugin_Updater::instance($kingster_fs);
        remove_filter('pre_set_site_transient_update_themes', array($updater, 'pre_set_site_transient_update_plugins_filter'));
    }

    add_action('kingster_fs_loaded', 'kingster_freemius_register_license_activation_hook');
    function kingster_freemius_register_license_activation_hook(){
        if( function_exists('kingster_fs') ){
            kingster_fs()->add_action('after_license_activation', 'kingster_freemius_verify_purchase');
            kingster_fs()->add_action('after_license_deactivation', 'kingster_freemius_after_license_deactivation');
            kingster_fs()->add_filter('connect_url', 'kingster_freemius_landing_url');
            kingster_fs()->add_filter('after_connect_url', 'kingster_freemius_landing_url');
            kingster_fs()->add_filter('after_pending_connect_url', 'kingster_freemius_landing_url');
            kingster_fs()->add_filter('show_admin_notice', '__return_false');
        }
    }

    function kingster_freemius_landing_url($url = ''){
        return admin_url('admin.php?page=theme_landing');
    }

    function kingster_freemius_after_license_deactivation($license = false){
        if( is_object($license) && isset($license->error) ){
            return;
        }

        wp_redirect(kingster_freemius_landing_url());
        exit;
    }

    add_filter('gdlr_core_getting_start_content', 'gdlr_core_getting_start_freemius', 10, 3);
    function gdlr_core_getting_start_freemius($ret, $type, $theme_landing){
        if( $type != 'registration' ) return '';
        if( !empty($theme_landing->is_verified) ) return '';

        $freemius_registration = array(
            'enabled' => false,
            'error' => esc_html__('Freemius is not available. Please contact support.', 'kingster'),
        );

        if( function_exists('kingster_fs') ){
            $kingster_fs = kingster_fs();

            if( is_object($kingster_fs) ){
                $freemius_registration = array(
                    'enabled' => true,
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'action' => $kingster_fs->get_ajax_action('activate_license'),
                    'security' => $kingster_fs->get_ajax_security('activate_license'),
                    'module_id' => $kingster_fs->get_id(),
                    'empty_message' => esc_html__('Please enter your license key.', 'kingster'),
                    'activating_message' => esc_html__('Activating license...', 'kingster'),
                    'success_message' => esc_html__('License activated successfully. Redirecting...', 'kingster'),
                    'error_message' => esc_html__('Unable to activate the license. Please try again.', 'kingster'),
                    'resend_action' => $kingster_fs->get_ajax_action('resend_license_key'),
                    'resend_security' => $kingster_fs->get_ajax_security('resend_license_key'),
                    'resend_empty_message' => esc_html__('Please enter the email address used for your purchase.', 'kingster'),
                    'resend_invalid_message' => esc_html__('Please enter a valid email address.', 'kingster'),
                    'resend_sending_message' => esc_html__('Sending license key...', 'kingster'),
                    'resend_success_message' => esc_html__('If an active license exists for this email, Freemius will send the license key shortly. Please check your inbox and spam folder.', 'kingster'),
                    'resend_error_message' => esc_html__('Unable to resend the license key. Please try again.', 'kingster'),
                );
            }
        }

        ob_start();
?>
<div class="gdlr-core-regis-wrap" >
    <div class="gdlr-core-regis-head" >
        <div class="gdlr-core-icon" >
            <svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.32167 13.7102C1.00909 14.0227 0.83343 14.4466 0.833336 14.8885V16.6985C0.833336 16.9196 0.921133 17.1315 1.07741 17.2878C1.23369 17.4441 1.44566 17.5319 1.66667 17.5319H4.16667C4.38768 17.5319 4.59964 17.4441 4.75592 17.2878C4.9122 17.1315 5 16.9196 5 16.6985V15.8652C5 15.6442 5.0878 15.4322 5.24408 15.276C5.40036 15.1197 5.61232 15.0319 5.83334 15.0319H6.66667C6.88768 15.0319 7.09964 14.9441 7.25592 14.7878C7.4122 14.6315 7.5 14.4196 7.5 14.1985V13.3652C7.5 13.1442 7.5878 12.9322 7.74408 12.776C7.90036 12.6197 8.11232 12.5319 8.33334 12.5319H8.47667C8.91866 12.5318 9.34251 12.3561 9.655 12.0435L10.3333 11.3652C11.4915 11.7687 12.7524 11.7671 13.9096 11.3608C15.0668 10.9546 16.0518 10.1676 16.7036 9.12865C17.3554 8.08973 17.6353 6.86036 17.4976 5.64166C17.3598 4.42297 16.8126 3.28709 15.9454 2.41986C15.0781 1.55262 13.9423 1.00537 12.7236 0.867623C11.5049 0.729878 10.2755 1.0098 9.23656 1.66158C8.19764 2.31337 7.41066 3.29844 7.00438 4.45565C6.59809 5.61285 6.59655 6.87369 7 8.03188L1.32167 13.7102Z" stroke="white" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h2><?php esc_html_e('Register your website', 'kingster') ?></h2>
        <div class="gdlr-core-description" ><?php esc_html_e('Choose how you purchased the theme and complete activation below to unlock updates, demos, and advanced tools.', 'kingster') ?></div>
    </div>
    <div class="gdlr-core-regis-method" >
        <div class="gdlr-core-col gdlr-core-fm" >
            <div class="gdlr-core-head" >
                <div class="gdlr-core-badge gdlr-core-blue" ><?php esc_html_e('Goodlayers Direct', 'kingster') ?></div>
                <div class="gdlr-core-badge gdlr-core-green" ><?php esc_html_e('Recommended', 'kingster') ?></div>
            </div>

            <h2><?php esc_html_e('Activate Goodlayers License', 'kingster') ?></h2>
            <div class="gdlr-core-description" ><?php echo wp_kses(__('Purchased directly from <strong>GoodLayers.com</strong>? Enter your license key from the confirmation message.', 'kingster'), array('strong' => array())); ?></div>
            <form class="kingster-freemius-activation-form">
                <label><?php esc_html_e('License Key', 'kingster') ?></label>
                <input type="text" name="freemius_license_key" placeholder="<?php esc_attr_e('Fill Your License Code', 'kingster') ?>" />
                <div class="gdlr-core-note" ><?php echo wp_kses(__('Your key usually begins with <strong>sk_</strong>', 'kingster'), array( 'strong' => array() )); ?></div>
                <input class="gdlr-core-button" type="submit" value="<?php esc_attr_e('Activate My License', 'kingster') ?>" />
                <div class="kingster-freemius-activation-message" aria-live="polite"></div>
            </form>
            <a class="gdlr-core-button gdlr-core-grey gdlr-core-resend-license" href="#" ><?php esc_html_e('Can\'t find the license?', 'kingster') ?></a>
            <form class="kingster-freemius-resend-form">
                <label><?php esc_html_e('Purchase Email', 'kingster') ?></label>
                <input type="email" name="freemius_resend_email" placeholder="<?php esc_attr_e('Email used during purchase', 'kingster') ?>" />
                <input class="gdlr-core-button" type="submit" value="<?php esc_attr_e('Send My License Key', 'kingster') ?>" />
                <div class="kingster-freemius-resend-message" aria-live="polite"></div>
            </form>
            <div class="gdlr-core-feature">
                <ul>
                    <li><?php esc_html_e('Full theme features and demo import unlocked immediately', 'kingster'); ?></li>
                    <li><?php esc_html_e('Automatic theme updates directly from your WordPress dashboard', 'kingster'); ?></li>
                    <li><?php esc_html_e('One year of premium support included with activation', 'kingster'); ?></li>
                </ul>
                <a class="gdlr-core-button2" href="https://goodlayers.com" target="_blank" ><?php esc_html_e('Learn More', 'kingster') ?></a>
            </div>
            <div class="gdlr-core-upsale">
                <p><strong><?php esc_html_e('Don\'t have a GoodLayers license yet?', 'kingster') ?></strong></p>
                <p>
                    <?php echo wp_kses(__('Purchase at <strong><a href="https://goodlayers.com" target="_blank" >goodlayers.com</a></strong>', 'kingster'), array( 'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() )); ?>
                </p>
            </div>
        </div>
        <div class="gdlr-core-col gdlr-core-tf" >
            <?php
                if( !empty($theme_landing->error_message) ){
                    echo '<div class="kingster-notification" ><i class="fa fa-info-circle"></i><p>';
                    echo gdlr_core_text_filter($theme_landing->error_message);
                    echo '</p></div>';
                }
            ?>
            <div class="gdlr-core-head" >
                <div class="gdlr-core-badge" ><?php esc_html_e('Themeforest Customer', 'kingster') ?></div>
            </div>

            <h2><?php esc_html_e('Activate with Purchase Code', 'kingster') ?></h2>
            <div class="gdlr-core-description" ><?php echo wp_kses(__('Purchased the theme on <strong>ThemeForest</strong>? Paste your item purchase code below to register your installation.', 'kingster'), array('strong' => array())); ?></div>
        
            <form method="POST">
                <input type="text" name="purchase_code" placeholder="<?php esc_attr_e('Fill Your Themeforest Purchase Code', 'kingster') ?>" />
                <input type="hidden" name="register" value="1" />
                <input class="gdlr-core-button" type="submit" value="<?php esc_attr_e('Register Now', 'kingster') ?>" />
            </form>

            <div class="gdlr-core-information" >
                <div class="gdlr-core-head" >
                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7.33335 14C11.0153 14 14 11.0153 14 7.33335C14 3.65146 11.0153 0.666687 7.33335 0.666687C3.65146 0.666687 0.666687 3.65146 0.666687 7.33335C0.666687 11.0153 3.65146 14 7.33335 14Z" stroke="#64748B" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5.39337 5.33339C5.55011 4.88783 5.85947 4.51213 6.26667 4.27281C6.67388 4.03349 7.15264 3.94601 7.61816 4.02586C8.08368 4.10571 8.50592 4.34774 8.81009 4.70907C9.11426 5.07041 9.28074 5.52774 9.28004 6.00005C9.28004 7.33339 7.28004 8.00005 7.28004 8.00005" stroke="#64748B" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7.33337 10.6667H7.34004" stroke="#64748B" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php esc_html_e('How do I find my purchase code?', 'kingster'); ?>
                </div>
                <ol>
                    <li><?php esc_html_e('Log in to your Envato Market account.', 'kingster'); ?></li>
                    <li><?php echo wp_kses(__('Click your username > <strong>Downloads</strong>.', 'kingster'), array('strong' => array())); ?></li>
                    <li><?php echo wp_kses(__('Find your item, click Download, and select <strong>License certificate & purchase code.</strong>', 'kingster'), array('strong' => array())); ?></li>
                    <li><?php esc_html_e('Open the file to find your Item Purchase Code (formatted as shown below):', 'kingster'); ?></li>
                </ol>
                <div class="gdlr-core-example" >baf6XXXX-b2BB-XXXX-cAAb-22XXXXf357A</div>
                <a href="#" ><?php esc_html_e('View screenshot guide →', 'kingster'); ?></a> 
            </div>
        </div>
    </div>
</div>
<style>
.gdlr-core-regis-wrap{ font-size: 15px; line-height: 1.7; padding: 30px; 
    background: #fff; color: #475569; border-radius: 10px; box-sizing: border-box; -webkit-box-sizing: border-box; }
.gdlr-core-regis-wrap *{ box-sizing: inherit; -webkit-box-sizing: inherit; }
.gdlr-core-regis-head{ margin-bottom: 35px; }
.gdlr-core-regis-head h2{ font-size: 24px; margin-top: 0; margin-bottom: 5px; }
.gdlr-core-regis-head .gdlr-core-icon{ display: flex; width: 44px; height: 44px; align-items: center; 
    justify-content: center; margin-bottom: 20px; background: #020617; border-radius: 10px; }
.gdlr-core-regis-method{ display: flex; gap: 20px; }
.gdlr-core-regis-method h2{ font-size: 19px; font-weight: 700; color: #020617; }
.gdlr-core-regis-method .gdlr-core-description{ margin-bottom: 30px }
.gdlr-core-regis-method .gdlr-core-head{ display: flex; gap: 5px; margin-bottom: 30px; }
.gdlr-core-regis-method .gdlr-core-badge{ font-size: 11px; font-weight: 600; letter-spacing: 1.4px; text-transform: uppercase; padding: 3px 12px;
    background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 15px; }
.gdlr-core-regis-method .gdlr-core-badge.gdlr-core-green{ color: #047857; background: #d1fae5; border-color: #a7f3d0; }
.gdlr-core-regis-method .gdlr-core-badge.gdlr-core-blue{ color: #1d4ed8; background: #eff6ff; border-color: #bfdbfe; }
.gdlr-core-regis-method .gdlr-core-col{ width: 50%; padding: 25px; border-radius: 10px; }
.gdlr-core-regis-method .gdlr-core-col.gdlr-core-fm{ border: 1px solid #e4eaf1; background: #f4f7fa; }
.gdlr-core-regis-method .gdlr-core-col.gdlr-core-tf{ border: 1px solid #e2e8f0; background: #fff; }
.gdlr-core-regis-method form label{ display: block; font-size: 13px; font-weight: 500; text-transform: uppercase; 
    letter-spacing: 2.52px; margin-bottom: 8px; color: #64748B; }
.gdlr-core-regis-method form input[type="text"], .gdlr-core-regis-method form input[type="email"]{ width: 100%; height: 48px; color: #8E9FB7; 
    background: #fff; border: 1px solid #e2d8f0; border-radius: 10px; }
.gdlr-core-regis-method form input[type="submit"]{ margin-top: 20px; cursor: pointer; }
.gdlr-core-regis-method .gdlr-core-note{ font-size: 12px; margin-top: 5px; }
.gdlr-core-regis-method .gdlr-core-button{ display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; 
    letter-spacing: 1.3px; text-transform: uppercase; width: 100%; height: 48px; color: #fff; background: #1565c0; border-radius: 10px; 
    border: none; outline: none; box-shadow: none; }
.gdlr-core-regis-method .gdlr-core-button.gdlr-core-grey{ color: #a1a6a9; background: #e3e8f2; margin-top: 8px; }
.kingster-freemius-resend-form{ display: none; padding: 18px; margin-top: 8px; border: 1px solid #e2e8f0; background: #fff; border-radius: 10px; }
.kingster-freemius-resend-form input[type="submit"]{ margin-top: 12px; }
.gdlr-core-regis-method .gdlr-core-upsale{ padding: 15px 20px 5px; color: #CBD5E1;  background: #020617; border-radius: 10px; }
.gdlr-core-regis-method .gdlr-core-upsale p{ margin-top: 0; }
.gdlr-core-regis-method .gdlr-core-upsale strong, .gdlr-core-upsale a{ color: #fff; }
.gdlr-core-regis-method .gdlr-core-button2{ height: 44px; padding: 4px 22px; display: inline-flex;
    align-items: center; justify-content: center; border: 2px solid #1565c0; color: #1565C0; border-radius: 10px; }
.gdlr-core-regis-method  .gdlr-core-feature{ padding: 20px 20px; margin: 20px 0; border: 1px solid #E2E8F0; background: #fff; border-radius: 10px; }
.gdlr-core-regis-method .gdlr-core-feature ul{ margin: 0 0 20px; }
.gdlr-core-regis-method .gdlr-core-feature ul li{ position: relative; padding-left: 30px; margin-bottom: 12px; }
.gdlr-core-regis-method .gdlr-core-feature ul li:before{ content: " "; display: block; width: 15px; height: 15px; 
    position: absolute; left: 5px; top: 6px; background-position: center; background-repeat: no-repeat;
    background-image: url("data:image/svg+xml,%3Csvg width='15' height='15' viewBox='0 0 15 15' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M7.33333 14C11.0152 14 14 11.0152 14 7.33329C14 3.65139 11.0152 0.666626 7.33333 0.666626C3.65143 0.666626 0.666664 3.65139 0.666664 7.33329C0.666664 11.0152 3.65143 14 7.33333 14Z' stroke='%2310B981' stroke-width='1.33333' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='M5.33333 7.33321L6.66666 8.66654L9.33333 5.99988' stroke='%2310B981' stroke-width='1.33333' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E%0A"); }
.gdlr-core-tf form{ padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; }
.gdlr-core-tf form input[type="submit"]{ margin-top: 12px; }
.gdlr-core-information{ padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; }
.gdlr-core-information .gdlr-core-head{ display: flex; align-items: center; gap: 8px; font-weight: 600; padding-bottom: 12px; color: #1E293B; border-bottom: 1px solid #e2e8f0; }
.gdlr-core-information ol{ margin-top: 0; color: #8090A5; }
.gdlr-core-information .gdlr-core-example{ font-size: 16px; font-weight: 600; color: #A3A8AE; margin-bottom: 20px; }
.gdlr-core-regis-method .gdlr-core-col .kingster-notification{ padding: 20px; border-radius: 10px; margin-bottom: 30px; }
.kingster-freemius-activation-message, .kingster-freemius-resend-message{ display: none; padding: 12px 14px; margin-top: 14px; border-radius: 8px; background: #eff6ff; color: #1d4ed8; }
.kingster-freemius-activation-message.gdlr-core-error, .kingster-freemius-resend-message.gdlr-core-error{ display: block; background: #fef2f2; color: #b91c1c; }
.kingster-freemius-activation-message.gdlr-core-success, .kingster-freemius-resend-message.gdlr-core-success{ display: block; background: #ecfdf5; color: #047857; }
.kingster-freemius-activation-message.gdlr-core-loading, .kingster-freemius-resend-message.gdlr-core-loading{ display: block; }
</style>
<script>
jQuery(function($){
    var freemiusRegistration = <?php echo wp_json_encode($freemius_registration); ?>;

    $('.kingster-freemius-activation-form').on('submit', function(e){
        e.preventDefault();

        var $form = $(this);
        var $button = $form.find('input[type="submit"]');
        var $message = $form.find('.kingster-freemius-activation-message');
        var licenseKey = $.trim($form.find('input[name="freemius_license_key"]').val());
        var buttonText = $button.val();

        var showMessage = function(message, type){
            $message
                .removeClass('gdlr-core-error gdlr-core-success gdlr-core-loading')
                .addClass(type)
                .text(message)
                .show();
        };

        if(!freemiusRegistration.enabled){
            showMessage(freemiusRegistration.error, 'gdlr-core-error');
            return;
        }

        if(!licenseKey){
            showMessage(freemiusRegistration.empty_message, 'gdlr-core-error');
            return;
        }

        showMessage(freemiusRegistration.activating_message, 'gdlr-core-loading');
        $button.prop('disabled', true).val(freemiusRegistration.activating_message);

        $.ajax({
            url: freemiusRegistration.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: freemiusRegistration.action,
                security: freemiusRegistration.security,
                license_key: licenseKey,
                module_id: freemiusRegistration.module_id
            }
        }).done(function(response){
            if(response && response.success){
                showMessage(freemiusRegistration.success_message, 'gdlr-core-success');

                if(response.next_page){
                    window.location.href = response.next_page;
                }else{
                    window.location.reload();
                }
            }else{
                showMessage((response && response.error)? response.error: freemiusRegistration.error_message, 'gdlr-core-error');
                $button.prop('disabled', false).val(buttonText);
            }
        }).fail(function(){
            showMessage(freemiusRegistration.error_message, 'gdlr-core-error');
            $button.prop('disabled', false).val(buttonText);
        });
    });

    $('.gdlr-core-resend-license').on('click', function(e){
        e.preventDefault();

        $(this).next('.kingster-freemius-resend-form').stop(true, true).slideToggle(200, function(){
            if($(this).is(':visible')){
                $(this).find('input[name="freemius_resend_email"]').trigger('focus');
            }
        });
    });

    $('.kingster-freemius-resend-form').on('submit', function(e){
        e.preventDefault();

        var $form = $(this);
        var $button = $form.find('input[type="submit"]');
        var $message = $form.find('.kingster-freemius-resend-message');
        var email = $.trim($form.find('input[name="freemius_resend_email"]').val());
        var buttonText = $button.val();

        var showMessage = function(message, type){
            $message
                .removeClass('gdlr-core-error gdlr-core-success gdlr-core-loading')
                .addClass(type)
                .text(message)
                .show();
        };

        if(!freemiusRegistration.enabled){
            showMessage(freemiusRegistration.error, 'gdlr-core-error');
            return;
        }

        if(!email){
            showMessage(freemiusRegistration.resend_empty_message, 'gdlr-core-error');
            return;
        }

        if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){
            showMessage(freemiusRegistration.resend_invalid_message, 'gdlr-core-error');
            return;
        }

        showMessage(freemiusRegistration.resend_sending_message, 'gdlr-core-loading');
        $button.prop('disabled', true).val(freemiusRegistration.resend_sending_message);

        $.ajax({
            url: freemiusRegistration.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: freemiusRegistration.resend_action,
                security: freemiusRegistration.resend_security,
                module_id: freemiusRegistration.module_id,
                email: email
            }
        }).done(function(response){
            if(response && response.success){
                showMessage(freemiusRegistration.resend_success_message, 'gdlr-core-success');
            }else{
                showMessage((response && response.error)? response.error: freemiusRegistration.resend_error_message, 'gdlr-core-error');
            }

            $button.prop('disabled', false).val(buttonText);
        }).fail(function(){
            showMessage(freemiusRegistration.resend_error_message, 'gdlr-core-error');
            $button.prop('disabled', false).val(buttonText);
        });
    });
});
</script>
<?php
        return ob_get_clean();
    }

    // 07/2026
    if( !function_exists('kingster_get_purchase_code') ){
		function kingster_get_purchase_code(){
			$purchase_code = get_option('envato_purchase_code_' . KINGSTER_ITEM_ID, '');
			if( !empty($purchase_code) ){
				return $purchase_code;
			}

			if( function_exists('kingster_fs') ){
				$kingster_fs = kingster_fs();

				if(
					is_object($kingster_fs) &&
					method_exists($kingster_fs, 'has_active_valid_license') &&
					$kingster_fs->has_active_valid_license()
				){
					$license = method_exists($kingster_fs, '_get_license')? $kingster_fs->_get_license(): false;
					if( is_object($license) && !empty($license->secret_key) ){
                        global $kingster_fs_active;
                        $kingster_fs_active = true;
                        
                        return $license->secret_key;
						return substr($license->secret_key, 0, 6) . str_repeat('*', max(4, strlen($license->secret_key) - 10)) . substr($license->secret_key, -4);
					}

					return ( is_object($license) && !empty($license->id) )? 'freemius-license-' . $license->id: 'freemius-license-active';
				}
			}

			return '';
		}
	}

    // send purchase code to Goodlayers server for verification
    function kingster_freemius_verify_purchase(){

        if(
            !function_exists('kingster_fs') ||
            !defined('KINGSTER_PURCHASE_VERFIY_URL') ||
            !defined('KINGSTER_FREEMIUS_ITEM_ID')
        ){
            return;
        }

        $kingster_fs = kingster_fs();
        $license = method_exists($kingster_fs, '_get_license')? $kingster_fs->_get_license(): false;
        $install = method_exists($kingster_fs, 'get_site')? $kingster_fs->get_site(): false;

        if( !is_object($license) || empty($license->secret_key) ){
            return;
        }

        $response = wp_remote_post(KINGSTER_PURCHASE_VERFIY_URL, array(
            'body' => array(
                'register' => 1,
                'item_name' => 'KINGSTER',
                'item_id' => KINGSTER_FREEMIUS_ITEM_ID,
                'website' => get_site_url(),
                'purchase_code' => $license->secret_key,
                'license_id' => ( !empty($license->id) )? $license->id: '',
                'install_id' => ( is_object($install) && !empty($install->id) )? $install->id: '',
                'source' => 'freemius'
            )
        ));

        if( is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200 ){
            return;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        update_option('fm_debug_data', $data);
        if( !empty($data['status']) && $data['status'] == 'success' ){
            if( !empty($data['download_key']) ){
                update_option('kingster_download_key', $data['download_key']);
            }
        }
    }

    function kingster_fs_registered_content(){
        $purchase_code = kingster_get_purchase_code();
        $kingster_fs = function_exists('kingster_fs') ? kingster_fs() : false;
        $deactivate_license_url = is_object($kingster_fs) ? $kingster_fs->_get_admin_page_url('account') : '';

?>
<div class="gdlr-core-download-section">
    <h2 class="gdlr-core-title">
        <?php esc_html_e('Download & Resources', 'kingster') ?>
        <span class="gdlr-core-banner" ><?php esc_html_e('Unlocked by your license', 'kingster') ?></span>
    </h2>
    <div class="gdlr-core-caption">
        <?php esc_html_e('Extra files that ship separately from the theme, plus the full documentation.', 'kingster') ?>
    </div>
    
    <ul class="gdlr-core-download-area" >
        <li>
            <span class="gdlr-core-icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18.1604 5.70207L9.5354 0.9104L0.9104 5.70207L9.5354 10.4937L18.1604 5.70207Z" stroke="#279FE0" stroke-width="1.82083" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M0.9104 5.70209V13.3688L9.5354 18.1604L18.1604 13.3688V5.70209" stroke="#279FE0" stroke-width="1.82083" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9.5354 10.4937V18.1604" stroke="#279FE0" stroke-width="1.82083" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="gdlr-core-content" >
                <h4 class="gdlr-core-title" ><?php esc_html_e('Demo & Extra Files', 'kingster') ?></h4>
                <div class="gdlr-core-caption" ><?php esc_html_e('Everything you need to reproduce our live demo and start customizing.', 'kingster') ?></div>
            </span>
            <span class="gdlr-core-action" >
                <a class="gdlr-core-button" href="<?php echo esc_url(kingster_get_download_url('freemius/kingster-files')); ?>" target="_blank" >
                    <svg width="12" height="13" viewBox="0 0 12 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5.6875 0.6875V8.1875" stroke="white" stroke-width="1.375" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2.5625 5.6875L5.6875 8.8125L8.8125 5.6875" stroke="white" stroke-width="1.375" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M0.6875 11.9375H10.6875" stroke="white" stroke-width="1.375" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php esc_html_e('Download', 'kingster') ?>
                </a>
            </span>
        </li>
        <li>
            <span class="gdlr-core-icon gdlr-core-orange">
                <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0.9104 2.82707C0.9104 2.31874 1.11233 1.83122 1.47178 1.47178C1.83122 1.11233 2.31874 0.9104 2.82707 0.9104H11.4521L16.2437 5.70207V16.2437C16.2437 16.7521 16.0418 17.2396 15.6824 17.599C15.3229 17.9585 14.8354 18.1604 14.3271 18.1604H2.82707C2.31874 18.1604 1.83122 17.9585 1.47178 17.599C1.11233 17.2396 0.9104 16.7521 0.9104 16.2437V2.82707Z" stroke="#E0912A" stroke-width="1.82083" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10.4937 0.9104V5.70207H15.2853" stroke="#E0912A" stroke-width="1.82083" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M4.74365 10.4937H12.4103M4.74365 14.327H10.4937" stroke="#E0912A" stroke-width="1.82083" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="gdlr-core-content" >
                <h4 class="gdlr-core-title" ><?php esc_html_e('Documentation', 'kingster') ?></h4>
                <div class="gdlr-core-caption" ><?php esc_html_e('Setup steps, shortcodes, slider import and FAQ — always kept up to date.', 'kingster') ?></div>
            </span>
            <span class="gdlr-core-action" >
                <a class="gdlr-core-button gdlr-core-grey" href="https://demo.goodlayers.com/document/kingster" target="_blank">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.9375 0.6875H10.6875V4.4375" stroke="#3A3D42" stroke-width="1.375" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10.6875 0.6875L5.0625 6.3125" stroke="#3A3D42" stroke-width="1.375" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.4375 6.9375V9.4375C9.4375 9.76902 9.3058 10.087 9.07138 10.3214C8.83696 10.5558 8.51902 10.6875 8.1875 10.6875H1.9375C1.60598 10.6875 1.28804 10.5558 1.05362 10.3214C0.819196 10.087 0.6875 9.76902 0.6875 9.4375V3.1875C0.6875 2.85598 0.819196 2.53804 1.05362 2.30362C1.28804 2.0692 1.60598 1.9375 1.9375 1.9375H4.4375" stroke="#3A3D42" stroke-width="1.375" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php esc_html_e('Open Docs', 'kingster') ?>
                </a>
            </span>
        </li>
    </ul>
</div>
<div class="gdlr-core-registered-section">
    <div class="gdlr-core-col2" >
        <h4 class="gdlr-core-title"><?php esc_html_e('Your License Code', 'kingster') ?></h4>
        <div class="gdlr-core-license-area" >
            <span class="gdlr-core-icon" >
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3.33329 10.6666C4.80605 10.6666 5.99996 9.47274 5.99996 7.99998C5.99996 6.52722 4.80605 5.33331 3.33329 5.33331C1.86053 5.33331 0.666626 6.52722 0.666626 7.99998C0.666626 9.47274 1.86053 10.6666 3.33329 10.6666Z" stroke="#279FE0" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5.23328 6.10002L10.6666 0.666687" stroke="#279FE0" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 1.33331L11.3333 2.66665" stroke="#279FE0" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 3.33331L9.33333 4.66665" stroke="#279FE0" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="gdlr-core-text" >
                <input type="text" value="<?php echo esc_attr($purchase_code); ?>" readonly />
            </span>
            <span class="gdlr-core-copy" role="button" tabindex="0" aria-label="<?php esc_attr_e('Copy license code', 'kingster'); ?>" >
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11.3335 5.33337H6.66683C5.93045 5.33337 5.3335 5.93033 5.3335 6.66671V11.3334C5.3335 12.0698 5.93045 12.6667 6.66683 12.6667H11.3335C12.0699 12.6667 12.6668 12.0698 12.6668 11.3334V6.66671C12.6668 5.93033 12.0699 5.33337 11.3335 5.33337Z" stroke="#6B7178" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2.66675 9.33335H2.00008C1.64646 9.33335 1.30732 9.19288 1.05727 8.94283C0.807224 8.69278 0.666748 8.35364 0.666748 8.00002V2.00002C0.666748 1.6464 0.807224 1.30726 1.05727 1.05721C1.30732 0.807163 1.64646 0.666687 2.00008 0.666687H8.00008C8.3537 0.666687 8.69284 0.807163 8.94289 1.05721C9.19294 1.30726 9.33341 1.6464 9.33341 2.00002V2.66669" stroke="#6B7178" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </div>
    </div>
    <div class="gdlr-core-col2" >
        <h4 class="gdlr-core-title"><?php esc_html_e('Manage This Site', 'kingster') ?></h4>
        <p class="gdlr-core-description"><?php esc_html_e('Moving to a new domain or want to free up this activation?', 'kingster'); ?></p>
        <?php if( !empty($deactivate_license_url) ){ ?>
            <form class="kingster-freemius-deactivate-form" action="<?php echo esc_url($deactivate_license_url); ?>" method="post">
                <input type="hidden" name="fs_action" value="deactivate_license" />
                <?php wp_nonce_field('deactivate_license'); ?>
                <button type="submit" class="gdlr-core-button gdlr-core-grey">
                    <?php esc_html_e('Deactivate License', 'kingster') ?>
                </button>
            </form>
        <?php } ?>
    </div>
</div>
<style>
.gdlr-core-download-section{ max-width: 980px; font-size: 13px; color: #6B7178;
padding: 25px; margin-bottom: 20px; background: #fff; border-radius: 10px; }
ul.gdlr-core-download-area{ margin: 20px 0; border: 1px solid #e4e6e9; border-radius: 8px; }
ul.gdlr-core-download-area li{ border-bottom: 1px solid #e4e6e9; padding: 20px; display: flex; align-items: center; margin: 0 !important; gap: 20px; }
ul.gdlr-core-download-area h4.gdlr-core-title{ font-size: 14px; font-weight: bold; color: #2a2d31; margin: 0 0 10px; }
ul.gdlr-core-download-area .gdlr-core-icon{ width: 44px; height: 44px; background: #eaf6fd; display: flex; align-items: center; justify-content: center; border-radius: 9px; }
ul.gdlr-core-download-area .gdlr-core-icon.gdlr-core-orange{ background: #fff4e5; }
ul.gdlr-core-download-area .gdlr-core-action{ margin-left: auto; }
.gdlr-core-download-section .gdlr-core-button{ display: inline-flex; gap: 10px; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; 
    padding: 13px 25px; letter-spacing: 0.26px; color: #fff; background: #1565c0; border-radius: 10px; 
    border: none; outline: none; box-shadow: none; }
.gdlr-core-download-section .gdlr-core-button.gdlr-core-grey{ border: 1px solid #e3e8f2; color: #3a3d42; background: #fff; }
.gdlr-core-download-section .gdlr-core-banner{ font-size: 10.5px; letter-spacing: 0.63px; font-weight: 600; text-transform: uppercase; color: #1a8f63; border: 1px solid #c7edd9; background: #e5f7ee; padding: 6px 12px; margin-left: 10px; border-radius: 18px; }

.gdlr-core-download-section h2{ margin-top: 0; margin-bottom: 12px; }
.gdlr-core-registered-section{ display: flex; gap: 50px; max-width: 980px; font-size: 13px; color: #6B7178;
padding: 25px; margin-bottom: 20px; background: #fff; border-radius: 10px; }
.gdlr-core-registered-section .gdlr-core-col2{ width: 50%; }
.gdlr-core-registered-section input[type="text"]{ width: 100%; background: transparent; border: none; font-family: monospace; font-size: 13px; color: #3a3d42; }
.gdlr-core-registered-section input[type="text"]:focus{ outline: none; border: none; box-shadow: none; }
.gdlr-core-registered-section .gdlr-core-text{ flex-grow: 1; }
.gdlr-core-registered-section h4.gdlr-core-title{ font-size: 10.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.94px; color: #9aa0a6; }
.gdlr-core-registered-section p.gdlr-core-description{ font-size: 13px !important; margin: 0 0 20px !important; }
.gdlr-core-registered-section .gdlr-core-license-area { height: 44px; background: #f7f8fa; border: 1px solid #d9dbdf; border-radius: 7px; display: flex; align-items: center; gap: 15px; overflow: hidden; }
.gdlr-core-registered-section .gdlr-core-license-area .gdlr-core-icon { height: 44px; display: flex; align-items: center; justify-content: center; width: 41px; border-right: 1px solid #E2ECF3; background: #EEF7FD; }
.gdlr-core-registered-section .gdlr-core-license-area span.gdlr-core-copy{ height: 44px; display: flex; align-items: center; justify-content: center; width: 42px; background: #fff; cursor: pointer; margin-left: auto; border-left: 1px solid #E2E4E8; }
.gdlr-core-registered-section .gdlr-core-license-area span.gdlr-core-copy.gdlr-core-copied{ background: #ecfdf5; }
.gdlr-core-registered-section .gdlr-core-license-area span.gdlr-core-copy.gdlr-core-copied path{ stroke: #047857; }
.gdlr-core-registered-section .gdlr-core-button{ display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; 
    padding: 12px 25px; letter-spacing: 1.3px; text-transform: uppercase; color: #fff; background: #279FE0; border-radius: 10px; 
    border: none; outline: none; box-shadow: none; }
.gdlr-core-registered-section .gdlr-core-button.gdlr-core-grey{ border: 1px solid #e3e8f2; color: #3a3d42; background: #fff; }
.kingster-freemius-deactivate-form{ margin: 0; padding: 0; }
.kingster-freemius-deactivate-form .gdlr-core-button{ cursor: pointer; }
</style>
<script>
jQuery(function($){

    $('.gdlr-core-copy').on('click', function(e){
        e.preventDefault();

        var button = $(this);
        var input = button.siblings('span.gdlr-core-text').find('input[type="text"]');
        input.select();
        document.execCommand('copy');

        button.css('opacity', '0.5');
        setTimeout(function(){
            button.css('opacity', '1');
        }, 1000);
    });

    $('.kingster-freemius-deactivate-form').on('submit', function(e){
        if(!confirm('<?php echo esc_js(__('Are you sure you want to deactivate this license on the current site?', 'kingster')); ?>')){
            e.preventDefault();
        }
    });
});
</script>
<?php
    }

    add_action('admin_init', 'kingster_verify_purchase_schedule');
    function kingster_verify_purchase_schedule(){
        $time = get_option('kingster_purchase_verified_schedule', 0);
        if( empty($time) || time() - $time > 3 * DAY_IN_SECONDS ){
            $purchase_code = kingster_get_purchase_code();

            if( !empty($purchase_code) ){
                global $kingster_fs_active;
                if( !empty($kingster_fs_active) ){
                    kingster_freemius_verify_purchase();
                }else{  
                    // kingster_verify_purchase($purchase_code, 0);
                }
            }
            update_option('kingster_purchase_verified_schedule', time());
        }
    }
