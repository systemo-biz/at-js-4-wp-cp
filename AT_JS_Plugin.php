<?php
/*
Plugin Name: At.js in WP by Sistemo
Description: At.js for WordPress comments
Version: 20141128
GitHub Plugin URI: https://github.com/systemo-biz/at-js-4-wp-cp
GitHub Branch: master
Author URI: http://casepress.org
*/

/*
Подключаем компоненты
*/
//Сохранение выбранного пользователя в мету коммента
require_once('includes/user_to_meta.php');

add_action('wp_ajax_at_js_user_select', 'at_js_user_select_cp');
add_action('wp_ajax_nopriv_at_js_user_select', 'at_js_user_select_cp');
function at_js_user_select_cp()
{
    // Производится AJAX запрос параметров
    // проверяем nonce код, если проверка не пройдена прерываем обработку
    check_ajax_referer( 'at_js_nonce', '_nonce' ); 
    //if ( !wp_verify_nonce($_REQUEST['nonce'], 'AT_JS_Plugin-nonce') )
      //  die ( 'Stop!');

    $subscribers = get_users(); // Массив с логинами зарегистрированных пользователей

    $AT_JS_users=array();
	$AT_JS_users_names=array();
	
    foreach ($subscribers as $subscriber)
    {
        $AT_JS_users[]=$subscriber -> user_login;
		$AT_JS_users_names[]=$subscriber -> display_name;
    }
	
    $AT_JS_Selector = '#comment';

    // Формируем JSON строку с требуемыми данными
	echo('{"WP_Users":' .json_encode($AT_JS_users). ',');
	echo('"WP_Users_Names":' .json_encode($AT_JS_users_names). ',');
    echo('"Selector":' .json_encode($AT_JS_Selector). '}'); 

    // Прекращаем выполнение скрипта (не требуется дальнейшее формирование html страницы)
    exit;
}




// Регистрируем нужные нам js скрипты и CSS стили
add_filter('wp_enqueue_scripts', 'AT_JS_Plugin_RegisterScripts');
function AT_JS_Plugin_RegisterScripts()
{
    wp_localize_script( 'jquery', 'myajax',
        array(
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('AT_JS_Plugin-nonce')
        ));

    $SCRIPT_DEBUG = false;

    $min = $SCRIPT_DEBUG ? '' : '.min';
    // URL адрес для нашего плагина
    $plugin_dir = trailingslashit(plugins_url('',__FILE__));


    // register scripts
    $scripts = array();
    $scripts[] = array(
        'handle'	=> 'caret',
        'src'		=> $plugin_dir . "js/jquery.caret{$min}.js",
        'deps'		=> array('jquery')
    );
    $scripts[] = array(
        'handle'	=> 'atwho',
        'src'		=> $plugin_dir . "js/jquery.atwho{$min}.js",
        'deps'		=> array('jquery', 'caret')
    );

    foreach( $scripts as $script )
    {
        wp_register_script( $script['handle'], $script['src'], $script['deps'], false ,true);
    }
    // Подключаем js скрипты
    wp_enqueue_script('atwho');

    // register styles
    $styles = array(
        'atwho'		=> $plugin_dir . "css/jquery.atwho{$min}.css",
    );

    foreach( $styles as $k => $v )
    {
        wp_register_style( $k, $v, false);
    }

    // Подключаем CSS стили
    wp_enqueue_style('atwho');
}

// Подключаем нужные нам скрипты  в футере (для ускорения загрузки страницы)
add_filter('wp_footer', 'AT_JS_Plugin_EnqueueScripts', 100);
function AT_JS_Plugin_EnqueueScripts() {
    $ajax_nonce = wp_create_nonce("at_js_nonce");
?>
    <script type="text/javascript">
        jQuery( document ).ready(function( $ ) {
	
            $.ajax({
                url         : "<?php echo admin_url('admin-ajax.php') . '?action=at_js_user_select&_nonce=' . $ajax_nonce ?>",
                type        : "POST",
                dataType    : 'json',
                //data        : "",
                success     : function(data){
					var real_names = data.WP_Users_Names;
					
					var names = data.WP_Users;
					var names = $.map(names,function(value,i) {
					  return {'id':i,'name':value, 'name_name':real_names[i]+' - '+value};
					});

                    $(data.Selector).atwho({
                        at: '@',
						tpl: "<li data-value='@${name}'>${name_name}</li>",
						search_key: 'name_name',
						data: names
                    });
                }
            });

        });
    </script>
<?php

}
