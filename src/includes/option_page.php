<?php
function Aveonline_create_menu() {
	add_menu_page('Aveonline Settings', 'Aveonline', 'administrator', __FILE__, 'Aveonline_settings_page' , plugins_url('../img/aveonline.png', __FILE__) );
	add_action( 'admin_init', 'register_Aveonline_settings' );
}
add_action('admin_menu', 'Aveonline_create_menu');

function register_Aveonline_settings() {
	//register our settings
	register_setting( 'Aveonline-settings-group', 'new_option_name' );
	register_setting( 'Aveonline-settings-group', 'some_other_option' );
	register_setting( 'Aveonline-settings-group', 'option_etc' );
}

function Aveonline_settings_page(){
    ?>
    <h1>
        Aveonline
    </h1>
    <?php
}

