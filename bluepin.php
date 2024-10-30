<?php
/*
  Plugin Name: Bluepin
  Plugin URI: https://app.getbluepin.com/
  Description: Integrate Bluepin into your WordPress site
  Version: 1.0.1
  Author: Bluepin
  Author URI: https://profiles.wordpress.org/getbluepin/
  Requires at least: 6.0
  Tested up to: 6.1.1
  Requires PHP: 8.0
  License: GPLv2
  License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/*
Bluepin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Bluepin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Bluepin. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.html.
*/

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

// Get the menu HTML
if (!function_exists('bluepin_options_page_html')) {
  function bluepin_options_page_html() {
    global $wpdb;
    $bluepin_query_result = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bluepin_data WHERE id=0');
    $bluepin_app_url = 'https://app.getbluepin.com';
    
    if (!empty($_GET['bluepin-apikey'])) {
      $bluepin_table_name = $wpdb->prefix . 'bluepin_data';
      $bluepin_apikey = sanitize_text_field($_GET['bluepin-apikey']);

      if (is_string($bluepin_apikey)) {
        // Check if the entry exists in the DB. If it does, update it and skip adding a new one
        if ($bluepin_query_result) {
          $wpdb->update(
            $bluepin_table_name, 
            array(
              'apikey' => $bluepin_apikey
            ),
            array(
              'id' => 0
            )
          );
        } else {
          $wpdb->insert(
            $bluepin_table_name, 
            array(
              'apikey' => $bluepin_apikey
            ), 
            array(
              '%s',
              '%s'
            )
          );
        }
      }
    } ?>
      <style>
        #wpcontent { padding-left: 0; }

        .bp-image { margin-bottom: 25px; }
        .bp-wrapper { min-height: 85vh; padding: 20px 0; text-align: center; background: #e6f0ff; }

        .bp-actions a { display: block; max-width: 280px; height: 60px; padding: 0 15px; margin: 0 auto 10px; vertical-align: middle; border-radius: 6px; font-family: Arial, "Helvetica Neue", Helvetica, sans-serif; font-size: 20px; line-height: 60px; font-weight: 600; text-align: center; cursor: pointer; color: #fff; text-decoration: none; background: #428cff; transition: background .3s; box-sizing: border-box; }
        .bp-actions a:hover { background: #2e62b2; }
        
        .bp-image { display: block; margin: 0 auto 20px; }
        .bp-image img { display: block; margin: 0 auto; }

        .bp-actions .bp-borders { border: 1px solid #428cff; color: #428cff; line-height: 58px; background: transparent; transition: border-color .3s, color .3s, background .3s; }
        .bp-actions .bp-borders:hover { border-color: #2e62b2; color: #fff; }
      </style>

      <div class="bp-wrapper">
        <div class="bp-image">
          <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'bluepin-icon-showcase.png'); ?>" width="128" height="128" />
        </div>

        <div class="bp-actions">
          <?php
            $bluepin_query_result = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bluepin_data WHERE id=0');
            $bluepin_connect_url = sanitize_url($bluepin_app_url . '?wpembed=1&wp_url=' . admin_url('admin.php?page=bluepin'));

            if ($bluepin_query_result) :
          ?>
            <a href="<?php echo esc_url($bluepin_app_url);?>">Go to Bluepin settings</a>

            <a href="<?php echo esc_url($bluepin_connect_url);?>" class='bp-borders'>Reconfigure</a>
          <?php else : ?>
            <a href="<?php echo esc_url($bluepin_connect_url);?>">Connect with Bluepin</a>
          <?php endif; ?>
        </div>
      </div>
    <?php
  }
}

// Register the WP menu with options
if (!function_exists('bluepin_options_page')) {
  function bluepin_options_page() {
    add_menu_page(
      'Bluepin',
      'Bluepin',
      'manage_options',
      'bluepin',
      'bluepin_options_page_html',
      plugin_dir_url(__FILE__) . 'bluepin.png',
      100
    );
  }
  add_action('admin_menu', 'bluepin_options_page');
}

// Enqueue the bluepin embed script if it's enabled
if (!function_exists('bluepin_embed_script')) {
  function bluepin_embed_script() {
    global $wpdb;
  
    $bluepin_query_result = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bluepin_data WHERE id=0');
  
    if ($bluepin_query_result) {
      wp_enqueue_script( 'bluepin_script', 'https://app.getbluepin.com/embed?wpembed=1&apiKey=' . $bluepin_query_result[0]->apikey, array(), null, true );
    }
  }
  add_action('wp_enqueue_scripts', 'bluepin_embed_script');
}

// Database table creation
if (!function_exists('bluepin_database_create')) {
  function bluepin_database_create() {
    global $wpdb;
  
    $bluepin_table_name = $wpdb->prefix . 'bluepin_data';
    $charset_collate = $wpdb->get_charset_collate();
  
    $sql = "CREATE TABLE $bluepin_table_name (
      id mediumint(9) NOT NULL,
      apikey text NOT NULL,
      PRIMARY KEY (id)
    ) $charset_collate;";
  
    dbDelta($sql);
  }
  register_activation_hook(__FILE__, 'bluepin_database_create');
}
