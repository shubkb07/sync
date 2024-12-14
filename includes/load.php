<?php
if ( function_exists( 'error_reporting' ) ) {
	error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
}

define ('CLASSES', INC_DIR . 'classes/');
define ('FUNCTIONS', INC_DIR . 'functions/');
define ('LIB', INC_DIR . 'lib/');

require_once INC_DIR . 'define.php';

if ( file_exists( ABSPATH . 'sync-config.php' ) ) {
	require_once ABSPATH . 'sync-config.php';
	require_once INC_DIR . 'config.php';
	require_once INC_DIR . 'load-functions.php';
	require_once INC_DIR . 'default-filters.php';

	if ( 'static' !== $_SERVER['ACCESS'] && defined( 'SITE_STATUS' ) && 'PRESETUP' === SITE_STATUS ) {
		define( 'INSTALLING', true );
		require_once ADMIN_DIR . 'setup/presetup.php';
		die();
	} elseif ( 'static' !== $_SERVER['ACCESS'] ) {
		sync_die('Please Define `SITE_STATUS` in `sync-config.php` before starting fun development.');
	}
} elseif ( 'static' !== $_SERVER['ACCESS'] ) {
	require_once ADMIN_DIR . 'setup/config.php';
	die();
}


/**
 * Static handler function to manage static file requests, image manipulations,
 * minification, and cache headers based on file extension and GET parameters.
 *
 * @param string $output The content of the requested file.
 * @param string $extention The extension of the requested file.
 * @return string The modified output (e.g., minified or processed image).
 */
function static_handler($path) {
	$extention = pathinfo($path, PATHINFO_EXTENSION);
    $mime = get_mime_types()[$extention];

    // Set content type header for all
    header('Content-Type: ' . $mime);

	$type_array = array(
		'web' => array('html', 'htm', 'css', 'js', 'json', 'xml'),
		'js' => array('js'),
		'image' => array(
			'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 
			'tiff', 'tif', 'webp', 'avif', 'ico', 'heic'
		),
		'video' => array(
			'asf', 'asx', 'wmv', 'wmx', 'wm', 'avi', 
			'divx', 'flv', 'mov', 'qt', 'mpeg', 'mpg', 
			'mpe', 'mp4', 'm4v', 'ogv', 'webm', 'mkv', 
			'3gp', '3gpp', '3g2', '3gp2'
		),
		'text' => array(
			'txt', 'asc', 'c', 'cc', 'h', 'srt', 
			'csv', 'tsv', 'ics', 'rtx', 'htm', 'html', 
			'vtt', 'dfxp'
		),
		'audio' => array(
			'mp3', 'm4a', 'm4b', 'aac', 'ra', 'ram', 
			'wav', 'ogg', 'oga', 'flac', 'mid', 'midi', 
			'wma', 'wax', 'mka'
		),
		'document' => array(
			'rtf', 'pdf', 'doc', 'pot', 'pps', 'ppt', 
			'wri', 'xla', 'xls', 'xlt', 'xlw', 'mdb', 
			'mpp', 'docx', 'docm', 'dotx', 'dotm', 
			'xlsx', 'xlsm', 'xlsb', 'xltx', 'xltm', 
			'xlam', 'pptx', 'pptm', 'ppsx', 'ppsm', 
			'potx', 'potm', 'ppam', 'sldx', 'sldm'
		),
		'compressed' => array(
			'tar', 'zip', 'gz', 'gzip', 'rar', '7z'
		),
		'executable' => array(
			'exe', 'class'
		),
		'design' => array(
			'psd', 'xcf'
		),
		'font' => array(
			'ttf', 'otf', 'woff', 'woff2'
		),
		'spreadsheet' => array(
			'xls', 'xlsx', 'csv'
		),
		'presentation' => array(
			'ppt', 'pptx', 'key', 'odp'
		),
		'other_office' => array(
			'onetoc', 'onetoc2', 'onetmp', 'onepkg', 
			'oxps', 'xps', 'odt', 'odp', 'ods', 'odg', 
			'odc', 'odb', 'odf', 'wp', 'wpd', 
			'key', 'numbers', 'pages'
		)
	);

	// Get Type of file.
	$file_type = array_key_first(
        array_filter(
            $type_array, 
            fn($values) => in_array($extention, array_map('strtolower', $values))
        )
    ) ?? null;

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

	if ( defined('ENVIRONMENT_TYPE') && 'production' !== ENVIRONMENT_TYPE && '0' !== $cache_time ) {
		if ( ! defined('FORCE_DEV_CACHE') || NO_CACHE !== true ) {
			$cache_time = '3600';
		}
	}

	if ( isset( $_GET['cache_time'] ) && is_numeric( $_GET['cache_time'] ) && (string)(int)$_GET['cache_time'] === (string)$_GET['cache_time'] ) {
		$cache_time = $_GET['cache_time'];
	}

	if (in_array($file_type, array('web', 'image')) && filesize($path) < 10485760) {

		// Get the file data.
		$output = file_get_contents( $path );

		if (isset($_GET['minify']) && $_GET['minify'] === '' && in_array($extention, array('html', 'css', 'js', 'xml'))) {
			$minifier = new Minify();
			$output = $minifier->minify($output,$extention);
		}

		$image_args = array(
			'w',
			'h',
			'max',
			'crop',
			'grayscale',
		);

		$image_optimizer_supported = array('jpeg', 'jpg', 'png', 'gif', 'avif', 'webp');

		if (array_intersect_key(array_flip($image_args), $_GET) && in_array($extention, $image_optimizer_supported)) {

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
			if (isset($_GET['reduce_bit_depth']) && is_numeric($_GET['reduce_bit_depth']) && (int)$_GET['reduce_bit_depth'] >= 2 && (int)$_GET['reduce_bit_depth'] <= 10000 && ! in_array($extention, array('webp'))) {
				$image_optimizer->reduce_bit_depth((int)$_GET['reduce_bit_depth']);
			}

			// Remove metadata
			if (isset($_GET['metadata']) && $_GET['metadata'] === 'none') {
				$image_optimizer->remove_metadata();
			}

			// Compress the image
			if (isset($_GET['compress']) && $_GET['compress'] === 'true') {
				if (in_array($extention, ['jpg', 'jpeg'])) {
					$image_optimizer->lossy_compress();
				} elseif ($extention === 'png') {
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
	


		if (isset($_GET['download'])) {
			$fileName = basename($path);
			$fileSize = strlen($output); // Get the size of the file from $output
			header('Content-Disposition: attachment; filename="' . $fileName . '"');
			header('Content-Length: ' . $fileSize); // Set content length for the browser
		
			header('Accept-Ranges: bytes');
			header('Cache-Control: private, max-age=0, must-revalidate');
		}

		echo $output;
		exit;
	}

	if (isset($_GET['download'])) {
		set_time_limit(0);
        $download = new Download($path);
        $download->process();
	}
}

if ( 'static' === $_SERVER['ACCESS'] ) {

	if ( ! file_exists( ABSPATH . 'sync-config.php' ) ) {
		require_once FUNCTIONS . 'static-preinstall.php';
	}
	static_handler($_SERVER['REQUEST_PATH']);
}
