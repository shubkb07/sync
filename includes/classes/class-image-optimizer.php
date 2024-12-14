<?php
/**
 * ImageOptimizer class.
 *
 * A class providing image optimization, conversion, resizing, and metadata management capabilities.
 * The class uses PHP's built-in GD library and finfo for mime detection. It does not rely on external APIs
 * or third-party libraries.
 *
 * @package ImageOptimizer
 */
class ImageOptimizer {
    /**
     * @var string Raw binary data of the image
     */
    private $raw_data;

    /**
     * @var string Detected mime type of the image
     */
    private $mime_type;

    /**
     * @var string Current image extension (e.g. jpg, png, etc.)
     */
    private $extension;

    /**
     * @var GdImage|null GD image resource (if raster image)
     */
    private $image_resource;

    /**
     * @var bool Whether the image is a vector format (e.g., SVG)
     */
    private $is_vector;

    /**
     * @var array|null EXIF data of the image if available (JPEG only)
     */
    private $exif_data;

    /**
     * @var int Compression quality (0-100)
     */
    private $quality = 80;

    /**
     * @var string Original raw data to allow for reverting
     */
    private $original_raw_data;

    /**
     * @var string Original mime type to allow for reverting
     */
    private $original_mime_type;

    /**
     * @var string Original extension to allow for reverting
     */
    private $original_extension;

    /**
     * @var bool Whether the original image is a vector format
     */
    private $original_is_vector;

    /**
     * @var array|null Original EXIF data to allow for reverting
     */
    private $original_exif_data;

    /**
     * Constructor for the ImageOptimizer class.
     *
     * Initializes the object by loading image data, detecting mime type, extension,
     * and image resource. It also loads EXIF data for JPEG images if available.
     *
     * @param mixed $input The input image data, either as a file path/URL or raw binary data.
     * @throws Exception If the image cannot be read or processed.
     */
    public function __construct($input) {
        // Detect and load input
        if (is_string($input) && (file_exists($input) || stripos($input, 'http://') === 0 || stripos($input, 'https://') === 0)) {
            $this->raw_data = @file_get_contents($input);
            if ($this->raw_data === false) {
                throw new \Exception("Unable to read image from given path/URL.");
            }
        } else {
            // Assume it's already raw data
            $this->raw_data = $input;
        }

        if (!$this->raw_data) {
            throw new \Exception("No image data provided.");
        }

        // Detect mime type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $this->mime_type = finfo_buffer($finfo, $this->raw_data);
        finfo_close($finfo);

        // Deduce extension from mime type
        $this->extension = $this->mime_to_extension($this->mime_type);

        // Check if the image is a vector format (SVG)
        $this->is_vector = in_array($this->extension, ['svg']);

        // Load image resource if not vector
        $this->image_resource = null;
        $this->exif_data = null;

        if (!$this->is_vector) {
            $this->load_image_resource();
            if (!$this->image_resource) {
                throw new \Exception("Failed to create image resource from data.");
            }
            $this->load_exif_data();
        }

        // Store the original image data for revert functionality
        $this->original_raw_data = $this->raw_data;
        $this->original_mime_type = $this->mime_type;
        $this->original_extension = $this->extension;
        $this->original_is_vector = $this->is_vector;
        $this->original_exif_data = $this->exif_data;
    }

    /**
     * Convert mime type to file extension.
     *
     * Maps mime types to corresponding file extensions (e.g., image/jpeg to jpg).
     *
     * @param string $mime The mime type to convert.
     * @return string The corresponding file extension.
     */
    private function mime_to_extension($mime) {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/svg+xml' => 'svg',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
        ];
        return isset($map[$mime]) ? $map[$mime] : 'bin';
    }

    /**
     * Load image resource using GD library.
     *
     * This method attempts to create a GD image resource from the raw binary data.
     *
     * @return void
     */
    private function load_image_resource() {
        $im = @imagecreatefromstring($this->raw_data);
        $this->image_resource = $im ?: null;
    }

    /**
     * Load EXIF data for JPEG images (if available).
     *
     * This method loads the EXIF data if the image is a JPEG and the exif extension is available.
     *
     * @return void
     */
    private function load_exif_data() {
        if ($this->extension === 'jpg' && function_exists('exif_read_data')) {
            $temp = $this->create_temp_file($this->raw_data);
            if ($temp) {
                $this->exif_data = @exif_read_data($temp, 'ANY_TAG', true);
                @unlink($temp);
            }
        }
    }

    /**
     * Create a temporary file from image data.
     *
     * @param string $data Raw image data.
     * @return string|false The temporary file path or false if unable to create the file.
     */
    private function create_temp_file($data) {
        $temp = tempnam(sys_get_temp_dir(), 'imgopt_');
        if ($temp !== false) {
            file_put_contents($temp, $data);
            return $temp;
        }
        return false;
    }

    /**
     * Get the mime type of the image.
     *
     * @return string The mime type of the image (e.g., image/jpeg, image/png).
     */
    public function get_mime_type() {
        return $this->mime_type;
    }

    /**
     * Get the file extension of the image.
     *
     * @return string The file extension of the image (e.g., jpg, png, svg).
     */
    public function get_extention() {
        return $this->extension;
    }

    /**
     * Get the raw binary data of the image.
     *
     * @return string Raw image data.
     */
    public function get_raw() {
        return $this->raw_data;
    }

    /**
     * Save the current image data to a file.
     *
     * @param string $path The file path to save the image.
     * @return bool True if successful, false otherwise.
     */
    public function save_to_path($path) {
        return file_put_contents($path, $this->raw_data) !== false;
    }

    /**
     * Set the quality for image compression.
     *
     * @param int $q Quality level (0-100).
     */
    public function set_quality($q) {
        $q = (int)$q;
        if ($q < 0) $q = 0;
        if ($q > 100) $q = 100;
        $this->quality = $q;
    }

    /**
     * Apply lossy compression to the image.
     *
     * This method applies lossy compression for supported formats (e.g., JPEG).
     *
     * @return void
     */
    public function lossy_compress() {
        if ($this->is_vector || !$this->image_resource) return;
        $this->reencode_image($this->extension, $this->quality);
    }

    /**
     * Apply lossless compression to the image.
     *
     * This method attempts lossless compression for supported formats (e.g., PNG).
     *
     * @return void
     */
    public function lossless_compress() {
        if ($this->is_vector || !$this->image_resource) return;
        if ($this->extension === 'png') {
            $this->reencode_image('png', null, true);
        } else {
            // fallback: re-encode with current format/quality
            $this->reencode_image($this->extension, $this->quality);
        }
    }

    /**
     * Convert the image to a different format.
     *
     * @param string $new_ext The new image extension (e.g., jpg, png, webp).
     * @throws Exception If the format conversion is not supported.
     */
    public function convert_format($new_ext) {
        $new_ext = strtolower($new_ext);
        if ($this->is_vector && $new_ext !== 'svg') {
            throw new \Exception("SVG to raster conversion not supported.");
        }

        if (!$this->is_vector && !$this->image_resource) {
            throw new \Exception("No raster image resource available for conversion.");
        }

        $this->reencode_image($new_ext, $this->quality);
    }

    /**
     * Re-encode the image in the given format.
     *
     * @param string $format The image format (e.g., jpg, png, webp).
     * @param int $quality The quality level (0-100).
     * @param bool $lossless_png Whether to apply lossless compression for PNG.
     * @return void
     * @throws Exception If the re-encoding fails.
     */
    private function reencode_image($format, $quality = 80, $lossless_png = false) {
        if ($this->is_vector) {
            if ($format === 'svg') {
                $this->extension = 'svg';
                $this->mime_type = 'image/svg+xml';
            } else {
                throw new \Exception("Conversion from vector to this format not supported.");
            }
            return;
        }

        ob_start();
        switch ($format) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($this->image_resource, null, $quality);
                $this->mime_type = 'image/jpeg';
                $this->extension = 'jpg';
                break;
            case 'png':
                $png_compression = $lossless_png ? 9 : (int)(9 - ($quality/11));
                imagepng($this->image_resource, null, $png_compression);
                $this->mime_type = 'image/png';
                $this->extension = 'png';
                break;
            case 'gif':
                imagegif($this->image_resource);
                $this->mime_type = 'image/gif';
                $this->extension = 'gif';
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    imagewebp($this->image_resource, null, $quality);
                    $this->mime_type = 'image/webp';
                    $this->extension = 'webp';
                } else {
                    ob_end_clean();
                    throw new \Exception("WebP not supported on this server.");
                }
                break;
            case 'avif':
                if (function_exists('imageavif')) {
                    imageavif($this->image_resource, null, $quality);
                    $this->mime_type = 'image/avif';
                    $this->extension = 'avif';
                } else {
                    ob_end_clean();
                    throw new \Exception("AVIF not supported on this server.");
                }
                break;
            case 'ico':
                $png_compression = (int)(9 - ($quality/11));
                imagepng($this->image_resource, null, $png_compression);
                $this->mime_type = 'image/x-icon';
                $this->extension = 'ico';
                break;
            default:
                ob_end_clean();
                throw new \Exception("Unsupported format for re-encoding: $format");
        }
        $data = ob_get_clean();
        if ($data !== false) {
            $this->raw_data = $data;
        } else {
            throw new \Exception("Failed to re-encode image.");
        }
    }

    /**
     * Convert image to grayscale.
     *
     * @return void
     */
    public function to_grayscale() {
        if ($this->is_vector || !$this->image_resource) return;
        imagefilter($this->image_resource, IMG_FILTER_GRAYSCALE);
        $this->update_resource();
    }

    /**
     * Reduce bit depth (convert to palette).
     *
     * @param int $colors Number of colors in the palette.
     * @return void
     */
    public function reduce_bit_depth($colors = 256) {
        if ($this->is_vector || !$this->image_resource) return;
        imagetruecolortopalette($this->image_resource, false, $colors);
        $this->update_resource();
    }

    /**
     * Remove metadata (EXIF) from image.
     *
     * @return void
     */
    public function remove_metadata() {
        if ($this->is_vector) return;
        $this->reencode_image($this->extension, $this->quality);
        $this->exif_data = null;
    }

    /**
     * Resize the image, preserving aspect ratio if width or height is not provided, one is required.
     *
     * @param array $dimensions An array with 'w' for width and 'h' for height.
     * @return void
     */
    public function resize($dimensions) {
        // Check if either 'w' or 'h' is provided
        if (empty($dimensions['w']) && empty($dimensions['h'])) {
            throw new \Exception("Either width ('w') or height ('h') must be provided.");
        }
    
        if ($this->is_vector || !$this->image_resource) return;
    
        $width = imagesx($this->image_resource);
        $height = imagesy($this->image_resource);
    
        // Calculate the missing dimension if it's not provided
        if (isset($dimensions['w']) && !isset($dimensions['h'])) {
            // Calculate height based on width
            $ratio = $width / $height;
            $dimensions['h'] = (int)($dimensions['w'] / $ratio);
        } elseif (isset($dimensions['h']) && !isset($dimensions['w'])) {
            // Calculate width based on height
            $ratio = $width / $height;
            $dimensions['w'] = (int)($dimensions['h'] * $ratio);
        }
    
        // Perform the resize operation
        $new_im = imagecreatetruecolor($dimensions['w'], $dimensions['h']);
        imagecopyresampled($new_im, $this->image_resource, 0, 0, 0, 0, $dimensions['w'], $dimensions['h'], $width, $height);
        imagedestroy($this->image_resource);
        $this->image_resource = $new_im;
        $this->update_resource();
    }

    /**
     * Create a thumbnail with max dimension.
     *
     * @param int $max_dimension The maximum dimension of the thumbnail.
     * @return void
     */
    public function create_thumbnail($max_dimension) {
        if ($this->is_vector || !$this->image_resource) return;
        $width = imagesx($this->image_resource);
        $height = imagesy($this->image_resource);
        if ($width > $height) {
            $this->resize(array('w'=>$max_dimension));
        } else {
            $ratio = $height / $width;
            $this->resize(array('w'=>(int)($max_dimension / $ratio), 'h'=>$max_dimension));
        }
    }

    /**
     * Smart crop the image (center crop).
     *
     * @param int $crop_width The width of the cropped image.
     * @param int $crop_height The height of the cropped image.
     * @return void
     */
    public function smart_crop($crop_width, $crop_height) {
        if ($this->is_vector || !$this->image_resource) return;
        $width = imagesx($this->image_resource);
        $height = imagesy($this->image_resource);

        $x = max(0, ($width - $crop_width) / 2);
        $y = max(0, ($height - $crop_height) / 2);

        $new_im = imagecreatetruecolor($crop_width, $crop_height);
        imagecopyresampled($new_im, $this->image_resource, 0, 0, $x, $y, $crop_width, $crop_height, $crop_width, $crop_height);
        imagedestroy($this->image_resource);
        $this->image_resource = $new_im;
        $this->update_resource();
    }

    /**
     * Enhance sharpness of the image (approximate).
     *
     * @return void
     */
    public function enhance_sharpness() {
        if ($this->is_vector || !$this->image_resource) return;
        $matrix = [
            [ -1, -1, -1 ],
            [ -1, 16, -1 ],
            [ -1, -1, -1 ]
        ];
        $div = 8;
        $offset = 0;
        imageconvolution($this->image_resource, $matrix, $div, $offset);
        $this->update_resource();
    }

    /**
     * Reduce noise in the image (approximate via smoothing).
     *
     * @return void
     */
    public function reduce_noise() {
        if ($this->is_vector || !$this->image_resource) return;
        imagefilter($this->image_resource, IMG_FILTER_SMOOTH, 10);
        $this->update_resource();
    }

    /**
     * Adjust contrast of the image.
     *
     * @param int $level The contrast level (-100 to 100).
     * @return void
     */
    public function adjust_contrast($level) {
        if ($this->is_vector || !$this->image_resource) return;
        imagefilter($this->image_resource, IMG_FILTER_CONTRAST, $level);
        $this->update_resource();
    }

    /**
     * Adjust brightness of the image.
     *
     * @param int $level The brightness level (-255 to 255).
     * @return void
     */
    public function adjust_brightness($level) {
        if ($this->is_vector || !$this->image_resource) return;
        imagefilter($this->image_resource, IMG_FILTER_BRIGHTNESS, $level);
        $this->update_resource();
    }

    /**
     * Apply color balance to the image.
     *
     * @param int $red The red balance (-255 to 255).
     * @param int $green The green balance (-255 to 255).
     * @param int $blue The blue balance (-255 to 255).
     * @return void
     */
    public function color_balance($red, $green, $blue) {
        if ($this->is_vector || !$this->image_resource) return;
        imagefilter($this->image_resource, IMG_FILTER_COLORIZE, $red, $green, $blue);
        $this->update_resource();
    }

    /**
     * Apply gamma correction to the image.
     *
     * @param float $input_gamma The input gamma value.
     * @param float $output_gamma The output gamma value.
     * @return void
     */
    public function gamma_correction($input_gamma, $output_gamma) {
        if ($this->is_vector || !$this->image_resource) return;
        imagegammacorrect($this->image_resource, $input_gamma, $output_gamma);
        $this->update_resource();
    }

    /**
     * Apply edge enhancement (edge detect filter).
     *
     * @return void
     */
    public function edge_enhance() {
        if ($this->is_vector || !$this->image_resource) return;
        imagefilter($this->image_resource, IMG_FILTER_EDGEDETECT);
        $this->update_resource();
    }

    /**
     * Rotate the image by a given angle.
     *
     * @param int        $angle The angle to rotate (positive for counter-clockwise).
     * @param int|string $bgcolor The background color for empty areas (default is black), appect int,color name, hex, rgb() and rgba().
     * @return void
     */
    public function rotate($angle, $bgcolor = 0) {
        if ($this->is_vector || !$this->image_resource) return;
        $rotated = imagerotate($this->image_resource, $angle, $this->parse_color($bgcolor));
        if ($rotated) {
            imagedestroy($this->image_resource);
            $this->image_resource = $rotated;
            $this->update_resource();
        }
    }

	/**
	 * Parse the background color to a numeric value.
	 *
	 * This method converts various color formats (e.g., number, hex, rgb, rgba, color names)
	 * to a numeric value that can be used in imagerotate.
	 *
	 * @param mixed $color The color in any of the accepted formats.
	 * @return int The color as an integer.
	 */
	private function parse_color($color) {
	    // If it's already a number, return it directly
	    if (is_numeric($color)) {
	        return (int) $color;
	    }

	    // If it's a named color, convert it to RGB using imagecolorresolve
	    if (is_string($color)) {
	        // Check if it's a color name
	        $named_colors = array(
				'black' => 0x000000, 'white' => 0xFFFFFF, 'red' => 0xFF0000, 'green' => 0x00FF00,
				'blue' => 0x0000FF, 'yellow' => 0xFFFF00, 'cyan' => 0x00FFFF, 'magenta' => 0xFF00FF,
				'gray' => 0x808080, 'silver' => 0xC0C0C0, 'maroon' => 0x800000, 'olive' => 0x808000,
				'purple' => 0x800080, 'teal' => 0x008080, 'navy' => 0x000080, 'fuchsia' => 0xFF00FF,
				'aqua' => 0x00FFFF, 'lime' => 0x00FF00, 'orange' => 0xFFA500, 'pink' => 0xFFC0CB,
				'brown' => 0xA52A2A, 'beige' => 0xF5F5DC, 'ivory' => 0xFFFFF0, 'gold' => 0xFFD700,
				'khaki' => 0xF0E68C, 'plum' => 0xDDA0DD, 'orchid' => 0xDA70D6, 'coral' => 0xFF7F50,
				'salmon' => 0xFA8072, 'tomato' => 0xFF6347, 'peru' => 0xCD853F, 'chocolate' => 0xD2691E,
				'tan' => 0xD2B48C, 'wheat' => 0xF5DEB3, 'seashell' => 0xFFF5EE, 'antiquewhite' => 0xFAEBD7,
				'linen' => 0xFAF0E6, 'mintcream' => 0xF5FFFA, 'snow' => 0xFFFAFA, 'ghostwhite' => 0xF8F8FF,
				'whitesmoke' => 0xF5F5F5, 'lightgray' => 0xD3D3D3, 'darkgray' => 0xA9A9A9, 'lightblue' => 0xADD8E6,
				'lightgreen' => 0x90EE90, 'lightcoral' => 0xF08080, 'darkgreen' => 0x006400, 'darkblue' => 0x00008B,
				'darkred' => 0x8B0000, 'darkviolet' => 0x9400D3, 'darkorchid' => 0x9932CC, 'darkkhaki' => 0xBDB76B,
				'darkturquoise' => 0x00CED1, 'midnightblue' => 0x191970, 'slateblue' => 0x6A5ACD, 'slategray' => 0x708090,
				'forestgreen' => 0x228B22, 'seagreen' => 0x2E8B57, 'mediumslateblue' => 0x7B68EE, 'royalblue' => 0x4169E1,
				'dodgerblue' => 0x1E90FF, 'lightseagreen' => 0x20B2AA, 'mediumaquamarine' => 0x66CDAA, 'mediumseagreen' => 0x3CB371,
				'mediumturquoise' => 0x48D1CC, 'paleturquoise' => 0xAFEEEE, 'lightpink' => 0xFFB6C1, 'hotpink' => 0xFF69B4,
				'deeppink' => 0xFF1493, 'mediumvioletred' => 0xC71585, 'darkviolet' => 0x9400D3, 'indigo' => 0x4B0082,
				'purple' => 0x800080, 'violet' => 0xEE82EE, 'blueviolet' => 0x8A2BE2, 'mediumpurple' => 0x9370DB,
				'lavender' => 0xE6E6FA, 'thistle' => 0xD8BFD8, 'plum' => 0xDDA0DD, 'orchid' => 0xDA70D6,
				'darkorchid' => 0x9932CC, 'darkviolet' => 0x9400D3, 'purple' => 0x800080, 'indigo' => 0x4B0082
			);
			
	        if (array_key_exists(strtolower($color), $named_colors)) {
	            return $named_colors[strtolower($color)];
	        }

	        // Check for hex color
	        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
	            return hexdec(substr($color, 1)); // Convert hex to integer
	        }

	        // Check for rgb() or rgba() format
	        if (preg_match('/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/', $color, $matches)) {
	            return ($matches[1] << 16) | ($matches[2] << 8) | $matches[3]; // RGB to integer
	        }

	        // Check for rgba() format
	        if (preg_match('/^rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d\.]+)\)$/', $color, $matches)) {
	            $rgb = ($matches[1] << 16) | ($matches[2] << 8) | $matches[3]; // RGB to integer
	            $alpha = round($matches[4] * 127); // Convert alpha to an 8-bit value
	            return ($rgb | ($alpha << 24)); // Add alpha to the color integer
	        }
	    }

	    // Default to black if the color format is not recognized
	    return 0x000000; // Black color
	}

    /**
     * Flip the image horizontally, vertically, or both.
     *
     * @param string $mode The mode of flipping ('horizontal', 'vertical', 'both').
     * @return void
     */
    public function flip($mode = 'horizontal') {
        if ($this->is_vector || !$this->image_resource) return;
        $width = imagesx($this->image_resource);
        $height = imagesy($this->image_resource);
        $flipped = imagecreatetruecolor($width, $height);

        switch ($mode) {
            case 'vertical':
                for ($y = 0; $y < $height; $y++) {
                    imagecopy($flipped, $this->image_resource, 0, $height - $y - 1, 0, $y, $width, 1);
                }
                break;
            case 'both':
                for ($y = 0; $y < $height; $y++) {
                    imagecopy($flipped, $this->image_resource, 0, $height - $y - 1, 0, $y, $width, 1);
                }
                $temp = $flipped;
                $flipped = imagecreatetruecolor($width, $height);
                for ($x = 0; $x < $width; $x++) {
                    imagecopy($flipped, $temp, $width - $x - 1, 0, $x, 0, 1, $height);
                }
                imagedestroy($temp);
                break;
            case 'horizontal':
            default:
                for ($x = 0; $x < $width; $x++) {
                    imagecopy($flipped, $this->image_resource, $width - $x - 1, 0, $x, 0, 1, $height);
                }
                break;
        }

        imagedestroy($this->image_resource);
        $this->image_resource = $flipped;
        $this->update_resource();
    }

    /**
     * Revert the image back to its original state as loaded in the constructor.
     *
     * @return void
     */
    public function revert_to_original() {
        $this->raw_data = $this->original_raw_data;
        $this->mime_type = $this->original_mime_type;
        $this->extension = $this->original_extension;
        $this->is_vector = $this->original_is_vector;
        $this->exif_data = $this->original_exif_data;

        if (!$this->is_vector) {
            $this->load_image_resource();
            $this->load_exif_data();
        } else {
            $this->image_resource = null;
        }
    }

    /**
     * Update raw data from the image resource after modifications.
     *
     * @return void
     */
    private function update_resource() {
        if ($this->is_vector) return;
        $this->reencode_image($this->extension, $this->quality);
    }
}