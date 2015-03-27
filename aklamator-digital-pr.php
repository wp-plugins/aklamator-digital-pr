<?php
/*
Plugin Name: Aklamator - Digital PR
Plugin URI: http://www.aklamator.com/wordpress
Description: Aklamator digital PR service enables you to sell PR announcements, cross promote web sites using RSS feed and provide new services to your clients in digital advertising.
Version: 1.1.3
Author: Aklamator
Author URI: http://www.aklamator.com/
License: GPL2

Copyright 2015 Aklamator.com (email : info@aklamator.com)

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

/*
 * Add setting link on plugin page
 */

if( !function_exists("aklamator_plugin_settings_link")){
    // Add settings link on plugin page
    function aklamator_plugin_settings_link($links) {
        $settings_link = '<a href="admin.php?page=aklamator-digital-pr">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
add_filter("plugin_action_links_".plugin_basename(__FILE__), 'aklamator_plugin_settings_link' );

/*
 * Activation Hook
 */

register_activation_hook( __FILE__, 'set_up_options' );

function set_up_options(){
    add_option('aklamatorApplicationID', '');
    add_option('aklamatorPoweredBy', '');
    add_option('aklamatorSingleWidgetID', '');
    add_option('aklamatorPageWidgetID', '');
    add_option('aklamatorSingleWidgetTitle', '');
}

/*
 * Uninstall Hook
 */
register_uninstall_hook(__FILE__, 'aklamator_uninstall');

function aklamator_uninstall()
{

    if (get_option('aklamatorApplicationID')) {
        delete_option('aklamatorApplicationID');
    }

    if (get_option('aklamatorPoweredBy')) {
        delete_option('aklamatorPoweredBy');
    }

    if(get_options('aklamatorSingleWidgetID')){
        delete_options('aklamatorSingleWidgetID');
    }

    if(get_options('aklamatorPageWidgetID')){
        delete_options('aklamatorPageWidgetID');
    }

    if(get_options('aklamatorSingleWidgetTitle')){
        delete_options('aklamatorSingleWidgetTitle');
    }


}


if( !function_exists("bottom_of_every_post")){
    function bottom_of_every_post($content){

        /*  we want to change `the_content` of posts, not pages
            and the text file must exist for this to work */

        if (is_single()){
            $widget_id = get_option('aklamatorSingleWidgetID');
        }elseif (is_page()) {
            $widget_id = get_option('aklamatorPageWidgetID');
        }else{

            /*  if `the_content` belongs to a page or our file is missing
                the result of this filter is no change to `the_content` */

            return $content;
        }

        $title = "";
            if(get_option('aklamatorSingleWidgetTitle') !== ''){
                $title .= "<h2>". get_option('aklamatorSingleWidgetTitle'). "</h2>";
            }

            /*  append the text file contents to the end of `the_content` */
            return $content . $title ."<!-- created 2014-11-25 16:22:10 -->
            <div id=\"akla$widget_id\"></div>
            <script>(function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = \"http://aklamator.com/widget/$widget_id\";
            fjs.parentNode.insertBefore(js, fjs);
         }(document, 'script', 'aklamator-$widget_id'));</script>
        <!-- end -->" . "<br>";  
    }

}

class AklamatorWidget
{

    public $aklamator_url;
    public $api_data;

    public function __construct()
    {

        $this->aklamator_url = "http://aklamator.com/";
        
        if (is_admin()) {
            add_action("admin_menu", array(
                &$this,
                "adminMenu"
            ));

            add_action('admin_init', array(
                &$this,
                "setOptions"
            ));

            if (get_option('aklamatorApplicationID') !== '') {
                $this->api_data =  $this->addNewWebsiteApi();
            }
        }
        if (get_option('aklamatorSingleWidgetID') !== 'none') {

            if (get_option('aklamatorSingleWidgetID') == '') {
                if ($this->api_data->data[0]) {
                    update_option('aklamatorSingleWidgetID', $this->api_data->data[0]->uniq_name);
                }

            }
            add_filter('the_content', 'bottom_of_every_post');
        }

        if (get_option('aklamatorPageWidgetID') !== 'none') {

            if (get_option('aklamatorPageWidgetID') == '') {
                if ($this->api_data->data[0]) {
                    update_option('aklamatorPageWidgetID', $this->api_data->data[0]->uniq_name);
                }

            }
            add_filter('the_content', 'bottom_of_every_post');
        }



    }

    function setOptions()
    {
        register_setting('aklamator-options', 'aklamatorApplicationID');
        register_setting('aklamator-options', 'aklamatorPoweredBy');
        register_setting('aklamator-options', 'aklamatorSingleWidgetID');
        register_setting('aklamator-options', 'aklamatorPageWidgetID');
        register_setting('aklamator-options', 'aklamatorSingleWidgetTitle');

    }

    public function adminMenu()
    {
        add_menu_page('Aklamator Digital PR', 'Aklamator PR', 'manage_options', 'aklamator-digital-pr', array(
            $this,
            'createAdminPage'
        ), content_url() . '/plugins/aklamator-digital-pr/images/aklamator-icon.png');

    }
            
            
            
    public function getSignupUrl()
    {
        
        return $this->aklamator_url . 'registration/publisher?utm_source=wordpress&utm_medium=admin&e=' . urlencode(get_option('admin_email')) . '&pub=' .  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']).
        '&un=' . urlencode(wp_get_current_user()->display_name);

    }

    private function addNewWebsiteApi()
    {

        if (!is_callable('curl_init')) {
            return;
        }


        $service     = $this->aklamator_url . "wp-authenticate/user";
        $p['ip']     = $_SERVER['REMOTE_ADDR'];
        $p['url']    = site_url();
        $p['source'] = "wordpress";
        $p['AklamatorApplicationID'] = get_option('aklamatorApplicationID');


        $client = curl_init();

        curl_setopt($client, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($client, CURLOPT_HEADER, 0);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_URL, $service);

        if (!empty($p)) {
            curl_setopt($client, CURLOPT_POST, count($p));
            curl_setopt($client, CURLOPT_POSTFIELDS, http_build_query($p));
        }

        $data = curl_exec($client);
        curl_close($client);

        $data = json_decode($data);

        return $data;

    }

    public function createAdminPage()
    {
        $code = get_option('aklamatorApplicationID');
        $ak_home_url = 'http://aklamator.com';
        $ak_dashboard_url = 'http://aklamator.com/dashboard';

        ?>
        <style>
            #adminmenuback{ z-index: 0}
            #aklamator-options ul { margin-left: 10px; }
            #aklamator-options ul li { margin-left: 15px; list-style-type: disc;}
            #aklamator-options h1 {margin-top: 5px; margin-bottom:10px; color: #00557f}
            .fz-span { margin-left: 23px;}


            .aklamator-signup-button {
                float: left;
                vertical-align: top;
                width: auto;
                height: 30px;
                line-height: 30px;
                padding: 10px;
                font-size: 22px;
                color: white;
                text-align: center;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
                background: #c0392b;
                border-radius: 5px;
                border-bottom: 2px solid #b53224;
                cursor: pointer;
                -webkit-box-shadow: inset 0 -2px #b53224;
                box-shadow: inset 0 -2px #b53224;
                text-decoration: none;
                margin-top: 3px;
                margin-bottom: 10px;
                /*clear: both;*/
            }

            a.aklamator-signup-button:hover {
                cursor: pointer;
                color: #f8f8f8;
            }

            .btn { border: 1px solid #fff; font-size: 13px; border-radius: 3px; background: transparent; text-transform: uppercase; font-weight: 700; padding: 4px 10px; min-width: 162px; max-width: 100%; text-decoration: none;}
            .btn:Hover, .btn.hovered { border: 1px solid #fff; }
            .btn:Active, .btn.pressed { opacity: 1; border: 1px solid #fff; border-top: 3px solid #17ade0; -webkit-box-shadow: 0 0 0 transparent; box-shadow: 0 0 0 transparent; }
            
            .btn-primary { background: #1ac6ff; border:1px solid #1ac6ff; color: #fff; text-decoration: none;}
            .btn-primary:hover, .btn-primary.hovered { background: #1ac6ff;  border:1px solid #1ac6ff; opacity:0.9; }
            .btn-primary:Active, .btn-primary.pressed { background: #1ac6ff; border:1px solid #1ac6ff; }

            .box{float: left; margin-left: 10px; width: 500px; background-color:#f8f8f8; padding: 10px; border-radius: 5px;}

        </style>
        <!-- Load css libraries -->

        <link href="//cdn.datatables.net/1.10.5/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">

        <div id="aklamator-options" style="width:880px;margin-top:10px;">

            <div style="float: left; width: 300px;">
                    
                <a target="_blank" href="<?php echo $ak_home_url; ?>?utm_source=wp-plugin">
                    <img style="border-radius:5px;border:0px;" src=" <?php echo plugins_url('images/logo.jpg', __FILE__);?>" /></a>
                <?php
                if ($code != '') : ?>
                    <a target="_blank" href="<?php echo $ak_dashboard_url; ?>?utm_source=wp-plugin">
                        <img style="border:0px;margin-top:5px;border-radius:5px;" src="<?php echo plugins_url('images/dashboard.jpg', __FILE__); ?>" /></a>

                <?php endif; ?>

                <a target="_blank" href="<?php echo $ak_home_url;?>/contact?utm_source=wp-plugin-contact">
                    <img style="border:0px;margin-top:5px; margin-bottom:5px;border-radius:5px;" src="<?php echo plugins_url('images/support.jpg', __FILE__); ?>" /></a>



            </div>
            <div class="box">

                <h1>Aklamator Digital PR</h1>

                <?php

                if ($code == '') : ?>
                    <h3 style="float: left">Step 1:</h3>
                    <a style="float: right" class='aklamator-signup-button' target='_blank' href="<?php echo $this->getSignupUrl(); ?>">Click here to create your FREE account!</a>

                <?php endif; ?>



                <div style="clear: both"></div>
                <?php if ($code == '') { ?>
                    <h3>Step 2: &nbsp;&nbsp;&nbsp;&nbsp; Paste your Aklamator Application ID</h3>
                    <?php }else{ ?>
                    <h3>Your Aklamator Application ID</h3>
                <?php } ?>


                <form method="post" action="options.php">
                    <?php
                    settings_fields('aklamator-options');
                    ?>

                    <p >
                        <input type="text" style="width: 400px" name="aklamatorApplicationID" id="aklamatorApplicationID" value="<?php
                        echo (get_option("aklamatorApplicationID"));
                        ?>" maxlength="999" />

                    </p>
                    <p>
                        <input type="checkbox" id="aklamatorPoweredBy" name="aklamatorPoweredBy" <?php echo (get_option("aklamatorPoweredBy") == true ? 'checked="checked"' : ''); ?> Required="Required">
                        <strong>Required</strong> I acknowledge there is a 'powered by aklamator' link on the widget. <br />
                    </p>

           
            <?php if(get_option('aklamatorApplicationID') !=='' && $this->api_data->flag): ?>
           
                    <p> 
                    <h1>Options</h1>
                    <h4>Select widget to be shown on bottom of the each:</h4>

                    <label for="aklamatorSingleWidgetTitle">Title Above widget (Optional): </label>    
                    <input type="text" style="width: 300px; margin-bottom:10px" name="aklamatorSingleWidgetTitle" id="aklamatorSingleWidgetTitle" value="<?php echo (get_option("aklamatorSingleWidgetTitle")); ?>" maxlength="999" />

                    <?php 

                        $widgets = $this->api_data->data;

                        /* Add new item to the end of array */
                        $item_add = new stdClass();
                        $item_add->uniq_name = 'none';
                        $item_add->title = 'Do not show';
                        $widgets[] = $item_add;

                    ?>   
                    
                    <label for="aklamatorSingleWidgetID">Single post: </label>
                    <select id="aklamatorSingleWidgetID" name="aklamatorSingleWidgetID">
                        <?php    
                        foreach ( $widgets as $item ): ?>
                            <option <?php echo (get_option('aklamatorSingleWidgetID') == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                        <?php endforeach; ?>
                        
                    </select>
                    </p>

                    <p>
                        <label for="aklamatorPageWidgetID">Single page: </label>
                        <select id="aklamatorPageWidgetID" name="aklamatorPageWidgetID">
                            <?php
                            foreach ( $widgets as $item ): ?>
                                <option <?php echo (get_option('aklamatorPageWidgetID') == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                            <?php endforeach; ?>
                            
                        </select>
                    </p>

                
            <?php endif; ?>
             <input style ="margin-bottom:15px;" type="submit" value="<?php echo (_e("Save Changes")); ?>" />


            </form>
            </div>

        </div>

        <div style="clear:both"></div>
        <div style="margin-top: 20px; margin-left: 0px; width: 810px;" class="box">

            <!-- Start of dataTables -->
            <div id="aklamator-options">
                <h1>Your Widgets</h1>
            </div>
            <br>

        <?php if (get_option('aklamatorApplicationID') == ""): ?>
            <a href="<?php echo $this->getSignupUrl(); ?>" target="_blank"><img style="border-radius:5px;border:0px;" src=" <?php echo plugins_url('images/teaser-810x262.png', __FILE__);?>" /></a>
        <?php else: ?>

        <?php if(!empty($this->api_data)) : ?>

        <?php if($this->api_data->flag ): ?>



            <table cellpadding="0" cellspacing="0" border="0"
                   class="responsive dynamicTable display table table-bordered" width="100%">
                <thead>
                <tr>

                    <th>Name</th>
                    <th>Domain</th>
                    <th>Image size</th>
                    <th>Column/row</th>
                    <th>Created At</th>

                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->api_data->data as $item): ?>

                    <tr class="odd">
                        <td style="vertical-align: middle;" ><?php echo $item->title; ?></td>
                        <td style="vertical-align: middle;" ><?php echo $item->domain; ?></td>
                        <td style="vertical-align: middle;" ><?php echo $item->img_size; ?>px</td>
                        <td style="vertical-align: middle;" ><?php echo $item->column_number; ?> x <?php echo $item->row_number; ?></td>
                        <td style="vertical-align: middle;" ><?php echo $item->date_created; ?></td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
                <tfoot>
                <tr>
                    <th>Name</th>
                    <th>Domain</th>
                    <th>Immg size</th>
                    <th>Column/row</th>
                    <th>Created At</th>


                </tr>
                </tfoot>
            </table>
            </div>

        <?php else : ?>
            <span style="color:red"><?php echo $this->api_data->error; ?></span>

        <?php endif;
    endif;
    endif; ?>


        <!-- load js scripts -->

        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo content_url(); ?>/plugins/aklamator-digital-pr/assets/dataTables/jquery.dataTables.min.js"></script>


        <script type="text/javascript">
            $(document).ready(function(){

                if ($('table').hasClass('dynamicTable')) {
                    $('.dynamicTable').dataTable({
                        "iDisplayLength": 10,
                        "sPaginationType": "full_numbers",
                        "bJQueryUI": false,
                        "bAutoWidth": false

                    });
                }
            });

        </script>

    <?php
    }


}


new AklamatorWidget();


// Widget section


add_action( 'after_setup_theme', 'vw_setup_vw_widgets_init_aklamator' );
function vw_setup_vw_widgets_init_aklamator() {
    add_action( 'widgets_init', 'vw_widgets_init_aklamator' );
}

function vw_widgets_init_aklamator() {
    register_widget( 'Wp_widget_aklamator' );
}

class Wp_widget_aklamator extends WP_Widget {

    private $default = array(
        'supertitle' => '',
        'title' => '',
        'content' => '',
    );



    public function __construct() {
        // widget actual processes
        parent::__construct(
            'wp_widget_aklamator', // Base ID
            'Aklamator Digital PR', // Name
            array( 'description' => __( 'Display Aklamator Widgets in Sidebar')) // Widget Description
        );


    }

    function widget( $args, $instance ) {
        extract($args);
        //var_dump($instance); die();

        $supertitle_html = '';
        if ( ! empty( $instance['supertitle'] ) ) {
            $supertitle_html = sprintf( __( '<span class="super-title">%s</span>', 'envirra' ), $instance['supertitle'] );
        }

        $title_html = '';
        if ( ! empty( $instance['title'] ) ) {
            $title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base);
            $title_html = $supertitle_html.$title;
        }

        echo $before_widget;
        if ( $instance['title'] ) echo $before_title . $title_html . $after_title;
        ?>
        <?php echo $this->show_widget(do_shortcode( $instance['widget_id'] )); ?>
        <?php

        echo $after_widget;
    }

    private function show_widget($widget_id){
        $code = ""; ?>
        <!-- created 2014-11-25 16:22:10 -->
        <div id="akla<?php echo $widget_id; ?>"></div>
        <script>(function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "http://aklamator.com/widget/<?php echo $widget_id; ?>";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'aklamator-<?php echo $widget_id; ?>'));</script>
        <!-- end -->
    <?php }

    function form( $instance ) {

        $widget_data = new AklamatorWidget();

        $instance = wp_parse_args( (array) $instance, $this->default );

        $supertitle = strip_tags( $instance['supertitle'] );
        $title = strip_tags( $instance['title'] );
        $content = $instance['content'];
        $widget_id = $instance['widget_id'];


        if($widget_data->api_data->flag && !empty($widget_data->api_data->data)): ?>

            <!-- title -->
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title (text shown above widget):','envirra-backend'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            </p>

            <!-- Select - dropdown -->
            <label for="<?php echo $this->get_field_id('widget_id'); ?>"><?php _e('Widget:','envirra-backend'); ?></label>
            <select id="<?php echo $this->get_field_id('widget_id'); ?>" name="<?php echo $this->get_field_name('widget_id'); ?>">
                <?php foreach ( $widget_data->api_data->data as $item ): ?>
                    <option <?php echo ($widget_id == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <br>
            <br>
        <?php else :?>
            <br>
            <span style="color:red">Please make sure that you configured Aklamator plugin correctly</span>
            <a href="<?php echo admin_url(); ?>admin.php?page=aklamator-digital-pr">Click here to configure Aklamator plugin</a>
            <br>
            <br>
        <?php endif;

    }
}