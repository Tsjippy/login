<?php
namespace SIM\LOGIN;
use SIM;
use function SIM\ADMIN\getDefaultPageLink;

const MODULE_VERSION		= '8.4.3';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

require( MODULE_PATH  . 'lib/vendor/autoload.php');

add_filter('sim_submenu_login_description', __NAMESPACE__.'\moduleDescription', 10, 2);
function moduleDescription($description, $moduleSlug){
	ob_start();
	$menus			= wp_get_nav_menus();
	$menuLocations	= get_nav_menu_locations();
	$menuActivated	= false;

	// loop over all menus
	foreach ( (array) $menus as $menu ) {
		// check if this menu has a location
		if ( array_search( $menu->term_id, $menuLocations ) ) {
			$menuActivated	= true;
		}
	}

	if(!$menuActivated){
		echo "<div class='error'>";
			echo "<br>You have no active menu, please activate one to have login and logout menu items<br><br>";
		echo "</div>";
	}

	$links		= [];
	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'password-reset-page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Change password</a><br>";
	}

	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'register-page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Request user account</a><br>";
	}

	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, '2fa-page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Two Factor Authentication</a><br>";
	}

	if(!empty($links)){
		?>
		<p>
			<strong>Auto created pages:</strong><br>
			<?php
			foreach($links as $link){
				echo $link;
			}
			?>
		</p>
		<?php
	}

	return $description.ob_get_clean();
}

add_filter('sim_submenu_login_options', __NAMESPACE__.'\moduleOptions', 10, 2);
function moduleOptions($optionsHtml, $settings){
	ob_start();
	?>
	<p>
		You can enable user registration if you want.<br>
		If that's the case people can request an user account.<br>
		Once that account is approved they will be able to login.<br>
	</p>
	<label>
		<input type="checkbox" name="user-registration" value="enabled" <?php if($settings['user-registration']){echo 'checked';}?>>
		Enable user registration
	</label>
	<?php 
	if($settings['user-registration']){
		$url	= getDefaultPageLink(MODULE_SLUG, 'register-page');
		?>
		<a href="<?php echo $url;?>" target="_blank">View registration page</a>
		<?php
	}
	?>
	<br>
	<br>
	Where should the login menu item be added?
	<br>

	<table style="border: none;">
		<?php
		$menus	= wp_get_nav_menus();

		if(!isset($settings['visibilty-login-menu'])){
			$settings['visibilty-login-menu']	= [];
		}

		if(!isset($settings['visibilty-logout-menu'])){
			$settings['visibilty-logout-menu']	= [];
		}

		foreach($menus as $menu){
			$checked	= '';
			if(isset($settings['loginmenu']) && in_array($menu->term_id, $settings['loginmenu'])){
				$checked	= 'checked';
			}

			if(!isset($settings['visibilty-login-menu'][$menu->term_id])){
				$settings['visibilty-login-menu'][$menu->term_id]	= '';
			}
			echo "<tr>";
				echo "<td>";
					echo "<label>";
						echo "<input type='checkbox' name='loginmenu[]' value='$menu->term_id' $checked>";
						echo "$menu->name";
					echo "</label>";
				echo "</td>";

				$checked	= '';
				if($settings['visibilty-login-menu'][$menu->term_id] == ''){
					$checked	= 'checked';
				}
				echo "<td>";
					echo "<input type='radio' id='$menu->term_id' name='visibilty-login-menu[$menu->term_id]' value='' $checked>";
					echo "<label>Always</label>";
				echo "</td>";

				$checked	= '';
				if($settings['visibilty-login-menu'][$menu->term_id] == 'mobile'){
					$checked	= 'checked';
				}
				echo "<td>";
					echo "<input type='radio' id='$menu->term_id' name='visibilty-login-menu[$menu->term_id]' value='mobile' $checked>";
					echo "<label>Mobile only</label>";
				echo "</td>";

				$checked	= '';
				if($settings['visibilty-login-menu'][$menu->term_id] == 'desktop'){
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
			if(isset($settings['logoutmenu']) && in_array($menu->term_id, $settings['logoutmenu'])){
				$checked	= 'checked';
			}
			echo "<tr>";
				echo "<td>";
					echo "<label>";
						echo "<input type='checkbox' name='logoutmenu[]' value='$menu->term_id' $checked>";
						echo "$menu->name";
					echo "</label>";
				echo "</td>";

				$checked	= '';
				if($settings['visibilty-logout-menu'][$menu->term_id] == ''){
					$checked	= 'checked';
				}
				echo "<td>";
					echo "<input type='radio' id='$menu->term_id' name='visibilty-logout-menu[$menu->term_id]' value='' $checked>";
					echo "<label>Always</label>";
				echo "</td>";

				$checked	= '';
				if($settings['visibilty-logout-menu'][$menu->term_id] == 'mobile'){
					$checked	= 'checked';
				}
				echo "<td>";
					echo "<input type='radio' id='$menu->term_id' name='visibilty-logout-menu[$menu->term_id]' value='mobile' $checked>";
					echo "<label>Mobile only</label>";
				echo "</td>";

				$checked	= '';
				if($settings['visibilty-logout-menu'][$menu->term_id] == 'desktop'){
					$checked	= 'checked';
				}
				echo "<td>";
					echo "<input type='radio' id='$menu->term_id' name='visibilty-logout-menu[$menu->term_id]' value='desktop' $checked>";
					echo "<label>Desktop only </label>";
				echo "</td>";
			echo "</tr>";
		}

	echo "</table>";

	return $optionsHtml.ob_get_clean();
}

add_filter('sim_email_login_settings', __NAMESPACE__.'\emailSettings', 10, 2);
function emailSettings($html, $settings){
	ob_start();
	?>
	<h4>E-mail with the two factor login code</h4>
	<label>Define the e-mail people get when they requested a code for login.</label>
	<?php
	$twoFAEmail    = new TwoFaEmail(wp_get_current_user());
	$twoFAEmail->printPlaceholders();
	$twoFAEmail->printInputs($settings);

	?>
	<br>
	<br>

	<h4>Warning e-mail for unsafe login</h4>
	<label>Define the e-mail people get when they login to the website and they have not configured two factor authentication.</label>
	<?php
	$unsafeLogin    = new UnsafeLogin(wp_get_current_user());
	$unsafeLogin->printPlaceholders();
	$unsafeLogin->printInputs($settings);

	?>
	<br>
	<br>

	<h4>Two factor login reset e-mail</h4>
	<label>Define the e-mail people get when their two factor login got reset by a user manager.</label>
	<?php
	$twoFaReset    = new TwoFaReset(wp_get_current_user());
	$twoFaReset->printPlaceholders();
	$twoFaReset->printInputs($settings);

	?>
	<br>
	<br>
	<h4>Two factor login confirmation e-mail</h4>
	<label>Define the e-mail people get when they have just enabled email verification.</label>
	<?php
	$emailVerfEnabled    = new EmailVerfEnabled(wp_get_current_user());
	$emailVerfEnabled->printPlaceholders();
	$emailVerfEnabled->printInputs($settings);

	?>
	<h4>E-mail to people who requested a password reset</h4>
	<label>Define the e-mail people get when they requested a password reset</label>
	<?php
	$passwordResetMail    = new PasswordResetMail(wp_get_current_user());
	$passwordResetMail->printPlaceholders();
	$passwordResetMail->printInputs($settings);
	?>
	<br>
	<br>
	<?php
	return $html.ob_get_clean();
}

add_filter('sim_module_login_after_save', __NAMESPACE__.'\moduleUpdated', 10, 2);
function moduleUpdated($newOptions, $oldOptions){
	$publicCat	= get_cat_ID('Public');

	// Create password reset page
	$newOptions	= SIM\ADMIN\createDefaultPage($newOptions, 'password-reset-page', 'Change password', '[change_password]', $oldOptions, ['post_category' => [$publicCat]]);

	// Add registration page
	if(isset($newOptions['user-registration'])){
		$newOptions	= SIM\ADMIN\createDefaultPage($newOptions, 'register-page', 'Request user account', '[request_account]', $oldOptions, ['post_category' => [$publicCat]]);
	}

	// Add 2fa page
	$newOptions	= SIM\ADMIN\createDefaultPage($newOptions, '2fa-page', 'Two Factor Authentication', '[twofa_setup]', $oldOptions);

	// Remove registration page
	if(isset($oldOptions['register-page']) && !isset($newOptions['user-registration'])){
		foreach($oldOptions['register-page'] as $page){
			// Remove the auto created page
			wp_delete_post($page, true);
		}
		unset($newOptions['register-page']);
	}

	return $newOptions;

}

add_filter('display_post_states', __NAMESPACE__.'\postStates', 10, 2);
function postStates( $states, $post ) {

    if(in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'password-reset-page', false)) ) {
        $states[] = __('Password reset page');
    }elseif(in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'register-page', false))) {
        $states[] = __('User register page');
    }elseif(in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, '2fa-page', false)) ) {
        $states[] = __('Two Factor Setup page');
    }

    return $states;
}

add_action('sim_module_login_deactivated', __NAMESPACE__.'\moduleDeActivated');
function moduleDeActivated($options){
	$removePages	= [];

	if(is_array($options['password-reset-page'])){
		$removePages	= array_merge($removePages, $options['password-reset-page']);
	}

	if(is_array($options['register-page'])){
		$removePages	= array_merge($removePages, $options['register-page']);
	}

	if(is_array($options['2fa-page'])){
		$removePages	= array_merge($removePages, $options['2fa-page']);
	}

	// Remove the auto created pages
	foreach($removePages as $page){
		// Remove the auto created page
		wp_delete_post($page, true);
	}
}