<?php

class acf_field_video extends acf_field
{
	// vars
	var $settings, // will hold info such as dir / path
		$defaults; // will hold default field options
		
		
	/*
	*  __construct
	*
	*/
	function __construct()
	{
		// vars
		$this->name = 'video';
		$this->label = __('Video');
		$this->category = __("Content",'acf'); // Basic, Content, Choice, etc
		$this->defaults = array(
			// add default here to merge into your field. 
			// This makes life easy when creating the field options as you don't need to use any if( isset('') ) logic. eg:
			'preview_type' => 'embed', //whether to display a preview for the video. Display video embed, thumbnail image or none
			'return_value' => 'url', //return the url, embed code, thumbnail url or array or all
		);
		
		
		// do not delete!
    	parent::__construct();
    	
    	
    	// settings
		$this->settings = array(
			'path' => apply_filters('acf/helpers/get_path', __FILE__),
			'dir' => apply_filters('acf/helpers/get_dir', __FILE__),
			'version' => '1.0.0'
		);

	}
	
	
	/*
	*  create_options()
	*
	*/
	function create_options( $field )
	{
		// defaults?
		$field = array_merge($this->defaults, $field);
		
		// key is needed in the field names to correctly save the data
		$key = $field['name'];
		
		// Create Field Options HTML
		?>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e("Preview",'acf'); ?></label>
				<p class="description"><?php _e("Preview type to display in the dashboard.",'acf'); ?></p>
			</td>
			<td>
				<?php

				do_action('acf/create_field', array(
					'type'    =>  'radio',
					'name'    =>  'fields[' . $key . '][preview_type]',
					'value'   =>  $field['preview_type'],
					'layout'  =>  'horizontal',
					'choices' =>  array(
						'embed' => __('Embed'),
						'thumbnail' => __('Thumbnail'),
						'none' => __('None')
					)
				));

				?>
			</td>
		</tr>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e("Return Value",'acf'); ?></label>
				<p class="description"><?php _e("Specify the returned value on front end.",'acf'); ?></p>
			</td>
			<td>
				<?php

				do_action('acf/create_field', array(
					'type'    =>  'radio',
					'name'    =>  'fields[' . $key . '][return_value]',
					'value'   =>  $field['return_value'],
					'layout'  =>  'horizontal',
					'choices' =>  array(
						'url' => __('Video URL'),
						'embed' => __('Embed Code'),
						'thumbnail' => __('Thumbnail URL'),
						'array' => __('Array')
					)
				));

				?>
			</td>
		</tr> 
		<?php
		
	}
	
	
	/*
	*  create_field()
	*
	*/
	function create_field( $field )
	{

		global $post;

		// defaults?
		$field = array_merge($this->defaults, $field);
		
		if ( !empty( $field['value'] ) && ( $field['preview_type'] == 'thumbnail' ) )
			$thumbnail = self::get_video_thumbnail_uri( $field['value'] );

		if ( !empty ( $field['value'] ) && ( $field['preview_type'] == 'embed' ) ) 
			$embed = wp_oembed_get( $field['value'] );
		
		?>

		<?php if ( !empty( $thumbnail ) ) { ?>
		<a href="<?php echo $field['value']; ?>" title="" target="_blank" style="display:block;padding-top:4px;margin-bottom:10px;" ><img src='<?php echo $thumbnail; ?>' alt='' style="display:block;width:100%;" /></a>
		<?php } ?>

		<?php if ( !empty( $embed ) ) { 
			echo '<div class="acf-video-field-embed" style="margin-bottom:10px;">' . $embed . '</div>';
		} ?>

		<input type="text" id="<?php echo $field['key']; ?>" class="<?php echo $field['class']; ?>" name="<?php echo $field['name']; ?>" value="<?php echo $field['value']; ?>" />
		<?php
	}

	/*
	*  format_value_for_api()
	*
	*/
	function format_value_for_api($value, $post_id, $field)
	{

		if ( !empty( $value ) && ( $field['return_value'] == 'thumbnail' ) )
			$updated_value = self::get_video_thumbnail_uri( $value );

		if ( !empty ( $value ) && ( $field['return_value'] == 'embed' ) ) 
			$updated_value = wp_oembed_get( $value );

		if ( !empty ( $value ) && ( $field['return_value'] == 'array' ) ) {
			$updated_value = array(
				'url' => $value,
				'embed' => wp_oembed_get( $value ),
				'thumbnail' => self::get_video_thumbnail_uri( $value )
			);
		}

		if ( !empty ( $updated_value ) )
			return $updated_value;
		else
			return $value;

	}
	
	
	/*
	*  input_admin_enqueue_scripts()
	*
	*/
	function input_admin_enqueue_scripts()
	{
		
		// register scripts
		wp_register_script( 'jquery-fitvids', $this->settings['dir'] . 'js/jquery.fitvids.js', array('jquery'), $this->settings['version'] );
		wp_register_script( 'acf-input-video', $this->settings['dir'] . 'js/input.js', array('acf-input', 'jquery-fitvids'), $this->settings['version'] );
		
		// enqueu scripts
		wp_enqueue_script(array(
			'jquery-fitvids',	
		));
		wp_enqueue_script(array(
			'acf-input-video',	
		));
		
	}

	/**
	 * Get the video thumbnail
	 */
	function get_video_thumbnail_uri( $video_uri ) {
	
		$thumbnail_uri = '';
		
		// determine the type of video and the video id
		$video = self::parse_video_uri( $video_uri );
		
		// get youtube thumbnail
		if ( $video['type'] == 'youtube' )
			$thumbnail_uri = 'http://img.youtube.com/vi/' . $video['id'] . '/hqdefault.jpg';
		
		// get vimeo thumbnail
		if( $video['type'] == 'vimeo' )
			$thumbnail_uri = self::get_vimeo_thumbnail_uri( $video['id'] );

		// get wistia thumbnail
		if( $video['type'] == 'wistia' )
			$thumbnail_uri = self::get_wistia_thumbnail_uri( $video_uri );

		// get default/placeholder thumbnail
		if( empty( $thumbnail_uri ) || is_wp_error( $thumbnail_uri ) )
			$thumbnail_uri = ''; 
		
		//return thumbnail uri
		return $thumbnail_uri;
		
	}

	/**
	 * Parse the video uri/url to determine the video type/source and the video id
	 */
	function parse_video_uri( $url ) {
		
		// Parse the url 
		$parse = parse_url( $url );
		
		// Set blank variables
		$video_type = '';
		$video_id = '';
		
		// Url is http://youtu.be/xxxx
		if ( $parse['host'] == 'youtu.be' ) {
		
			$video_type = 'youtube';
			
			$video_id = ltrim( $parse['path'],'/' );	
			
		}
		
		// Url is http://www.youtube.com/watch?v=xxxx 
		// or http://www.youtube.com/watch?feature=player_embedded&v=xxx
		// or http://www.youtube.com/embed/xxxx
		if ( ( $parse['host'] == 'youtube.com' ) || ( $parse['host'] == 'www.youtube.com' ) ) {
		
			$video_type = 'youtube';
			
			parse_str( $parse['query'] );
			
			$video_id = $v;	
			
			if ( !empty( $feature ) )
				$video_id = end( explode( 'v=', $parse['query'] ) );
				
			if ( strpos( $parse['path'], 'embed' ) == 1 )
				$video_id = end( explode( '/', $parse['path'] ) );
			
		}
		
		// Url is http://www.vimeo.com
		if ( ( $parse['host'] == 'vimeo.com' ) || ( $parse['host'] == 'www.vimeo.com' ) ) {
		
			$video_type = 'vimeo';
			
			$video_id = ltrim( $parse['path'],'/' );	
						
		}

		$host_names = explode(".", $parse['host'] );

		$rebuild = ( ! empty( $host_names[1] ) ? $host_names[1] : '') . '.' . ( ! empty($host_names[2] ) ? $host_names[2] : '');

		// Url is an oembed url wistia.com
		if ( ( $rebuild == 'wistia.com' ) || ( $rebuild == 'wi.st.com' ) ) {
		
			$video_type = 'wistia';
				
			if ( strpos( $parse['path'], 'medias' ) == 1 )
					$video_id = end( explode( '/', $parse['path'] ) );
		
		}
		
		// If recognised type return video array
		if ( !empty( $video_type ) ) {
		
			$video_array = array(
				'type' => $video_type,
				'id' => $video_id
			);
		
			return $video_array;
			
		} else {
		
			return false;
			
		}
		
	}	


	/**
	 * Takes a Vimeo video/clip ID and calls the Vimeo API v2 to get the large thumbnail URL.
	 */
	function get_vimeo_thumbnail_uri( $clip_id ) {

		$vimeo_api_uri = 'http://vimeo.com/api/v2/video/' . $clip_id . '.php';

		$vimeo_response = wp_remote_get( $vimeo_api_uri );

		if( is_wp_error( $vimeo_response ) ) {
			return $vimeo_response;
		} else {
			$vimeo_response = unserialize( $vimeo_response['body'] );
			return $vimeo_response[0]['thumbnail_large'];
		}
		
	}

	/**
	 * Takes a wistia oembed url and gets the video thumbnail url.
	 */
	function get_wistia_thumbnail_uri( $video_uri ) {

		if ( empty($video_uri) )
			return false;

		$wistia_api_uri = 'http://fast.wistia.com/oembed?url=' . $video_uri;

		$wistia_response = wp_remote_get( $wistia_api_uri );

		if( is_wp_error( $wistia_response ) ) {
			return $wistia_response;
		} else {
			$wistia_response = json_decode( $wistia_response['body'], true );
			return $wistia_response['thumbnail_url'];
		}
		
	}

	
}


// create field
new acf_field_video();

?>