<?php
/*
Plugin Name: Aklamator - Digital PR
Plugin URI: https://www.aklamator.com/wordpress
Description: Aklamator digital PR service enables you to sell PR announcements, cross promote web sites using RSS feed and provide new services to your clients in digital advertising.
Version: 1.9.3
Author: Aklamator
Author URI: https://www.aklamator.com/
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
 * Add rate and review link in plugin section
 */
if( !function_exists("aklamator_plugin_meta_links")) {
    function aklamator_plugin_meta_links($links, $file)
    {
        $plugin = plugin_basename(__FILE__);
        // create link
        if ($file == $plugin) {
            return array_merge(
                $links,
                array('<a href="https://wordpress.org/support/view/plugin-reviews/aklamator-digital-pr" target=_blank>Please rate and review</a>')
            );
        }
        return $links;
    }
}
add_filter( 'plugin_row_meta', 'aklamator_plugin_meta_links', 10, 2);


/*
 * Adds featured images from posts to your site's RSS feed output,
 */

if(!function_exists('akla_featured_images_in_rss')) {
    function akla_featured_images_in_rss($content){
        global $post;
        if (has_post_thumbnail($post->ID)) {
            $featured_images_in_rss_size = 'thumbnail';
            $featured_images_in_rss_css_code = 'display: block; margin-bottom: 5px; clear:both;';
            $content = get_the_post_thumbnail($post->ID, $featured_images_in_rss_size, array('style' => $featured_images_in_rss_css_code)) . $content;
        }
        return $content;
    }
}

if(get_option('aklamatorFeatured2Feed')){
    add_filter('the_excerpt_rss', 'akla_featured_images_in_rss', 1000, 1);
    add_filter('the_content_feed', 'akla_featured_images_in_rss', 1000, 1);
}




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
    add_option('aklamatorFeatured2Feed', 'on');
}

/*
 * Uninstall Hook
 */
register_uninstall_hook(__FILE__, 'aklamator_uninstall');

function aklamator_uninstall()
{
    delete_option('aklamatorApplicationID');
    delete_option('aklamatorPoweredBy');
    delete_option('aklamatorSingleWidgetID');
    delete_option('aklamatorPageWidgetID');
    delete_option('aklamatorSingleWidgetTitle');
    delete_option('aklamatorFeatured2Feed');
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
            js.src = \"https://aklamator.com/widget/$widget_id\";
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

        $this->aklamator_url = "https://aklamator.com/";


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

        if(get_option('aklamatorFeatured2Feed')){
            update_option('aklamatorFeatured2Feed', 'on');
        }

    }

    function setOptions()
    {
        register_setting('aklamator-options', 'aklamatorApplicationID');
        register_setting('aklamator-options', 'aklamatorPoweredBy');
        register_setting('aklamator-options', 'aklamatorSingleWidgetID');
        register_setting('aklamator-options', 'aklamatorPageWidgetID');
        register_setting('aklamator-options', 'aklamatorSingleWidgetTitle');
        register_setting('aklamator-options', 'aklamatorFeatured2Feed');

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
        '&un=' . urlencode(wp_get_current_user()->display_name).'&domain='.site_url();

    }

    private function addNewWebsiteApi()
    {

        if (!is_callable('curl_init')) {
            return;
        }


        $service     = $this->aklamator_url . "wp-authenticate/user";
        $p['ip']     = $_SERVER['REMOTE_ADDR'];
        $p['domain'] = site_url();
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
        if (curl_error($client)!= "") {
            $this->curlfailovao=1;
        } else {
            $this->curlfailovao=0;
        }

        curl_close($client);

        $data = json_decode($data);

        return $data;

    }

    public function createAdminPage()
    {
        $code = get_option('aklamatorApplicationID');

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

            .btn { font-size: 13px; border-radius: 5px; background: transparent; text-transform: uppercase; font-weight: 700; padding: 4px 10px; min-width: 162px; max-width: 100%; text-decoration: none;}

            .btn-primary { background: #7BB32C; border:1px solid #fff; color: #fff; text-decoration: none}
            .btn-primary:hover, .btn-primary.hovered { background: #7BB32C;  border:1px solid #167AC6; opacity:0.9; color: #fff }
            .btn-primary:Active, .btn-primary.pressed { background: #7BB32C; border:1px solid #167AC6; color: #fff}

            .box{float: left; margin-left: 10px; width: 500px; background-color:#f8f8f8; padding: 10px; border-radius: 5px;}
            .right_sidebar{float: right; margin-left: 10px; width: 300px; background-color:#f8f8f8; padding: 10px; border-radius: 5px;}

            .alert{
                margin-bottom: 18px;
                color: #c09853;
                text-shadow: 0 1px 0 rgba(255,255,255,0.5);
                background-color: #fcf8e3;
                border: 1px solid #fbeed5;
                -webkit-border-radius: 4px;
                -moz-border-radius: 4px;
                border-radius: 4px;
                padding: 8px 35px 8px 14px;
            }
            .alert-msg {
                color: #3a87ad;
                background-color: #d9edf7;
                border-color: #bce8f1;
            }


        </style>
        <!-- Load css libraries -->

        <link href="//cdn.datatables.net/1.10.5/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">

        <div id="aklamator-options" style="width:1160px;margin-top:10px;">

            <div style="float: left; width: 300px;">
                    
                <a target="_blank" href="<?php echo $this->aklamator_url; ?>?utm_source=wp-plugin">
                    <img style="border-radius:5px;border:0px;" src=" <?php echo plugins_url('images/logo.jpg', __FILE__);?>" /></a>
                <?php
                if ($code != '') : ?>
                    <a target="_blank" href="<?php echo $this->aklamator_url; ?>dashboard?utm_source=wp-plugin">
                        <img style="border:0px;margin-top:5px;border-radius:5px;" src="<?php echo plugins_url('images/dashboard.jpg', __FILE__); ?>" /></a>

                <?php endif; ?>

                <a target="_blank" href="<?php echo $this->aklamator_url;?>contact?utm_source=wp-plugin-contact">
                    <img style="border:0px;margin-top:5px; margin-bottom:5px;border-radius:5px;" src="<?php echo plugins_url('images/support.jpg', __FILE__); ?>" /></a>

                <a target="_blank" href="http://qr.rs/q/4649f"><img style="border:0px;margin-top:5px; margin-bottom:5px;border-radius:5px;" src="<?php echo plugins_url('images/promo-300x200.png', __FILE__); ?>" /></a>

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
                        ?>" maxlength="999" onchange="appIDChange(this.value)"/>

                    </p>
                    <p>
                        <input type="checkbox" id="aklamatorPoweredBy" name="aklamatorPoweredBy" <?php echo (get_option("aklamatorPoweredBy") == true ? 'checked="checked"' : ''); ?> Required="Required">
                        <strong>Required</strong> I acknowledge there is a 'powered by aklamator' link on the widget. <br />
                    </p>
                    <p>
                        <input type="checkbox" id="aklamatorFeatured2Feed" name="aklamatorFeatured2Feed" <?php echo (get_option("aklamatorFeatured2Feed") == true ? 'checked="checked"' : ''); ?> >
                        <strong>Add featured</strong> images from posts to your site's RSS feed output
                    </p>

                    <p>
                        <div class="alert alert-msg">
                            <strong>Note </strong><span style="color: red">*</span>: By default, posts without images will not be shown in widgets. If you want to show them click on <strong>EDIT</strong> in table below!
                        </div>
                    </p>
                    <?php if($this->api_data->flag === false): ?>
                        <p><span style="color:red"><?php echo $this->api_data->error; ?></span></p>
                    <?php endif; ?>
           
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
                    <input style="margin-left: 5px;" id="preview_single" type="button" class="button primary big submit" onclick="myFunction($('#aklamatorSingleWidgetID option[selected]').val())" value="Preview" <?php echo get_option('aklamatorSingleWidgetID')=="none"? "disabled" :"" ;?>>
                    </p>

                    <p>
                        <label for="aklamatorPageWidgetID">Single page: </label>
                        <select id="aklamatorPageWidgetID" name="aklamatorPageWidgetID">
                            <?php
                            foreach ( $widgets as $item ): ?>
                                <option <?php echo (get_option('aklamatorPageWidgetID') == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input style="margin-left: 5px;" type="button" id="preview_page" class="button primary big submit" onclick="myFunction($('#aklamatorPageWidgetID option[selected]').val())" value="Preview" <?php echo get_option('aklamatorPageWidgetID')=="none"? "disabled" :"" ;?>>

                    </p>

                
            <?php endif; ?>
             <input style ="margin-bottom:15px;" type="submit" value="<?php echo (_e("Save Changes")); ?>" />


            </form>
            </div>
            <!-- right sidebar -->
            <div class="right_sidebar">
                <h3 style="text-align: center">Watch Walk-through tutorial</h3>
                <iframe width="300" height="225" src="https://www.youtube.com/embed/cCh-ayz6z5E?rel=0" frameborder="0" allowfullscreen></iframe>

                <h3 style="text-align: center">Benefits & Cross promotion tool</h3>
                <iframe width="300" height="225" src="https://www.youtube.com/embed/NA_yWJneYkI?rel=0" frameborder="0" allowfullscreen></iframe>
            </div>
            <!-- End Right sidebar -->
        </div>

        <div style="clear:both"></div>
        <div style="margin-top: 20px; margin-left: 0px; width: 810px;" class="box">

            <?php if ($this->curlfailovao && get_option('aklamatorApplicationID') != ''): ?>
                <h2 style="color:red">Error communicating with Aklamator server, please refresh plugin page or try again later. </h2>
            <?php endif;?>
            <?php if(!$this->api_data->flag): ?>
                <a href="<?php echo $this->getSignupUrl(); ?>" target="_blank"><img style="border-radius:5px;border:0px;" src=" <?php echo plugins_url('images/teaser-810x262.png', __FILE__);?>" /></a>
            <?php else : ?>
            <!-- Start of dataTables -->
            <div id="aklamatorPro-options">
                <h1>Your Widgets</h1>
                <div>In order to add new widgets or change dimensions please <a href="<?php echo $this->aklamator_url ;?>login" target="_blank">login to aklamator</a></div>
            </div>
            <br>
            <table cellpadding="0" cellspacing="0" border="0"
                   class="responsive dynamicTable display table table-bordered" width="100%">
                <thead>
                <tr>

                    <th>Name</th>
                    <th>Domain</th>
                    <th>Settings</th>
                    <th>Image size</th>
                    <th>Column/row</th>
                    <th>Created At</th>

                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->api_data->data as $item): ?>

                    <tr class="odd">
                        <td style="vertical-align: middle;" ><?php echo $item->title; ?></td>
                        <td style="vertical-align: middle;" >
                            <?php foreach($item->domain_ids as $domain): ?>
                                <a href="<?php echo $domain->url; ?>" target="_blank"><?php echo $domain->title; ?></a><br/>
                            <?php endforeach; ?>
                        </td>
                        <td style="vertical-align: middle">
                            <div style="float: left; margin-right: 10px" class="button-group">
                                <input type="button" class="button primary big submit" onclick="myFunction('<?php echo $item->uniq_name; ?>')" value="Preview Widget">
                            </div>
                        </td>
                        <td style="vertical-align: middle;" ><?php echo "<a href = \"$this->aklamator_url"."widget/edit/$item->id\" target='_blank' title='Click & Login to change'>$item->img_size px</a>";  ?></td>
                        <td style="vertical-align: middle;" ><?php echo "<a href = \"$this->aklamator_url"."widget/edit/$item->id\" target='_blank' title='Click & Login to change'>".$item->column_number ." x ". $item->row_number."</a>"; ?>

                            <div style="float: right;">
                                <?php echo "<a class=\"btn btn-primary\" href = \"$this->aklamator_url"."widget/edit/$item->id\" target='_blank' title='Edit widget settings'>Edit</a>"; ?>
<!--                                <a type="button" class="button primary big submit"  value="Preview Widget">-->
                            </div>

                        </td>
                        <td style="vertical-align: middle;" ><?php echo $item->date_created; ?></td>


                    </tr>
                <?php endforeach; ?>

                </tbody>
                <tfoot>
                <tr>
                    <th>Name</th>
                    <th>Domain</th>
                    <th>Settings</th>
                    <th>Immg size</th>
                    <th>Column/row</th>
                    <th>Created At</th>
                </tr>
                </tfoot>
            </table>
        </div>

    <?php endif; ?>


        <!-- load js scripts -->

        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo content_url(); ?>/plugins/aklamator-digital-pr/assets/dataTables/jquery.dataTables.min.js"></script>


        <script type="text/javascript">

            function appIDChange(val) {

                $('#aklamatorSingleWidgetID option:first-child').val('');
                $('#aklamatorPageWidgetID option:first-child').val('');

            }

            function myFunction(widget_id) {

                var myWindow = window.open('<?php echo $this->aklamator_url;?>show/widget/'+widget_id);

                myWindow.focus();

            }

            $(document).ready(function(){


                $("#aklamatorSingleWidgetID").change(function(){

                    if($(this).val() == 'none'){
                        $('#preview_single').attr('disabled', true);
                    }else{
                        $('#preview_single').removeAttr('disabled');
                    }

                    $(this).find("option").each(function () {
//
                        if (this.selected) {
                            $(this).attr('selected', true);

                        }else{
                            $(this).removeAttr('selected');

                        }
                    });

                });


                $("#aklamatorPageWidgetID").change(function(){

                    if($(this).val() == 'none'){

                        $('#preview_page').attr('disabled', true);
                    }else{
                        $('#preview_page').removeAttr('disabled');
                    }

                    $(this).find("option").each(function () {
//
                        if (this.selected) {
                            $(this).attr('selected', true);
                        }else{
                            $(this).removeAttr('selected');

                        }
                    });

                });



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

    public $aklamator_url = 'https://aklamator.com/';


    public $widget_data;

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
                js.src = "<?php echo $this->aklamator_url; ?>widget/<?php echo $widget_id; ?>";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'aklamator-<?php echo $widget_id; ?>'));</script>
        <!-- end -->
    <?php }

    function form( $instance ) {

        $widget = new AklamatorWidget();
        $this->aklamator_url = $widget->aklamator_url;
        $this->widget_data = $widget->api_data;

        $instance = wp_parse_args( (array) $instance, $this->default );

        $supertitle = strip_tags( $instance['supertitle'] );
        $title = strip_tags( $instance['title'] );
        $content = $instance['content'];
        $widget_id = $instance['widget_id'];


        if($this->widget_data->flag && !empty($this->widget_data->data)): ?>

            <!-- title -->
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title (text shown above widget):','envirra-backend'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            </p>

            <!-- Select - dropdown -->
            <label for="<?php echo $this->get_field_id('widget_id'); ?>"><?php _e('Widget:','envirra-backend'); ?></label>
            <select id="<?php echo $this->get_field_id('widget_id'); ?>" name="<?php echo $this->get_field_name('widget_id'); ?>">
                <?php foreach ( $this->widget_data->data as $item ): ?>
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