<?php

/**
 * Get Cache Time.
 *
 * @param string $file_type Type of File.
 *
 * @return 
 */
function get_cache_time($file_type) {
	$cache_time = '0';

	// Cache Time header handling.
	if ( ( ! defined( 'NO_CACHE' ) || NO_CACHE !== true ) && ( ! isset( $_GET['download'] ) || $_GET['download'] !== '' ) ) {
		$cache_max_age_option = in_array($file_type, array('css', 'js', 'image', 'font')) ? get_option('cache_max_age_' . $file_type) : false;
		if ( $cache_max_age_option && (string)(int)$cache_max_age_option === (string)$cache_max_age_option ) {
			$cache_time = $cache_max_age_option;
		} else {
			$cache_max_age_option = get_option('cache_max_age');
			if ( $cache_max_age_option && (string)(int)$cache_max_age_option === (string)$cache_max_age_option ) {
				$cache_time = $cache_max_age_option;
			} else {
				$cache_time = '3600';
			}
		}
	}

	if ( defined('ENVIRONMENT_TYPE') && 'production' !== ENVIRONMENT_TYPE && '3600' !== $cache_time ) {
		if ( ! defined('FORCE_DEV_CACHE') || FORCE_DEV_CACHE !== true ) {
			$cache_time = '3600';
		}
	}

	if ( isset( $_GET['cache_time'] ) && is_numeric( $_GET['cache_time'] ) && (string)(int)$_GET['cache_time'] === (string)$_GET['cache_time'] ) {
		$cache_time = $_GET['cache_time'];
	}

	return $cache_time;
}

/**
 * Get File Type.
 *
 * @param string $extension File Extension.
 *
 * @return string Group Name.
 */
function get_file_type($extension) {
	$type_array = array(
		'css' => array('css'),
		'js' => array('js'),
		'data' => array('csv', 'txt', 'dat', 'json', 'xml'),
		'image' => array('jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tiff', 'tif', 'webp', 'avif', 'ico', 'heic'),
		'video' => array(
			'asf', 'asx', 'wmv', 'wmx', 'wm', 'avi', 'divx', 'flv', 'mov', 'qt', 'mpeg', 'mpg', 
			'mpe', 'mp4', 'm4v', 'ogv', 'webm', 'mkv', '3gp', '3gpp', '3g2', '3gp2'
		),
		'text' => array('txt', 'asc', 'c', 'cc', 'h', 'srt', 'csv', 'tsv', 'ics', 'rtx', 'htm', 'html', 'vtt', 'dfxp'),
		'audio' => array('mp3', 'm4a', 'm4b', 'aac', 'ra', 'ram', 'wav', 'ogg', 'oga', 'flac', 'mid', 'midi', 'wma', 'wax', 'mka'),
		'document' => array(
			'rtf', 'pdf', 'doc', 'pot', 'pps', 'ppt', 'wri', 'xla', 'xls', 'xlt', 'xlw', 'mdb', 
			'mpp', 'docx', 'docm', 'dotx', 'dotm', 'xlsx', 'xlsm', 'xlsb', 'xltx', 'xltm', 
			'xlam', 'pptx', 'pptm', 'ppsx', 'ppsm', 'potx', 'potm', 'ppam', 'sldx', 'sldm'
		),
		'compressed' => array('tar', 'zip', 'gz', 'gzip', 'rar', '7z'),
		'executable' => array('exe', 'class'),
		'design' => array('psd', 'xcf'),
		'font' => array('ttf', 'otf', 'woff', 'woff2'),
		'spreadsheet' => array('xls', 'xlsx', 'csv'),
		'presentation' => array('ppt', 'pptx', 'key', 'odp'),
		'other_office' => array(
			'onetoc', 'onetoc2', 'onetmp', 'onepkg', 'oxps', 'xps', 'odt', 'odp', 'ods', 'odg', 
			'odc', 'odb', 'odf', 'wp', 'wpd', 'key', 'numbers', 'pages'
		)
	);

	// Get Type of file.
	return array_key_first(
        array_filter(
            $type_array,
            fn($values) => in_array($extension, array_map('strtolower', $values))
        )
    ) ?? null;
}

/**
 * Minification and Optimizations.
 *
 * @return string Output.
 */
function minify_and_optimize($path, $extension) {

	// Get the file data.
	$output = file_get_contents( $path );
	if ( false === $output ) {
		status_header( 404, 'File does not exist' );
		exit;
	}


	if (isset($_GET['minify']) && $_GET['minify'] === '' && in_array($extension, array('html', 'htm', 'css', 'js', 'xml'))) {
		$minifier = new Minify();
		$output = $minifier->minify($output,$extension);
	}

	$image_args = array(
		'w',
		'h',
		'max',
		'crop',
		'grayscale',
	);

	$image_optimizer_supported = array('jpeg', 'jpg', 'png', 'gif', 'avif', 'webp');

	if (array_intersect_key(array_flip($image_args), $_GET) && in_array($extension, $image_optimizer_supported)) {

		$image_optimizer = new ImageOptimizer($output);

		// Resize image based on 'w' and 'h'.
		if (isset($_GET['w']) || isset($_GET['h'])) {
			$dimensions = [];
			if (isset($_GET['w'])) $dimensions['w'] = (int)$_GET['w'];
			if (isset($_GET['h'])) $dimensions['h'] = (int)$_GET['h'];
			$image_optimizer->resize($dimensions);
		}

		// Create thumbnail with max dimension
		if (isset($_GET['max']) && is_numeric($_GET['max']) && (int)$_GET['max'] <= 10000) {
			$image_optimizer->create_thumbnail((int)$_GET['max']);
		}

		// Crop image if 'crop' parameter is set
		if (isset($_GET['crop'])) {
			$crop_values = explode(',', $_GET['crop']);
			if (count($crop_values) === 2 && is_numeric($crop_values[0]) && is_numeric($crop_values[1])) {
				$crop_width = (int)$crop_values[0];
				$crop_height = (int)$crop_values[1];
				$image_optimizer->smart_crop($crop_width, $crop_height);
			} elseif (count($crop_values) === 1 && is_numeric($crop_values[0])) {
				$crop_width = (int)$crop_values[0];
				$crop_height = (int)$crop_values[0];
				$image_optimizer->smart_crop($crop_width, $crop_height);
			}
		}

		// Convert image to grayscale
		if (isset($_GET['grayscale']) && $_GET['grayscale'] === '') {
			$image_optimizer->to_grayscale();
		}

		// Reduce bit depth
		if (isset($_GET['reduce_bit_depth']) && is_numeric($_GET['reduce_bit_depth']) && (int)$_GET['reduce_bit_depth'] >= 2 && (int)$_GET['reduce_bit_depth'] <= 10000 && ! in_array($extension, array('webp'))) {
			$image_optimizer->reduce_bit_depth((int)$_GET['reduce_bit_depth']);
		}

		// Remove metadata
		if (isset($_GET['metadata']) && $_GET['metadata'] === 'none') {
			$image_optimizer->remove_metadata();
		}

		// Compress the image
		if (isset($_GET['compress']) && $_GET['compress'] === 'true') {
			if (in_array($extension, ['jpg', 'jpeg'])) {
				$image_optimizer->lossy_compress();
			} elseif ($extension === 'png') {
				$image_optimizer->lossless_compress();
			}
		}

		// Convert format
		if (isset($_GET['convert']) && in_array($_GET['convert'], $image_optimizer_supported)) {
			$image_optimizer->convert_format($_GET['convert']);
		}

		// Rotate the image
		if (isset($_GET['rotate']) && is_numeric($_GET['rotate']) && (int)$_GET['rotate'] >= 0 && (int)$_GET['rotate'] <= 360) {
			$image_optimizer->rotate((int)$_GET['rotate']);
		}

		// Flip the image
		if (isset($_GET['flip']) && in_array($_GET['flip'], ['h', 'v', 'hv'])) {
			$flip_values = array(
				'h' => 'horizontal',
				'v' => 'vertical',
				'hv' => 'both',
			);
			$image_optimizer->flip($flip_values[$_GET['flip']]);
		}

		// Enhance sharpness or edge
		if (isset($_GET['enhance'])) {
			$enhance_params = explode(',', $_GET['enhance']);
			if (in_array('sharpness', $enhance_params)) {
				$image_optimizer->enhance_sharpness();
			}
			if (in_array('edge', $enhance_params)) {
				$image_optimizer->edge_enhance();
			}
		}

		// Reduce noise in the image
		if (isset($_GET['reduce_noise']) && $_GET['reduce_noise'] === '') {
			$image_optimizer->reduce_noise();
		}

		// Output the final processed image
		$output = $image_optimizer->get_raw();
	}

	return $output;
}


/**
 * Static handler function to manage static file requests, image manipulations,
 * minification, and cache headers based on file extension and GET parameters.
 *
 * @param string $path The path of the requested file.
 */
function static_handler($path) {

	$path = ABSPATH . $path;

	$fileTypeInfo = check_filetype(basename($path), get_mime_types());
	$extension = $fileTypeInfo['ext'];
	$mime = $fileTypeInfo['type'];

	if ( strpos($path, '/./') !== false || strpos($path, '/../') !== false || !file_exists($path) || !is_file($path) || in_array($extension, array('', 'htm', 'html', 'php', 'env'), true)) {
		status_header( 404, 'File does not exist' );
		exit;
	}

	$file_type = get_file_type($extension);

	$output = '';

	$css_js_minify_limit = ($css_js_minify_limit = get_option('css_js_min_limit')) ? (int)$css_js_minify_limit : 1048576;
	$image_optimize_limit = ($image_optimize_limit = get_option('image_optimize_limit')) ? (int)$image_optimize_limit : 1048576;

	if ((in_array($file_type, array('css','js')) && filesize($path) < $css_js_minify_limit) || ($file_type === 'image' && filesize($path) < $image_optimize_limit)) {
		$output = minify_and_optimize($path, $extension);
	}

	$browser_display_supported = array(
	    'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp',
	    'tiff', 'tif', 'webp', 'avif', 'ico', 'heic',
	    'mp4', 'm4v', 'ogv', 'webm', 'txt', 'asc',
	    'c', 'cc', 'h', 'srt', 'csv', 'tsv',
	    'ics', 'rtx', 'css', 'htm', 'html', 'vtt',
	    'dfxp', 'js', 'pdf', 'mp3', 'm4a', 'm4b',
	    'aac', 'wav', 'ogg', 'oga', 'flac', 'svg',
	    'xml', 'json', 'webmanifest', 'wasm'
	);


	if (isset($_GET['download']) || !in_array($extension, $browser_display_supported)) {
		set_time_limit(0);
		if ('' === $output) {
    		$download = new Download($path);
		} else {
			$download = new Download($output,basename($path));
		}
        $download->process();
	} else {
		$cache_time = get_cache_time($file_type);
		if ('0' !== $cache_time) {
			$file_mtime = false === ($file_mtime = filemtime($path)) ? time() : $file_mtime;
			$cache_time  = (int) $cache_time;
			$expire_time = time() + $cache_time;
			$file_size  = filesize($path);
			header('Cache-Control: public, max-age=' . $cache_time . ', s-maxage=' . $cache_time . ', stale-while-revalidate=3600, stale-if-error=86400, immutable');
			header('Expires: ' . gmdate( 'D, d M Y H:i:s', $expire_time ) . ' GMT');
			header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $file_mtime ) . ' GMT');
			header('ETag: W/"' . dechex($file_mtime) . '-' . dechex($file_size) . '-' . md5($path) . '"');
			header('Vary: Accept-Encoding');
		}
		header('Content-Type: ' . $mime);
		if ('' === $output) {
			$fh = fopen( $path, 'r' );
			if ( false !== $fh ) {
			    while ( ! feof( $fh ) ) {
			        echo fread( $fh, 8192 );
			    }
			    fclose( $fh );
			} else {
			    status_header( 404, 'File does not exist' );
				exit;
			}
		} else {
			echo $output;
		}
		exit();
	}
}