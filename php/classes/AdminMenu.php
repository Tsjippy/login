<?php
namespace TSJIPPY\LOGIN;

use function TSJIPPY\addElement;
use function TSJIPPY\addRawHtml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends \TSJIPPY\ADMIN\SubAdminMenu{

    /**
     * AdminMenu constructor.
     * 
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
        ob_start();
        ?>
        <p>
            You can enable user registration if you want.<br>
            If that's the case people can request an user account.<br>
            Once that account is approved they will be able to login.<br>
        </p>
        <label>
            <input type="checkbox" name="user-registration" value="enabled" <?php if($this->settings['user-registration']){echo 'checked';}?>>
            Enable user registration
        </label>
        <?php 
        if($this->settings['user-registration']){
            $url	= get_permalink(SETTINGS['register-page'] ?? '');

            if($url){
                ?>
                <a href="<?php echo $url;?>" target="_blank">View registration page</a>
                <?php
            }
        }

        if(wp_is_block_theme()){
            ?>
            <div class='warning' style='max-width: 500px;'>
                This site has an active block theme.<br>
                Make sure to add a login/logout block to your menu <a href="<?php echo SITEURL;?>/wp-admin/site-editor.php?p=%2Fnavigation">here</a>.
                <br>
                <br>
                <label>
                    <input type='checkbox' name='addLoginLogout' value="1">
                    Add Login/Logout Button
                </label>
            </div>
            <?php
            addRawHtml(ob_get_clean(), $parent);
        
            return true;
        }

        // Classic menus's
        $menus	= wp_get_nav_menus();
        

        if(empty($menus)){
            ?>
            <div class='warning' style='max-width: 500px;'>
                You do not have any active menu's. Please add one 
            </div>
            <?php

            addRawHtml(ob_get_clean(), $parent);

            return true;
        }
        ?>
        <br>
        <br>
        Where should the login menu item be added?
        <br>

        <table style="border: none;">
            <?php

            if(!isset($this->settings['visibilty-login-menu'])){
                $this->settings['visibilty-login-menu']	= [];
            }

            if(!isset($this->settings['visibilty-logout-menu'])){
                $this->settings['visibilty-logout-menu']	= [];
            }

            foreach($menus as $menu){
                $checked	= '';
                if(isset($this->settings['login-menu']) && in_array($menu->term_id, $this->settings['login-menu'])){
                    $checked	= 'checked';
                }

                if(!isset($this->settings['visibilty-login-menu'][$menu->term_id])){
                    $this->settings['visibilty-login-menu'][$menu->term_id]	= '';
                }
                echo "<tr>";
                    echo "<td>";
                        echo "<label>";
                            echo "<input type='checkbox' name='login-menu[]' value='$menu->term_id' $checked>";
                            echo "$menu->name";
                        echo "</label>";
                    echo "</td>";

                    $checked	= '';
                    if($this->settings['visibilty-login-menu'][$menu->term_id] == ''){
                        $checked	= 'checked';
                    }
                    echo "<td>";
                        echo "<input type='radio' id='$menu->term_id' name='visibilty-login-menu[$menu->term_id]' value='' $checked>";
                        echo "<label>Always</label>";
                    echo "</td>";

                    $checked	= '';
                    if($this->settings['visibilty-login-menu'][$menu->term_id] == 'mobile'){
                        $checked	= 'checked';
                    }
                    echo "<td>";
                        echo "<input type='radio' id='$menu->term_id' name='visibilty-login-menu[$menu->term_id]' value='mobile' $checked>";
                        echo "<label>Mobile only</label>";
                    echo "</td>";

                    $checked	= '';
                    if($this->settings['visibilty-login-menu'][$menu->term_id] == 'desktop'){
                        $checked	= 'checked';
                    }
                    echo "<td>";
                        echo "<input type='radio' id='$menu->term_id' name='visibilty-login-menu[$menu->term_id]' value='desktop' $checked>";
                        echo "<label>Desktop only </label>";
                    echo "</td>";
                echo "</tr>";
            }
            ?>
        </table>
        <br>
        Where should the logout menu item be added?
        <br>
        <table>
            <?php
            foreach($menus as $menu){
                $checked	= '';
                if(isset($this->settings['logout-menu']) && in_array($menu->term_id, $this->settings['logout-menu'])){
                    $checked	= 'checked';
                }
                echo "<tr>";
                    echo "<td>";
                        echo "<label>";
                            echo "<input type='checkbox' name='logout-menu[]' value='$menu->term_id' $checked>";
                            echo "$menu->name";
                        echo "</label>";
                    echo "</td>";

                    $checked	= '';
                    if($this->settings['visibilty-logout-menu'][$menu->term_id] == ''){
                        $checked	= 'checked';
                    }
                    echo "<td>";
                        echo "<input type='radio' id='$menu->term_id' name='visibilty-logout-menu[$menu->term_id]' value='' $checked>";
                        echo "<label>Always</label>";
                    echo "</td>";

                    $checked	= '';
                    if($this->settings['visibilty-logout-menu'][$menu->term_id] == 'mobile'){
                        $checked	= 'checked';
                    }
                    echo "<td>";
                        echo "<input type='radio' id='$menu->term_id' name='visibilty-logout-menu[$menu->term_id]' value='mobile' $checked>";
                        echo "<label>Mobile only</label>";
                    echo "</td>";

                    $checked	= '';
                    if($this->settings['visibilty-logout-menu'][$menu->term_id] == 'desktop'){
                        $checked	= 'checked';
                    }
                    echo "<td>";
                        echo "<input type='radio' id='$menu->term_id' name='visibilty-logout-menu[$menu->term_id]' value='desktop' $checked>";
                        echo "<label>Desktop only </label>";
                    echo "</td>";
                echo "</tr>";
            }

        echo "</table>";

        addRawHtml(ob_get_clean(), $parent);
        
        return true;
    }

    public function emails($parent){
        $tab      = 'twofa-email';
        if(isset($_GET['second-tab'])){
            $tab  = sanitize_key( wp_unslash( $_GET['second-tab']));
        }

        $tablinkWrapper = addElement('div', $parent, ['class' => 'tablink-wrapper']);

        $buttons    = [
            'twofa-email'               => 'Login Code',
            'unsafe-login-email'        => 'Unsafe Login',
            'twofa-reset-email'         => '2FA Reset',
            'twofa-confirmation-email'  => '2FA Confirmation',
            'pwd-reset-email'           => 'Password Reset',
        ];

        foreach($buttons as $id => $text){
            $attributes = [
                'class'       => 'tablink' . ($tab == $id ? ' active' : ''),
                'id'          => "show-$id",
                'data-target' => $id,
                'type'        => 'button'
            ];
            addElement('button', $tablinkWrapper, $attributes, $text);
        }

        ob_start();
        ?>
        <div id="twofa-email" class="tabcontent <?php echo $tab != 'twofa-email' ? 'hidden' : '';?>">

            <h4>E-mail with the two factor login code</h4>
            <label>Define the e-mail people get when they requested a code for login.</label>
            <?php
            $twoFAEmail    = new TwoFaEmail(wp_get_current_user());
            $twoFAEmail->printPlaceholders();
            $twoFAEmail->printInputs();
            ?>
        </div>

        <div id="unsafe-login-email" class="tabcontent <?php echo $tab != 'unsafe-login-email' ? 'hidden' : '';?>">
            <h4>Warning e-mail for unsafe login</h4>
            <label>Define the e-mail people get when they login to the website and they have not configured two factor authentication.</label>
            <?php
            $unsafeLogin    = new UnsafeLogin(wp_get_current_user());
            $unsafeLogin->printPlaceholders();
            $unsafeLogin->printInputs();
            ?>
        </div>

        <div id="twofa-reset-email" class="tabcontent <?php echo $tab != '2fa-reset-email' ? 'hidden' : '';?>">

            <h4>Two factor login reset e-mail</h4>
            <label>Define the e-mail people get when their two factor login got reset by a user manager.</label>
            <?php
            $twoFaReset    = new TwoFaReset(wp_get_current_user());
            $twoFaReset->printPlaceholders();
            $twoFaReset->printInputs();
            ?>
        </div>

        <div id="twofa-confirmation-email" class="tabcontent <?php echo $tab != '2fa-confirmation-email' ? 'hidden' : '';?>">
            <h4>Two factor login confirmation e-mail</h4>
            <label>Define the e-mail people get when they have just enabled email verification.</label>
            <?php
            $emailVerfEnabled    = new EmailVerfEnabled(wp_get_current_user());
            $emailVerfEnabled->printPlaceholders();
            $emailVerfEnabled->printInputs();
            ?>
        </div>

        <div id="pwd-reset-email" class="tabcontent <?php echo $tab != 'pwd-reset-email' ? 'hidden' : '';?>">
            <h4>E-mail to people who requested a password reset</h4>
            <label>Define the e-mail people get when they requested a password reset</label>
            <?php
            $passwordResetMail    = new PasswordResetMail(wp_get_current_user());
            $passwordResetMail->printPlaceholders();
            $passwordResetMail->printInputs();
            ?>
        </div>
        <?php

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function data($parent=''){

        return false;
    }

    public function functions($parent){

        return false;
    }

    /**
     * Adds a login/logout block to all nvigation blocks
     */
    public function postSettingsSave(){
        if(isset($_POST['addLoginLogout'])){
            $posts = get_posts(
				array(
					'numberposts'	=> -1,
					'post_type'      => 'wp_navigation',
					'post_status'    => array( 'publish', 'inherit' ),
				)
			);

            foreach($posts as $post){
                if(!str_contains($post->post_content, 'wp:loginout')){
                    $post->post_content .= "<!-- wp:loginout /-->";

                    wp_update_post( ['ID' => $post->ID, 'post_content' => $post->post_content] );
                }
            }

            return "Added login/logout button to the menu";
        }
    }
}