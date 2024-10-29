<?php
    /*
    Plugin Name: Actionable
    Plugin URI: http://www.23systems.net/plugins/actionable
    Description: Actionable is a plugin for WordPress that allows you to create a check list of action items that users can check off and track.
    Author: Dan Zappone
    Version: v0.8.3
    Author URI: http://www.23systems.net/
    */
    global $actionable_path, $actionable_db, $actionable_actions_db, $actionable_categories_db, $table_prefix;
    $actionable_path = WP_PLUGIN_URL.'/actionable';
    load_plugin_textdomain('wpactionable', $path = $actionable_path);
    wp_enqueue_script('animatedcollapse', WP_PLUGIN_URL.'/actionable/js/animatedcollapse.js', array('jquery'));
    $actionable_db            = $table_prefix."actionable";
    $actionable_actions_db    = $table_prefix."actionable_actions";
    $actionable_categories_db = $table_prefix."actionable_categories";

    /*---- Instantiate plugin for WordPress ----*/
    $is_installed = get_option('actionable');
    if (empty($is_installed)) {
        actionable_init();
    }

    /*---- Admin Header ----*/
    function actionable_admin_head() {
        echo '<link rel="stylesheet" type="text/css" href="'.$actionable_path.'/css/admin.css" />';
    }
    add_action('admin_head', 'actionable_admin_head');

    /*---- Set up database and options ----*/
    function actionable_init() {
        add_option('actionable', true);
        global $wpdb, $actionable_db, $actionable_actions_db, $actionable_categories_db;
        $sql    = "CREATE TABLE IF NOT EXISTS ".$actionable_db." (
        `actionable_id` int(10) NOT NULL auto_increment,
        `user_id` bigint(20) default NULL,
        `actionable_value` longtext,
        PRIMARY KEY  (`actionable_id`),
        KEY `user_id` (`user_id`)
        )";
        $result = $wpdb->query($sql);
        $sql    = "CREATE TABLE  ".$actionable_actions_db." (
        `actionable_id` int(10) NOT NULL auto_increment,
        `actionable_action` text,
        `actionable_cat` smallint(5) default NULL,
        `actionable_value` smallint(5) default NULL,
        PRIMARY KEY  (`actionable_id`),
        KEY `ID` (`actionable_id`)
        )";
        $result = $wpdb->query($sql);
        $sql    = "CREATE TABLE  ".$actionable_categories_db." (
        `actionable_id` int(10) NOT NULL auto_increment,
        `actionable_cat` varchar(255) default NULL,
        PRIMARY KEY  (`actionable_id`)
        )";
        $result = $wpdb->query($sql);
        $wpdb->hide_errors();
    }

    /*---- Wrapper function which calls the form and processes input. ----*/
    function wpactionable_callback($content) {
        global $wpactionable_strings, $wpdb, $user_ID, $actionable_db, $actionable_actions_db, $actionable_categories_db;
        get_currentuserinfo();
        if (!preg_match('|<!--actionable form-->|', $content)) {
            return $content;
        }

        /*-- If no user is logged then --*/
        if (!$user_ID) {
            echo '<p>By <a href="'.bloginfo('url').'/wp-register.php">registering</a>, you can create a green actions profile and save it for future reference.</p>';
        }

        /*-- Else show the form.  --*/
        else {
            $userprofile = $wpdb->get_results("SELECT actionable_value FROM ".$actionable_db." WHERE user_id = $user_ID");
            $serialactions = $wpdb->escape(serialize($_POST['box']));
            if (wpactionable_check_input()) {
                if ($userprofile) {
                    $query_upd = "UPDATE ".$actionable_db." SET actionable_value = '$serialactions' WHERE user_id = $user_ID";
                    $result = $wpdb->query($query_upd);
                }
                else {
                    $query_add = "INSERT INTO ".$actionable_db." (user_id, actionable_value) VALUES ($user_ID, '$serialactions')";
                    $result = $wpdb->query($query_add);
                }
            }
            $dbuserprofile = $wpdb->get_row("SELECT * FROM ".$actionable_db." WHERE user_id = $user_ID");
            $userprofile = $dbuserprofile->actionable_value;
            if ($userprofile) {
                $unserialactions = unserialize($userprofile);
            }
            $cathead  = $wpdb->get_results("SELECT * FROM ".$actionable_categories_db."");
            $form     = '<a name="menu"></a>'.eol();
            $catcount = 1;
            $form    .= '<table width="480" border="0" cellpadding="10" cellspacing="0" class="action-table">'.eol();
            $form    .= '<tr>'.eol();
            foreach ($cathead as $catitemhead) {
                $divref = divname($catitemhead->actionable_cat);
                $divref = "javascript:animatedcollapse.toggle('$divref')";
                $form  .= '<td class="action-menu-item"><a href="'.$divref.'">'.$catitemhead->actionable_cat.'</td>'.eol();
                if ($catcount % 6 == 0) {
                    $form .= '</tr><tr>'.eol();
                }
                $catcount++;
            }
            $form .= '<tr>'.eol();
            $form .= '</table>'.eol();
            $form .= '<div class="actionform">'.eol();
            $form .= '<form name="actions" action="'.get_permalink().'" method="post">'.eol();

            /*---- TO DO - demographic information ex. $form .= 'Postal Code: <input type="text" name="zipcode" />';   ----*/
            $catlist = $wpdb->get_results("SELECT * FROM ".$actionable_categories_db."");
            foreach ($catlist as $catitem) {
                $actionlist = $wpdb->get_results("SELECT * FROM ".$actionable_actions_db." WHERE actionable_cat = $catitem->actionable_id");
                $form      .= '<!--a name="'.$catitem->actionable_cat.'" class="collapse"/ --><div class="action-div" id="'.divname($catitem->actionable_cat).'">';
                $form      .= '<h3 class="action-head">'.$catitem->actionable_cat.'</h3>';
                $form      .= '<table border="0" cellpadding="10" cellspacing="0" class="action-table">';
                $rowcount   = 1;
                foreach ($actionlist as $actionitem) {
                    if ($rowcount % 2 == 0) {
                        $rowcolor = ' class="action-alt-row"';
                    }
                    else {
                        $rowcolor = '';
                    }
                    $currentaction = $unserialactions[$actionitem->actionable_id];
                    $form .= '<tr'.$rowcolor.'><td valign="top" style="padding:5px;"><input type="checkbox" name="box['.$actionitem->actionable_id.']" value="1" '.wpactionable_check_box($currentaction).'/></td><td valign="top" style="padding: 0 5px 5px 5px;">'.$actionitem->actionable_action.'</tr>';
                    $rowcount++;
                }
                $form .= "</table>";
                $form .= '<div style="float:left;"><input type="submit" name="Submit-'.$catitem->actionable_cat.'" value="'.__('Submit', 'wpactionable').'" id="actionssubmit-'.$catitem->actionable_cat.'" class="action-button" /></div><div style="float:right;"><a href="#menu">Top</a></div><div class="action-clear">&nbsp;</div>';
                $form .= '</div>';
            }
            $form .= '<input type="hidden" name="actionable_post" value="process" />';
            $form .= '</form>';
            $form .= '</div>';
            $form .= '<div style="clear:both; height:1px;">&nbsp;</div>';
            return str_replace('<!--actionable form-->', $form, $content);
        }

        /*-- END - if (!($user_ID) else  --*/
    }

    /*-- END - wpactionable_callback --*/
    /*---- This function checks to see if data is being posted ----*/
    function wpactionable_check_input() {
        if (!(isset($_POST['actionable_post']))) {
            return false;
        }
        else {
            return true;
        }
    }

    /*---- Sets checkboxes to checked if the action profile contains that item ----*/
    function wpactionable_check_box($boxvalue) {
        if ($boxvalue == "1") {
            $setvalue = 'checked="checked"';
        }
        else {
            $setvalue = "";
        }
        return $setvalue;
    }

    /*---- Actionable CSS Styling and animatedcollapse (should be moved) ---*/
    function wpactionable_css() {
        global $actionable_path, $wpdb, $actionable_categories_db;
        $wp = get_bloginfo('wpurl');
        if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN')) {
            $endl = "\r\n";
        }
        elseif (strtoupper(substr(PHP_OS, 0, 3) == 'MAC')) {
            $endl = "\r";
        }
        else {
            $endl = "\n";
        }
        $url = $wp.'/'.$actionable_path;
        echo '<link rel="stylesheet" href="'.$actionable_path.'/css/style.css" type="text/css" media="screen" />';
        echo '<script type="text/javascript">'.$endl;
        $divlist = $wpdb->get_results("SELECT * FROM ".$actionable_categories_db."");
        foreach ($divlist as $divitem) {
            $divid = divname($divitem->actionable_cat);
            echo "animatedcollapse.addDiv('$divid', 'fade=1,speed=400,group=pets,persist=1,hide=1')".$endl;
        }
        echo 'animatedcollapse.init()'.$endl;
        echo '</script>'.$endl;
    }

    function divname($divinput) {
        $divtag = str_replace(" ", "-", $divinput);
        $divtag = strtolower($divtag);
        return $divtag;
    }

    /*---- Add administrative page ----*/
    function wpactionable_add_options_page() {
        add_options_page('Actionable Options', 'Actionable', 8, basename(__FILE__), 'wpactionable_subpanel');
    }

    function eol() {
        if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN')) {
            $el = "\r\n";
        }
        elseif (strtoupper(substr(PHP_OS, 0, 3) == 'MAC')) {
            $el = "\r";
        }
        else {
            $el = "\n";
        }
        return $el;
    }

    function wpactionable_count_stats($table) {
        global $wpdb;
        $stats_count = $wpdb->get_var("SELECT COUNT(*) FROM $table;");
        return $stats_count;
    }

    function wpactionable_list_users() {
        global $wpdb, $user_ID, $actionable_db;
        $actionable_user_list = "";
        $actionableusers = $wpdb->get_results("SELECT user_id FROM ".$actionable_db." ORDER by user_id ASC;");
        foreach ($actionableusers as $actionableuser) {
            $userid                  = $actionableuser->user_id;
            $actionable_user         = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE ID = $userid");
            $actionable_action_count = count_user_actions($userid);
            $actionable_user_list   .= "$actionable_user->user_login - $actionable_action_count actions<br />".eol();
        }
        return $actionable_user_list;
    }

    function count_user_actions($usrid) {
        global $wpdb, $actionable_db;
        $useractions = $wpdb->get_row("SELECT * FROM ".$actionable_db." WHERE user_id = $usrid");
        $actioncount = $useractions->actionable_value;
        $actioncount = unserialize($actioncount);
        $actioncount = count($actioncount);
        return $actioncount;
    }

    /*
    function wpactionable_show_results {
    global $wpdb;
    $numberactions = wpactionable_count_stats('wp_actionable_actions');
    $totalresults = $wpdb->get_results("SELECT actionable_value FROM wp_actionable WHERE user_id = $user_ID");
    foreach ($totalresults as $result) {
    $currentresult = $unserialactions[$result->actionable_id];
    foreach ($currentresult as $resultkey->$resultitem) {
    for ($i = 0; $i <= $numberactions; $i++) {

    }
    }
    }
    }
    */
    /*---- Add administrative page ----*/
    function wpactionable_add_pages() {

        /*-- Add a new submenu under Manage:  --*/
        add_management_page('Actionable', 'Actionable', 8, 'actionable.php', 'wpactionable_manage_page');
    }

    /*---- Displays the page content for the Actionable Managment ----*/
    function wpactionable_manage_page() {
        global $table_prefix, $wpdb, $actionable_db, $actionable_actions_db, $actionable_categories_db;
        $action = $_GET['action'];
        if (!empty($action)) {
            switch ($action) {
                case "add":
                    $type = $_REQUEST['type'];
                    if (!empty($type)) {

                        /*-- Save Action --*/
                        if ($_POST['add_action']) {

                            /*-- Get postdata  --*/
                            $actionable_item     = htmlspecialchars(trim($_POST['actionable_item']));
                            $actionable_category = htmlspecialchars(trim($_POST['actionable_category']));
                            $actionable_value    = htmlspecialchars(trim($_POST['actionable_value']));

                            /*-- validate fields  --*/
                            if (empty($_POST['actionable_item'])) 
                                $errors .= __('<div class="error"><strong>Action</strong> is required</div>', "actionable");
                            if ($_POST['actionable_category'] == '0') 
                                $errors .= __('<div class="error"><strong>Category</strong> is required</div>', "actionable");
                            $query_add = sprintf("INSERT INTO %s (actionable_action, actionable_cat, actionable_value) VALUES ('%s','%s','%s')", $wpdb->escape($actionable_actions_db), $wpdb->escape($actionable_item), $wpdb->escape($actionable_category), $wpdb->escape($actionable_value));
                            $result    = $wpdb->query($query_add);
                            if ($result) {
                                if (empty($info)) 
                                    echo '<div id="message" class="updated fade"><p><strong>'.__("Action successfully added", "actionable").'</strong></p></div>';
                                else 
                                    echo '<div id="message" class="updated fade"><p><strong>'.__("Download added Successfully", "wp-download_monitor").' - '.$info.'</strong></p></div>';
                                $_POST['add_action'] = "";
                                $show = true;
                            }
                            else 
                                _e('<div class="error">Error saving to database</div>', "actionable");
                            break;
                        }
                    }
                    if (!empty($_POST['add_new_action'])) {
                    ?>
                    <div class="wrap">
                        <h2><?php _e('Add Action', 'actionable');?></h2>
                        <form action="?page=actionable.php&amp;action=add&amp;type=insert" method="post" id="actionable_add" name="add_action" class="form-table">



                            <table class="optiontable niceblue">
                                <tr valign="middle">
                                    <th scope="row"><strong><?php _e('Action (required)', "actionable");?>: </strong></th>
                                    <td>
                                        <textarea style="width:320px;" class="cleardefault" name="actionable_item" id="actionable_item"><?php echo $actionable_item;?></textarea>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><strong><?php _e('Category', "actionable");?></strong></th>
                                    <td>
                                        <select name="actionable_category">
                                            <option value="">N/A</option>
                                            <?php
                                                $query_select_cats = sprintf("SELECT * FROM %s ORDER BY actionable_id;", $wpdb->escape($actionable_categories_db));
                                                $cats = $wpdb->get_results($query_select_cats);
                                                if (!empty($cats)) {
                                                    foreach ($cats as $c) {
                                                        echo '<option ';
                                                        if ($_POST['actionable_category'] == $c->actionable_id) 
                                                            echo 'selected="selected"';
                                                        echo 'value="'.$c->actionable_id.'">'.$c->actionable_cat.'</option>';
                                                    }
                                                }
                                            ?>
                                    </select><br /><?php _e('Categories are required and allow you to group and organize associated actions.', "actionable");?></td>
                                </tr>
                                <tr valign="middle">
                                    <th scope="row"><strong><?php _e('Value', "actionable");?>: </strong></th>
                                    <td>
                                        <select name="actionable_value">
                                            <option value="0">0</option>
                                            <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo '<option ';
                                                    if ($_POST['actionable_value'] == $i) 
                                                        echo 'selected="selected"';
                                                    echo 'value="'.$i.'">'.$i.'</option>';
                                                }
                                            ?>
                                    </select><br /><?php _e('Values are optional and allow you to assign weight to an action.', "actionable");?></td>
                                </tr>
                            </table>

                            <p class="submit"><input type="submit" class="btn" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Save Action', "actionable");?>" /></p>
                            <input type="hidden" name="add_action" value="add_action" />
                        </form>
                    </div>
                    <?php
                    }
                    break;

                case "edit":
                    break;

                case "delete":
                    break;
            }
        }
        if (empty($action)) {
        ?>
        <div class="wrap alternate">
            <h2><?php _e('Actionable Administration', 'actionable')?></h2>
            <?php _e('<h4>This page is currently a stub pending completion of administrative page development.  Currently you <strong>cannot</strong> add or edit actions, or categories.  You can page though the list of actions and view the current categories and basic statistics.</h4>', 'actionable')?>
            <br style="clear: both;"/>
            <form action="?page=actionable.php&amp;action=add" method="post" id="actionable_add" name="actionable_add">
                <div class="tablenav">
                    <div style="float: left;">
                        <input type="submit" class="button-secondary" name="add_new_action" value="<?php _e('Add New Action', "actionable");?>" />
                    </div>
                    <br style="clear: both;"/>
                </div>
            </form>
            <br style="clear: both;"/>
            <table class="widefat">
                <thead>
                    <tr>
                        <th scope="col" style="text-align:center"><a href="?page=actionable.php&amp;sort=actionable_id"><?php _e('ID', "wp-actionable");?></a></th>
                        <th scope="col"><a href="?page=actionable.php&amp;sort=actionable_action"><?php _e('Action', "actionable");?></a></th>
                        <th scope="col"><a href="?page=actionable.php&amp;sort=actionable_cat"><?php _e('Category', "actionable");?></a></th>
                        <th scope="col"><a href="?page=actionable.php&amp;sort=actionable_value"><?php _e('Value', "actionable");?></a></th>
                        <th scope="col"><?php _e('Action', "actionable");?></th>
                    </tr>
                </thead>
                <?php
                    /*-- If current page number, use it  --*/
                    if (!isset($_REQUEST['p'])) {
                        $page = 1;
                    }
                    else {
                        $page = $_REQUEST['p'];
                    }

                    /*-- Sort column  --*/
                    $sort = "actionable_action";
                    if ($_REQUEST['sort'] && ($_REQUEST['sort'] == "actionable_id" || $_REQUEST['sort'] == "actionable_cat" || $_REQUEST['sort'] == "actionable_value")) 
                        $sort = $_REQUEST['sort'];
                    $total_results = sprintf("SELECT COUNT(actionable_id) FROM %s;", $wpdb->escape($actionable_actions_db));

                    /*-- Figure out the limit for the query based on the current page number. --*/
                    $from         = (($page * 20) - 20);
                    $paged_select = sprintf("SELECT * FROM %s ORDER BY %s LIMIT %s,20;", $wpdb->escape($actionable_actions_db), $wpdb->escape($sort), $wpdb->escape($from));
                    $actions      = $wpdb->get_results($paged_select);
                    $total        = $wpdb->get_var($total_results);

                    /*-- Figure out the total number of pages. Always round up using ceil()  --*/
                    $total_pages = ceil($total / 20);
                    if (!empty($actions)) {
                        echo '<tbody id="the-list">';
                        foreach ($actions as $a) {
                            echo('<tr class="alternate">');
                            echo '<td style="text-align:center">'.$a->actionable_id.'</td>
                            <td>'.$a->actionable_action.'</td>
                            <td style="text-align:center">';
                            if ($a->actionable_cat == "" || $a->actionable_cat == 0) 
                                echo "N/A";
                            else {
                                $c = $wpdb->get_row("SELECT * FROM ".$actionable_categories_db." where actionable_id=".$a->actionable_cat." LIMIT 1;");
                                $chain = $c->actionable_cat;
                                while ($c->parent > 0) {
                                    $c = $wpdb->get_row("SELECT * FROM ".$actionable_categories_db." where actionable_id=".$c->parent." LIMIT 1;");
                                    $chain = $c->actionable_cat.' &mdash; '.$chain;
                                }
                                echo $chain;
                            }
                            echo '</td>
                            <td style="text-align:center">'.$a->actionable_value.'</td>
                            <td><a href="?page=actionable.php&amp;action=edit&amp;id='.$a->id.'&amp;sort='.$sort.'&amp;p='.$page.'"><img src="../wp-content/plugins/actionable/images/edit.png" alt="Edit" title="Edit" /></a> <a href="?page=actionable.php&amp;action=delete&amp;id='.$a->id.'&amp;sort='.$sort.'&amp;p='.$page.'"><img src="../wp-content/plugins/actionable/images/cross.png" alt="Delete" title="Delete" /></a></td>';
                        }
                        echo '</tbody>';
                    }
                    else 
                        echo '<tr><th colspan="10">'.__('No actions added yet.', "actionable").'</th></tr>';
                    // FIXED: 1.6 - Colspan changed
                ?>
            </table>
            <div class="tablenav">
                <div style="float:left">
                    <?php
                        if ($total_pages > 1) {

                            /*-- Build Page Number Hyperlinks --*/
                            if ($page > 1) {
                                $prev = ($page - 1);
                                echo "<a href=\"?page=actionable.php&amp;p=$prev&amp;sort=$sort\">&laquo; ".__('Previous', "actionable")."</a> ";
                            }
                            else 
                                echo "&laquo; ".__('Previous', "actionable")."";
                            for ($i = 1; $i <= $total_pages; $i++) {
                                if (($page) == $i) {
                                    echo " $i ";
                                }
                                else {
                                    echo " <a href=\"?page=actionable.php&amp;p=$i&amp;sort=$sort\">$i</a> ";
                                }
                            }

                            /*-- Build Next Link --*/
                            if ($page < $total_pages) {
                                $next = ($page + 1);
                                echo "<a href=\"?page=actionable.php&amp;p=$next&amp;sort=$sort\">".__('Next', "wp-download_monitor")." &raquo;</a>";
                            }
                            else 
                                echo __('Next', "actionable")." &raquo;";
                        }
                    ?>
                </div>
                <br style="clear: both;"/>
            </div>

            <div id="poststuff" class="actionable">
                <div class="postbox <?php if (!$ins_cat) { echo 'close-me'; } ?>">
                    <h3><?php _e('Actionable Categories', "actionable");?></h3>
                    <div class="inside">
                        <?php _e('<p>You must categorize your actions for them to appear.</p>', "actionable");?>

                        <form action="?page=actionable.php&amp;action=categories" method="post">
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th scope="col" style="text-align:center"><?php _e('ID', "actionable");?></th>
                                        <th scope="col">Name</th>
                                        <th scope="col" style="text-align:center"><?php _e('Action', "actionable");?></th>
                                    </tr>
                                </thead>
                                <tbody id="the-list">
                                    <?php
                                        $query_select_cats = sprintf("SELECT * FROM %s ORDER BY actionable_id;", $wpdb->escape($actionable_categories_db));
                                        $cats = $wpdb->get_results($query_select_cats);
                                        if (!empty($cats)) {
                                            foreach ($cats as $c) {
                                                echo '<tr><td style="text-align:center">'.$c->actionable_id.'</td><td>'.$c->actionable_cat.'</td><td style="text-align:center"><a href="?page=actionable.php&amp;action=deletecat&amp;id='.$c->actionable_id.'"><img src="../wp-content/plugins/actionable/images/cross.png" alt="Delete" title="Delete" /></a></td></tr>';
                                            }
                                        }
                                        else {
                                            echo '<tr><td colspan="3">'.__('No categories exist', "actionable").'</td></tr>';
                                        }
                                    ?>
                                </tbody>
                            </table>
                            <h4><?php _e('Add category', "actionable");?></h4>
                            <table class="niceblue">
                                <tr>
                                    <th scope="col"><?php _e('Name', "actionable");?>:</th>
                                    <td><input type="text" name="cat_name" /></td>
                                </tr>
                                <tr>
                                    <th scope="col">&nbsp;</th>
                                    <td><input type="submit" value="<?php _e('Add', "actionable");?>" /></td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
                <div class="postbox close-me">
                    <h3><?php _e('Statistics', "actionable");?></h3>
                    <div class="inside">
                        <h4><?php _e('General Statistics', "actionable");?></h4>
                        <?php _e('<p>Number of actions available: '.wpactionable_count_stats($actionable_actions_db).'</p>
                            <p>Number of action categories: '.wpactionable_count_stats($actionable_categories_db).'</p>
                            <p>Number of action profiles entered: '.wpactionable_count_stats($actionable_db).'</p>', "actionable");?>
                        <h4><?php _e('Users with Profiles', "actionable");?></h4>
                        <?php _e(wpactionable_list_users(), "actionable");?>
                        <h4><?php _e('Forthcoming features', "actionable");?></h4>
                        <?php _e('<ol>
                            <li>Add, edit and delete actions</li>
                            <li>Add and collect demographic information</li>
                            <li>Add, edit and delete categories  and subcategories</li>
                            <li>View collated totals by action</li>
                            <li>View individual actions per user</li>
                            <li>More...</li>
                            </ol>', "actionable");?>
                    </div>
                </div>
            </div>

        </div> <!-- warp alternate -->
        <script type="text/javascript">
            <!--
            jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
            //jQuery('.togbox').click( function() { jQuery(jQuery(this).parent().parent().get(0)).toggleClass('closed'); } );
            jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
            jQuery('.postbox.close-me').each(function(){
                jQuery(this).addClass("closed");
            });
            //-->
        </script>
        <?php
        }
    }

    /*-- Hook for adding admin menus --*/
    add_action('admin_menu', 'wpactionable_add_pages');

    /* add_action('admin_menu', 'wpactionable_add_options_page'); */
    add_filter('wp_head', 'wpactionable_css');
    add_filter('the_content', 'wpactionable_callback', 7);
?>