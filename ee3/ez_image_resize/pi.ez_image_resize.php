<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ez_image_resize Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Plugin
 * @author		Vim Interactive, Inc.
 * @link		http://viminteractive.com
 */

/** 
@last update	11/10/2011
**/

$plugin_info = array(
	'pi_name'		=> 'ez_image_resize',
	'pi_version'	=> '1.5.3',
	'pi_author'		=> 'Vim Interactive, Inc.',
	'pi_author_url'	=> 'http://viminteractive.com',
	'pi_description'=> 'EZ Image Resize brings the best features of CE Image into your text, textareas and wysiwyg field types. Allowing you to control image size, quality and much more inside of a multi-image field like WYGWAM.',
	'pi_usage'		=> ez_image_resize::usage()
);


class ez_image_resize {

	
	private $defaults;
	private $current_domain = '';
	private $memory_limit = 64;
	private $cache_dir = '/images/made/';
	private $remote_dir = '/images/remote/';
	
	    
	/**
	 * Constructor
	 */
	public function __construct()
	{
		
		$this->EE =& get_instance();

		$this->log( 'start __construct()' );

	}

	public function remove()
	{

		$this->log( 'start remove()' );

		$tagdata = $this->EE->TMPL->tagdata;
		
		$tagdata = preg_replace('#<img(.*?)>#i','',$tagdata);
		
		return $tagdata;

	}

	public function run()
	{

		$this->log( 'start run()' );
		
		//require the CE Image class
		$CE = PATH_THIRD .'ce_img/libraries/Ce_image.php';
		if (file_exists($CE)):
		
			require_once $CE;
		
			$this->log( 'loaded CE Image File' );

		else:
		
			die ("<p>The file <strong>$CE</strong> does not exist</p><p>Please visit <a href='http://www.causingeffect.com/software/ee/ce_img'>http://www.causingeffect.com/software/ee/ce_img</a> to grab your copy!</p>");
		
		endif;

		$max_width = $this->EE->TMPL->fetch_param('max_width', 800);
		$quality = $this->EE->TMPL->fetch_param('quality', 70);
		$DM = $this->EE->TMPL->fetch_param('delimiter', '`');

		// create defaults
		$defaults = array( 
			'width' => $max_width,
			'quality' => $quality,
			'save_type' => 'png'
			);

	//original link
		$link_array = '';
		if ($this->EE->TMPL->fetch_param('original_link'))
		{ 
			$link_array = explode("|",  $this->EE->TMPL->fetch_param('original_link', ''));
		}

		

	//base param
		$base = '';
		if ($this->EE->TMPL->fetch_param('base'))
		{ 
			$base = $this->EE->TMPL->fetch_param('base');
		}
		else if ( isset( $this->EE->config->_global_vars['ce_image_document_root'] ) && $this->EE->config->_global_vars['ce_image_document_root'] != '' ) //first check global array
		{
			$base = $this->EE->config->_global_vars['ce_image_document_root'];
		}
		else if ( $this->EE->config->item('ce_image_document_root') != '' ) //then check config
		{
			$base = $this->EE->config->item('ce_image_document_root');
		}

		$defaults['base'] = $base;
		unset( $base );

	//current_domain param
		$defaults['current_domain'] = $this->current_domain;
		if ( $this->EE->config->item('ce_image_current_domain') != FALSE) //attempt to get current domain from config
		{
			$defaults['current_domain'] = $this->EE->config->item('ce_image_current_domain');
		}

	//fallback_src param
		$defaults['fallback_src'] = $this->EE->TMPL->fetch_param('fallback_src');

	//made_regex - check global array and config
		$defaults['made_regex'] = ( isset( $this->EE->config->_global_vars['ce_image_made_regex'] ) && $this->EE->config->_global_vars['ce_image_made_regex'] != '' ) ? $this->EE->config->_global_vars['ce_image_made_regex'] : $this->EE->config->item('ce_image_made_regex');

	//memory_limit
		if ( $this->EE->config->item('ce_image_memory_limit') != '' )
		{
			$this->memory_limit = $this->EE->config->item('ce_image_memory_limit');
		}
		$defaults['memory_limit'] = $this->memory_limit;

	//remote_dir param
		if ( $this->EE->TMPL->fetch_param('remote_dir') != '' )
		{
			$this->remote_dir = $this->EE->TMPL->fetch_param('remote_dir');
		}
		else if ( $this->EE->config->item('ce_image_remote_dir') != FALSE)
		{
			$this->remote_dir = $this->EE->config->item('ce_image_remote_dir');
		}
		$defaults['remote_dir'] = $this->remote_dir;

	//src_regex param - check global array and config
		$defaults['src_regex'] = ( isset( $this->EE->config->_global_vars['ce_image_src_regex'] ) && $this->EE->config->_global_vars['ce_image_src_regex'] != '' ) ? $this->EE->config->_global_vars['ce_image_src_regex'] : $this->EE->config->item('ce_image_src_regex');

		

	//save_type param
		$defaults['save_type'] = $this->EE->TMPL->fetch_param('save_type');

	//rounded_corners param
		$corners = trim( $this->EE->TMPL->fetch_param('rounded_corners') );
		if ( $corners != '' )
		{
			$corners = explode( '|', $corners );
			foreach ( $corners as $index => $corner )
			{
				$corners[$index] = explode( ',', $corner );
			}
		}
		else
		{
			$corners = FALSE;
		}
		$defaults['rounded_corners'] = $corners;
		unset( $corners );

	//bg_color param
		$defaults['bg_color'] = $this->EE->TMPL->fetch_param('bg_color');

	//rotate param
		$defaults['rotate'] = strtolower( $this->EE->TMPL->fetch_param('rotate') );

	//border param
		$border = trim( $this->EE->TMPL->fetch_param('border') );
		if ( $border != '' )
		{
			$border = explode( '|', $border );
		}
		$defaults['border'] = $border;
		unset( $border );

	//crop param
		$crop = strtolower( $this->EE->TMPL->fetch_param('crop') );
		if ( $crop == '' ||  $crop[0] == 'n' || $crop[0] == 'o'  )
		{
			$crop = FALSE;
		}
		else
		{
			//test just to make sure
			$crop = explode( '|', $crop );
			if ( $crop[0] == "yes" || $crop[0] == "y" || $crop[0] == "on" )
			{
				$crop[0] = TRUE;

				$crop_count = count( $crop );

				//positions
				if ( $crop_count > 1 )
				{
					$crop[1] = explode( ',', $crop[1] );
				}

				//offsets
				if ( $crop_count > 2 )
				{
					$crop[2] = explode( ',', $crop[2] );
				}

				//smart scale
				if ( $crop_count > 3 )
				{
					$crop[3] = ($crop[3] == "no" || $crop[3] == "n" || $crop[3] == "off" ) ? FALSE : TRUE;
				}

			}
			else
			{
				$crop[0] = FALSE;
			}
		}
		$defaults['crop'] = $crop;
		unset( $crop );

	//filter param
		$filter = trim( $this->EE->TMPL->fetch_param('filter') );
		$filters = array();
		if ( $filter != '' )
		{
			$filter = explode("|", trim( $filter, '|' ) );

			foreach( $filter as $f )
			{
				$filters[] = explode( ",", $f ); //trim( $f, ',' ) );
			}
		}
		$defaults['filters'] = $filters;
		unset( $filter );
		unset( $filters );

	//cache_dir param
		if ( $this->EE->TMPL->fetch_param('cache_dir') !== FALSE )
		{
			$this->cache_dir = $this->EE->TMPL->fetch_param('cache_dir');
		}
		else if ( $this->EE->config->item('ce_image_cache_dir') != FALSE)
		{
			$this->cache_dir = $this->EE->config->item('ce_image_cache_dir');
		}
		$defaults['cache_dir'] = $this->cache_dir;
		unset( $this->cache_dir );

	// start
		
		$tagdata = $this->EE->TMPL->tagdata;
		
		$img_count = preg_match_all('#<img(.*?)>#i',$tagdata,$imgs);
		
		// check if any image tags are found	
		if($img_count > 0) :

			$this->log( "found $img_count images" );
		
			// loop thru all images
			foreach ($imgs[0] as $img):
				
				$size = $max_width;
				
				//instantiate the ce_img class
				$ce_image = new Ce_image( $defaults );

				$this->log( 'loaded CE Img class' );

				$this->log( $defaults );
				
				$src_count = preg_match($DM.'src=[\"|\'](.*?)[\"|\']'.$DM.'i',$img,$src);

				$style_count = preg_match($DM.'style=[\"|\'](.*?)[\"|\']'.$DM.'i',$img,$style);
				
				if($src_count > 0):
					
					// check for inline style constraints and match image to style 
					if($style_count > 0):
					
					$styles = $this->get_styles($style[1]);
						 
						$w_c = preg_match($DM.'width:(.*?)(px|%|in|cm|mm|em|ex|pt|pc);'.$DM.'i',$img,$w);
						$h_c = preg_match($DM.'height:(.*?)(px|%|in|cm|mm|em|ex|pt|pc);'.$DM.'i',$img,$h);
						
						// check if size is smaller then max_width / image size
						if ($w_c > 0):

							if(trim($w[1]) < $size):
							
								$size = trim($w[1]);
							
							endif;
						
						endif;
						
					endif;
					
					$width_count = preg_match($DM.'width=[\"|\'](.*?)[\"|\']'.$DM.'i',$img,$width);
					$height_count = preg_match($DM.'height=[\"|\'](.*?)[\"|\']'.$DM.'i',$img,$height);	
					
					// check for inline width/height constraints and match image to style 
					if($width_count > 0):
						 
						if($width[1] < $size) $size = trim($width[1]);
			
					endif;	
																				
					// make new image with size and quality limitations
					if($ce_image->make( $src[1] , array('width' => $size) ) ):
					
						$this->log( "making new image {$src[1]}" );

						$styles['width'] = $ce_image->get_width();
						$styles['height'] = $ce_image->get_height();
										
						// add link if original_link is requested
						if (isset($link_array[0]) && $link_array[0] === 'yes'):
							
							$class = '';
							if (isset($link_array[1])) $class='class="'.$link_array[1].'"';

							$replace = '<a href="'.$src[1].'" '.$class.'>'.$img.'</a>';
						
							$tagdata = $this->str_replace_once($img, $replace,$tagdata);						

						endif;						
												
						// replace width/height data if found	
						if($width_count > 0):
							
							$tagdata = $this->str_replace_once($width[0], "width=\"{$styles['width']}\"",$tagdata);
							
						endif;
						
						if($height_count > 0):
							
							//if($width[1] < $size) $size = trim($width[1]);
							//echo $height[0], $styles['height'];
							$tagdata = $this->str_replace_once($height[0], "height=\"{$styles['height']}\"",$tagdata);
							
						endif;
						
						// replace style data if found					
						if($style_count > 0):	

							$tagdata = $this->str_replace_once($style[1], $this->set_styles($styles),$tagdata);
						
						endif;
						
						// replace old image location with new resized img location
						$tagdata = $this->str_replace_once('src="'.$src[1], 'src="'.$ce_image->get_relative_path(),$tagdata);
												

					else:

						$this->log( $ce_image->get_debug_messages() );
						
					endif;	
													
				endif;	

			endforeach;
		
		endif;	
				
		return $tagdata;
		
	}	
	// ----------------------------------------------------------------
	
	/**
	 * helper function - str_replace only once
	 */
	private function str_replace_once($str_pattern, $str_replacement, $string)
	{
		if (strpos($string, $str_pattern) !== false):
			
			$occurrence = strpos($string, $str_pattern);
			
			return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));

		endif;
	
		return $string;
	}
	// ------------------------------------------------------------------------
	
	/**
	 * helper function - create style string from array
	 */
	private function set_styles($arr)
	{
		
		$style = '';
		
		foreach($arr as $key => $val):
		
			$style .= "$key:$val; ";
		
		endforeach;
		
		return $style;		
		
    }	
	// ------------------------------------------------------------------------
	
	/**
	 * helper function - split style string to array
	 */
	private function get_styles($str)
	{
		$arr = explode(';',$str);
			
		foreach ($arr as $item):
		
			if(trim($item) == '') continue;
			
				$exp = explode(':',trim($item));
				$key = trim($exp[0]);
				$val = trim($exp[1]);
				$style[$key] = $val;
			
		//	endif;
		
		endforeach;
		
		return $style;		
		
    }	
	// ------------------------------------------------------------------------
	

	/**
	 * helper function - get new size keeping aspect ratio
	 */
	private function get_image_sizes($sourceImageFilePath, $maxResizeWidth, $maxResizeHeight) 
	{
		// Get width and height of original image
		$size = getimagesize($sourceImageFilePath);
		if($size === FALSE) return FALSE; // Error
		$origWidth = $size[0];
		$origHeight = $size[1];

		// Change dimensions to fit maximum width and height
		$resizedWidth = $origWidth;
		$resizedHeight = $origHeight;
		if($resizedWidth > $maxResizeWidth) {
			$aspectRatio = $maxResizeWidth / $resizedWidth;
			$resizedWidth = round($aspectRatio * $resizedWidth);
			$resizedHeight = round($aspectRatio * $resizedHeight);
		}
		if($resizedHeight > $maxResizeHeight) {
			$aspectRatio = $maxResizeHeight / $resizedHeight;
			$resizedWidth = round($aspectRatio * $resizedWidth);
			$resizedHeight = round($aspectRatio * $resizedHeight);
		}

		// Return an array with the original and resized dimensions
		return array($origWidth, $origHeight, $resizedWidth, $resizedHeight);
	}
	// ------------------------------------------------------------------------

	/**
	 * Simple method to log an array of debug messages to the EE Debug console.
	 * 
	 * @param array $messages The debug messages.
	 * @return void
	 */
	private function log( $message )
	{
		if ($this->EE->TMPL->fetch_param('debug') == 'yes' )
		{ 

			if ( is_array($message) ) 
			{
				$temp = '';
				foreach ( $message as $k => $v )
				{
					$temp .= " $k:$v;";
				}
				$message = $temp ;
			}

			$this->EE->TMPL->log_item( '&nbsp;------&nbsp; Ez debug: ' . $message );
		}
	}
	// ------------------------------------------------------------------------

	/**
	 * Plugin Usage
	 */
	public static function usage()
	{
		ob_start();
?>

	Tags

	{exp:ez_image_resize:run max_width="600" quality="100"}

		{content_field}

	{/exp:ez_image_resize:run}

<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}


/* End of file pi.ez_image_resize.php */
/* Location: /system/expressionengine/third_party/ez_image_resize/pi.ez_image_resize.php */
