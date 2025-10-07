<?php
namespace SIM\LOGIN;
use SIM;

//add login and logout buttons to menu's
add_filter('wp_nav_menu_items', __NAMESPACE__.'\menuItems', 10, 2);
function menuItems($items, $args) {
    $loginMenus     = SIM\getModuleOption(MODULE_SLUG, 'loginmenu', false);
    $logoutMenus    = SIM\getModuleOption(MODULE_SLUG, 'logoutmenu', false);

    if(
        !in_array($args->menu->term_id, $loginMenus)   &&  // Do not add when not in the list
        !in_array($args->menu->term_id, $logoutMenus)  &&
        !empty(SIM\getModuleOption(MODULE_SLUG, 'menu', false))
    ){
        return $items;
    }

    // We should add a logout menu item
    if(is_user_logged_in() && in_array($args->menu->term_id, $logoutMenus)){
        $class  = '';
        if($args->menu->slug != 'footer'){
            $class  = 'button';
        }

        $visibilities   = SIM\getModuleOption(MODULE_SLUG, 'visibilty-logout-menu', false);

        if(in_array($args->menu->term_id, array_keys($visibilities))){
            if($visibilities[$args->menu->term_id] == 'mobile'){
                $class  .= " hide-on-desktop";
            }

            if($visibilities[$args->menu->term_id] == 'desktop'){
                $class  .= " hide-on-mobile";
            }
        }
        
        $items .= "<li class='menu-item logout hidden'><a href='#logout' class='logout $class'>Log out</a></li>";
    }

    // We should add a login menu item
    if(
        !is_user_logged_in() &&                     // we are not logged in
        in_array($args->menu->term_id, $loginMenus) // we should add it to the current menu
    ){
        $shouldAdd  = apply_filters('sim_add_login_button', true, $args->menu->term_id, $loginMenus);

        if(!$shouldAdd){
            return $items;
        }

        $class   = '';
        if($args->menu->slug != 'footer'){
            $class  = 'button';
        }

        $visibilities   = SIM\getModuleOption(MODULE_SLUG, 'visibilty-login-menu', false);

        if(in_array($args->menu->term_id, array_keys($visibilities))){
            if($visibilities[$args->menu->term_id] == 'mobile'){
                $class  .= " hide-on-desktop";
            }

            if($visibilities[$args->menu->term_id] == 'desktop'){
                $class  .= " hide-on-mobile";
            }
        }

        $menuItem   = apply_filters('sim-login-menu-item', "<a href='#login' class='login $class'>Log in</a>");
        $items     .=  "<li class='menu-item login hidden'>$menuItem</li>";
    }
  return $items;
}