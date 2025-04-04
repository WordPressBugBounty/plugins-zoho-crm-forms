<?php
if (!defined('ABSPATH'))
    exit;

$siteurl = site_url();
$siteurl = esc_url($siteurl);
$config = get_option("zcf_contactform7plugin_settings");
define('WP_LB_PLUGIN_URL', plugin_dir_url(__FILE__));
?>

<?php
$contactform7_plugin = get_option("ZcfLeadContactformPLugin");
?>
<input type="hidden" id="third_plugin_value" value='<?php echo esc_html($contactform7_plugin); ?>'>
<div class="dN">
    <form id="crmforms-thirdparty-settings-form" method="post">
        <input type="hidden" name="crmforms-thirdparty-settings-form" value="crmforms-thirdparty-settings-form" />
        <input type="hidden" id="plug_URL" value="<?php echo esc_url(ZCF_PLUGIN_BASE_URL); ?>" />
        <script>
            jQuery("#dialog-modal").hide();
        </script>
        <span id="Fields" style="margin-right:20px;"></span>
    </form>
</div>

  <div id="loading-image" style="display: none;"></div>

<?php
$captcha_config = get_option("zcf_captcha_settings");
$captcha = ZCF_PLUGIN_BASE_URL;
?>
<div>
    <div style="width:99%;">
        <div>

            <div class="captcha">
                <form id="crmforms-crmformswpbuilder-captcha-form" method="post">
                    <input type="hidden" name="crmforms-crmformswpbuilder-captcha-form" value="crmforms-crmformswpbuilder-captcha-form" />

                    <div class="form-group dN">
                        <label id="inneroptions" class="leads-builder-heading"><?php echo esc_html__('Notification ', 'zoho-crm-forms'); ?> </label>
                    </div>
                    <div class="form-group col-md-12 dN">
                        <div class="col-md-3">
                            <label id="innertext" class="leads-builder-label"><?php echo esc_html__('Which details you would like to be notified?', 'zoho-crm-forms'); ?> </label>
                        </div>
                        <div class="col-md-2">
                            <span id="circlecheck">
                                <select class="selectpicker form-control" data-live-search="false" name="emailcondition" id="emailcondition" onchange="enablecrmformsemail(this.id)">

                                    <option value="failure"  id = 'failureemailcondition' selected=selected>Failure
                                    </option>

                                </select>
                            </span>
                        </div>
                    </div>

                    <div class="form-group col-md-12 mt20">
                        <div class="col-md-3">
                            <label id="innertext" class="leads-builder-label"> <?php echo esc_html__('Which email address you need to be notified to?', "zoho-crm-forms"); ?> </label>
                        </div>
                        <div class="col-md-4">
                          <?php $emailId = sanitize_email($captcha_config['email']);?>
                            <input type='text' class='crmforms-vtiger-settings form-control' name='email' id='email' value="<?php
                            if (isset($captcha_config['email'])) {
                                echo esc_html($emailId);
                            }
                            ?>" <?php if (isset($captcha_config['emailcondition']) && $captcha_config['emailcondition'] == 'none') { ?> disabled="disabled"
                                   <?php } ?> />
                        </div>
                    </div>

                    <div class="form-group col-md-12">
                        <div class="col-md-3">
                            <label id="inneroptions" class="leads-builder-label"><?php echo esc_html__("Would you like to put google captcha in all your form? ", "zoho-crm-forms"); ?> <?php echo esc_html(str_repeat('&nbsp', 2)); ?>  </label>
                        </div>
                        <div class="col-md-4">
                            <span id="circlecheck">
                                <input type='radio'  name='crmforms_recaptcha' id='crmforms_recaptcha_no'  value="no"
                                <?php
                                if ($captcha_config['crmforms_recaptcha'] == 'no' || !isset($captcha_config['crmforms_recaptcha'])) {
                                    echo "checked";
                                }
                                ?> onclick="toggleRecaptcha('no');">
                                <label for="crmforms_recaptcha_no"  id="innertext"  class="leads-builder-label mr10"> <?php echo esc_html__('No', 'zoho-crm-forms'); ?>
                                </label>
                                <input type='radio'  name='crmforms_recaptcha' id='crmforms_recaptcha_yes'  value="yes"<?php
                                if ($captcha_config['crmforms_recaptcha'] == 'yes') {
                                    echo "checked";
                                }
                                ?> onclick="toggleRecaptcha('yes');">
                                <label for="crmforms_recaptcha_yes"  id="innertext"  class="leads-builder-label"> <?php echo esc_html__('Yes', 'zoho-crm-forms'); ?>
                                </label>
                            </span>
                        </div>
                    </div>

                    <div class='leads-captcha'>
                        <div id="recaptcha_public_key" <?php
                        if ($captcha_config['crmforms_recaptcha'] == 'no' || !isset($captcha_config['crmforms_recaptcha'])) {
                            echo 'style="display:none"';
                        } else {
                            echo 'style="display:block;margin-top:18px;"';
                        }
                        ?>
                             >
                            <div class="form-group col-md-12">
                                <div class="col-md-3">
                                    <label id="innertext" class="leads-builder-label"><?php echo esc_html__('Google Recaptcha Site Key', 'zoho-crm-forms'); ?>  <?php echo esc_html(str_repeat('&nbsp;', 50)); ?>   </label>
                                </div>

                                <div class="col-md-4">
                                    <input type='text' class='crmforms-vtiger-settings-text form-control' placeholder='<?php echo esc_attr__(' Enter your recaptcha site key here ', 'zoho-crm-forms'); ?>' name='recaptcha_public_key' id='crmforms_public_key' value="<?php echo esc_html($captcha_config['recaptcha_public_key']) ?>" />
                                </div>
                            </div>


                        </div>

                        <div id="recaptcha_private_key" <?php
                        if ($captcha_config['crmforms_recaptcha'] == 'no' || !isset($captcha_config['crmforms_recaptcha'])) {
                            echo 'style="display:none"';
                        } else {
                            echo 'style="display:block;margin-top:13px"';
                        }
                        ?>
                             >
                            <div class="form-group col-md-12">
                                <div class="col-md-3">
                                    <label id="innertext" class="leads-builder-label"><?php echo esc_html__("Google Recaptcha Secret Key", "zoho-crm-forms"); ?></label>
                                    <?php echo esc_html(str_repeat('&nbsp;', 50)); ?>
                                </div>
                                <div class="col-md-4">
                                    <input type='text' class='crmforms-vtiger-settings-text form-control' placeholder='<?php echo esc_attr__("Enter your recaptcha Secret key here", "zoho-crm-forms"); ?>' name='recaptcha_private_key' id='crmforms_private_key' value="<?php echo esc_html($captcha_config['recaptcha_private_key']) ?>" />
                                </div>
                            </div>
                        </div>
                        <!-- recaptcha private key div close -->
                    </div>
                    <!--leads captcha div close -->

                    <input type="hidden" name="posted" value="<?php echo 'posted'; ?>">
                    <input type="button" value="<?php echo esc_attr__('Save', 'zoho-crm-forms'); ?>" onclick="updatecaptchakey();" id="innersave" class="primarybtn" />

            </div>
        </div>
    </div>
