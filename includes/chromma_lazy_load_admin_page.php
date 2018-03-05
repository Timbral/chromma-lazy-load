<?php
//construct options page
add_action( 'admin_menu', 'chromma_lazy_load_plugin_menu' );

/*Add Menu*/
function chromma_lazy_load_plugin_menu() {
	add_management_page(
		'Chromma Lazy Load',
		'Chromma Lazy Load',
		'manage_options',
		'chromma-lazy-load',
		'chromma_lazy_load_options'
	);
}

function chromma_lazy_load_options() {
  //must check that the user has the required capability
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    //hidden field name
    $hidden_field_name = 'mt_submit_hidden';


    // Read in existing option value from database
    $option_chromma_loadeffect_val = get_option( 'chromma_loadeffect' );
		$option_chromma_load_dimensions = get_option('chromma-load-dimensions');

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' )
    {
        // Read their posted value
        $option_chromma_loadeffect_val =  $_POST[ 'chromma-loadeffect' ];
				$option_chromma_load_dimensions = $_POST[ 'chromma-load-dimensions' ];
    }

    // Save the posted value in the database
    update_option( 'chromma_loadeffect', $option_chromma_loadeffect_val );
		update_option( 'chromma-load-dimensions', $option_chromma_load_dimensions );

    //Put a "settings saved" message on the screen
  ?>
  <div class="updated"><p><strong><?php _e('settings saved.', 'menu-test' ); ?></strong></p></div>
  <?php
	//ask for the currently set option choice
	$selected_effect = get_option( 'chromma_loadeffect' );
	$all_thumbnail_sizes = get_intermediate_image_sizes();
	$selected_dimension = get_option('chromma-load-dimensions');
	?>

  <div class="wrap">
    <h1>Chromma Lazy Load Settings</h1>
      <form name="chromma-loadeffect-form" method="post" action="">
        <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y"/>
        <label><strong>Load Effect:</strong></label>&nbsp;
        <select name="chromma-loadeffect" id="chromma-loadeffect">
            <option value="fadein" <?php selected( $selected_effect, 'fade-in' ); ?>>Fade-in</option>
            <option value="blur" <?php selected( $selected_effect, 'blur' ); ?>>Blur</option>
        </select>
				<br/>
				<label><strong>If using "blur" effect, please choose a low-res thumbnail size.</strong></label>&nbsp;
				<select name="chromma-load-dimensions" id="chromma-load-dimensions">
						<?php
						foreach($all_thumbnail_sizes as $thumnail_size)
						{
							$thumnail_size_dimensions= get_image_size($thumnail_size);
							$dimensions = "-".$thumnail_size_dimensions["width"].'x'.$thumnail_size_dimensions["height"];
							echo '<option value="'.$dimensions.'"'.	selected( $selected_dimension, $dimensions ) .'>'.$thumnail_size.'  W: '.$thumnail_size_dimensions["width"].'  H: '.$thumnail_size_dimensions["height"].'</option>';
						}
						?>
				</select>
        <div class="submit">
          <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
        </div>
      </form>
  </div>

<?php
	//write scss to file based on option $selected
	$lazyload_scss_file = plugin_dir_path( __FILE__ ) . '../assets/_lazyload.scss';
	$handle = fopen($lazyload_scss_file, 'w') or die('Cannot open file:  '.$lazyload_scss_file);
	$scss_data = '';
	if(get_option('chromma_loadeffect') == 'fadein')
	{
		$scss_data = '.lazyload-img {
			will-change: transform, opacity, filter;
			perspective: 1000;
			backface-visibility: hidden;
			-webkit-backface-visibility: hidden;
			-webkit-transform: translate3d(0, 0, 0);
			transform: translate3d(0, 0, 0);
			-webkit-transform-style: preserve-3d;
			image-rendering: -webkit-optimize-contrast;
		}

		//lazy load fade in
		.llreplace {
			opacity: 0;
		}
		.reveal {
			transition: reveal .8s ease-out;
			opacity: 1;
		}
	}
	elseif(get_option('chromma_loadeffect') == 'blur')
	{
		$scss_data = '.lazyload-img {
			will-change: transform, opacity, filter;
			perspective: 1000;
			backface-visibility: hidden;
			-webkit-backface-visibility: hidden;
			-webkit-transform: translate3d(0, 0, 0);
			transform: translate3d(0, 0, 0);
			-webkit-transform-style: preserve-3d;
			image-rendering: -webkit-optimize-contrast;
		}

		//lazy load blur
		.llreplace {
			filter: blur(2vw);
			-webkit-filter: blur(2vw);
		}
		.reveal {
			filter: opacity(1) !important;
			-webkit-filter: opacity(1) !important;
			animation-fill-mode: forwards;
			animation-iteration-count: 1;
			animation: reveal .6s ease-out;
		}

		@keyframes reveal {
			0% {filter: blur(2vw); -webkit-filter: blur(2vw);}
			100% {filter: blur(0); -webkit-filter: blur(0);}
		}';
	}
	fwrite($handle, $scss_data);
	fclose($handle);
}
