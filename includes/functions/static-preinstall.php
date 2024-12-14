<?php

function get_mime_types() {
	return array(
		'css' => 'text/css',
		'js'  => 'application/javascript',
	);
}

/**
  * Minify CSS content.
  *
  * @param string $css The CSS code to be minified.
  *
  * @return string Minified CSS.
  *
  * @throws InvalidArgumentException If input is not a string.
  */
function minify_css($css) {
    if (!is_string($css)) {
        throw new InvalidArgumentException('CSS input must be a string.');
    }

    // Remove comments.
    // This pattern removes all /* ... */ comments.
    $css = preg_replace('#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $css);

    // Normalize whitespace: replace tabs and newlines with a single space.
    $css = str_replace(["\r", "\n", "\t"], ' ', $css);

    // Remove unnecessary whitespace around punctuation.
    // Remove spaces before and after { } : ; , >
    $css = preg_replace('/\s+/', ' ', $css);
    $css = preg_replace('/\s*([{};:>+,])\s*/', '$1', $css);

    // Remove the final semicolon in a CSS block.
    $css = preg_replace('/;}/', '}', $css);

    // Trim final result.
    $css = trim($css);

    return $css;
}

 /**
  * Minify JS content.
  *
  * @param string $js The JavaScript code to be minified.
  *
  * @return string Minified JS.
  *
  * @throws InvalidArgumentException If input is not a string.
  */
function minify_js($js) {
    if (!is_string($js)) {
        throw new InvalidArgumentException('JS input must be a string.');
    }

    // Remove multi-line comments /* ... */
    $js = preg_replace('#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $js);

    // Remove single-line comments, but carefully avoid URLs like http://
    // Negative lookbehind for ':' to prevent removing URL parts.
    $js = preg_replace('#(?<!:)//[^\r\n]*#', '', $js);

    // Remove line-breaks and tabs.
    $js = str_replace(["\r", "\n", "\t"], ' ', $js);

    // Collapse multiple spaces into one.
    $js = preg_replace('/\s+/', ' ', $js);

    // Remove unnecessary spaces around punctuation.
    // We must be careful to not break strings and object keys.
    // This is naive and may need refinement.
    $js = preg_replace('/\s*([=+\-{};(),:\[\]])\s*/', '$1', $js);

    // Trim final result.
    $js = trim($js);

    return $js;
}

 /**
  * Minify HTML content, including inline <style> and <script> tags.
  *
  * @param string $html The HTML code to be minified.
  *
  * @return string Minified HTML.
  *
  * @throws InvalidArgumentException If input is not a string.
  */
function minify_html($html) {
    if (!is_string($html)) {
        throw new InvalidArgumentException('HTML input must be a string.');
    }

    // Minify inline CSS in <style> tags.
    $html = preg_replace_callback(
        '#<style[^>]*>(.*?)</style>#is',
        function ($matches) {
            $minified_css = minfy_css($matches[1]);
            return '<style>' . $minified_css . '</style>';
        },
        $html
    );

    // Minify inline JS in <script> tags.
    $html = preg_replace_callback(
        '#<script[^>]*>(.*?)</script>#is',
        function ($matches) {
            $minified_js = minfy_js($matches[1]);
            return '<script>' . $minified_js . '</script>';
        },
        $html
    );

    // Remove HTML comments except for IE conditional comments.
    // <!--[if ...]> and <!--<![endif]--> should remain.
    $html = preg_replace('/<!--(?!\[if|<!\[endif)(.*?)-->/', '', $html);

    // Remove redundant whitespace between tags.
    // Remove spaces between tags: > < to ><.
    $html = preg_replace('/>\s+</', '><', $html);

    // Remove multiple spaces.
    $html = str_replace(["\r", "\n", "\t"], ' ', $html);
    $html = preg_replace('/\s{2,}/', ' ', $html);

    // Minify attributes spacing:
    // Remove spaces around = for attributes.
    $html = preg_replace('/\s*=\s*/', '=', $html);

    // Trim final result.
    $html = trim($html);

    return $html;
}
