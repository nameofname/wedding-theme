<?php

add_action( 'init', 'remove_crap' );
    function remove_crap() {

    //remove_custom_image_header();
    remove_custom_background();
    //remove_theme_support('post-formats');
}

/**
 * Function to set the output of the hero banner admin page. 
 */
add_action('admin_menu', 'hero_admin');

function hero_admin() {
    if (current_user_can('manage_options'))  {
        add_theme_page('Hero Image admin', 'Hero Image', 'manage_options', 'hero-images', 'hero_image_admin');
    }
}

/**
 * Sets up the hero image admin page. 
 * Uses update_option, add_option to store image locations in the DB, 
 * Uses custom wrapper around wp_handle_upload to do file upload. 
 */
function hero_image_admin() {
    // Check whether a file was submitted via form. If so, handle the upload: 
    if (isset($_POST['page_slug']) && $_FILES['file']){
        do_upload(); 
    } else {
    // If one or the other is not set, render the hero form: 
        return hero_form(); 
    }
}

/**
 * Takes $_POST  and $_FILES variables and attempts to do an actual upload
 */
function do_upload(){
    // set page slug
    $page_slug = $_POST['page_slug']; 
    // set file for upload 
    $upload = $_FILES['file']; //Receive the uploaded image from form
    // upload file, and get back upload path. 
    $upload = image_upload($upload); 
    //echo '<pre>'; print_r($file_path); exit; 
    if (is_array($upload)) {
        $file_path = $upload['url']; 
        $path_stored = store_hero_location($page_slug, $file_path);
        if ($path_stored){
            echo "<h1>The hero banner has been updated for &#34;$page_slug&#34;</h1>"; 
            echo "<a href='/wp-admin/themes.php?page=hero-images'>&#171; back</a>"; 
        } else {
            echo '<h1>Nothing happened, everything is bad...</h1>'; 
        }
        return true; 
    } else { // Some basic error handling for bad uploads. 
        echo 'There was a problem uploading your file: '; 
        switch ($upload) {
            case 'tmp_failed': 
                echo 'The file could not be uploaded to the tmp directory. Please verify you have the correct (server side) permissions'; break; 
            case 'already_exists': 
                echo 'This file already exists. '; break; 
            case 'not_writable': 
                echo 'The uploads directory is not writeable. Please change permissions '; 
        }
    }
}

/**
 * Use this funciton to set an option in the DB. 
 * The default functionality for wordpress function add_option is to do nothing if the option exists, 
 * So just add_option with blank params, then update option every time. 
 */
function store_hero_location($page_slug, $file_path) {
    $option_name = "hero_img_" . $page_slug; 
    $added = add_option($option_name, '', '', 'yes');
    $updated = update_option($option_name, $file_path); 

    return $updated; 
}


/**
 * Just echoes the HTML for the upload form. 
 */
function hero_form() {
    $pages = get_pages(); 
    $out = ''; 
    $out .= '
        <br /><form id="upload_form" enctype="multipart/form-data" method="post" class="well"> 
        <h1>Upload Hero Images! Because you are my hero.  </h1> 
        <p>Each page should be listed in the drop down, and you should be able to upload a hero image for each page. Please use high res pics (think above 2K px). </p>
        <select name="page_slug">
        '; 
        foreach ($pages as $page) {
            $out .= "<option value='$page->post_name'>$page->post_title</option>";
        }
        $out .= '</select>
        <!--<input type="hidden" name="MAX_FILE_SIZE" value="3000">!--> 
        <div>
        <label for="file">File to upload:</label> 
        <input id="file" type="file" name="file"> 
        </div>
        <div>
        <!--<label for="remove">Remove hero banner: </label> 
        <input id="remove" type="checkbox" name="remove"> -->
        </div>
        <br /><input id="submit" class="btn btn-primary btn-large" type="submit" name="submit" value="Submit"> 
        </form> ';
    //$file_dir=get_bloginfo('template_directory'); 
    $file_dir=get_stylesheet_directory_uri(); 
    wp_enqueue_style("functions", $file_dir."/bootstrap.min.css", false, "1.0", "all");  
    echo $out; 
}

/**
 * Wraps the wordpress function wp_handle_upload() - gives back useful output 
 */
function image_upload($upload){
//    echo '<pre>'; print_r($upload); exit; 
    $uploads = wp_upload_dir(); //Get path of upload dir of wordpress
    if (is_writable($uploads['path'])) {//Check if upload dir is writable 
        if ((!empty($upload['tmp_name']))) {  //Check if uploaded image is not empty
            if ($upload['tmp_name']) { //Check if image has been uploaded in temp directory
                $file=handle_image_upload($upload); /*Call our custom function to ACTUALLY upload the image*/
            } else {
                $file = 'tmp_failed'; 
            }
        } else {
            $file = 'already_exists'; 
        }
    }
    else {
        $file = 'not_writable'; 
    }
    return $file; 
}

function handle_image_upload($upload){
    global $post;
    if (file_is_displayable_image( $upload['tmp_name'] )) {//Check if image
        //handle the uploaded file
        $overrides = array('test_form' => false);
        $file=wp_handle_upload($upload, $overrides);
    }
    return $file;
}

?>
