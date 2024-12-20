<?php
/**
 * Minify class file.
 *
 * This file defines the Minify class, which provides methods to minify CSS and JS content.
 * It strips comments, whitespace, and other unnecessary characters to produce smaller,
 * more efficient code.
 *
 * @package Minify
 */

/**
 * Class Minify
 *
 * Provides methods to minify CSS and JavaScript strings. The class extracts strings, comments,
 * regex patterns, and other data, then reduces the code size by removing unnecessary whitespace,
 * comments, and optimizing syntax where possible.
 */
class Minify {

	/**
	 * Regex pattern used to match valid variable names or identifiers.
	 *
	 * @var string
	 */
	const REGEX_VARIABLE = '\b[$A-Z\_a-z\xaa\xb5\xba\xc0-\xd6\xd8-\xf6\xf8-\x{02c1}\x{02c6}-\x{02d1}\x{02e0}-\x{02e4}\x{02ec}\x{02ee}\x{0370}-\x{0374}\x{0376}\x{0377}\x{037a}-\x{037d}\x{0386}\x{0388}-\x{038a}\x{038c}\x{038e}-\x{03a1}\x{03a3}-\x{03f5}\x{03f7}-\x{0481}\x{048a}-\x{0527}\x{0531}-\x{0556}\x{0559}\x{0561}-\x{0587}\x{05d0}-\x{05ea}\x{05f0}-\x{05f2}\x{0620}-\x{064a}\x{066e}\x{066f}\x{0671}-\x{06d3}\x{06d5}\x{06e5}\x{06e6}\x{06ee}\x{06ef}\x{06fa}-\x{06fc}\x{06ff}\x{0710}\x{0712}-\x{072f}\x{074d}-\x{07a5}\x{07b1}\x{07ca}-\x{07ea}\x{07f4}\x{07f5}\x{07fa}\x{0800}-\x{0815}\x{081a}\x{0824}\x{0828}\x{0840}-\x{0858}\x{08a0}\x{08a2}-\x{08ac}\x{0904}-\x{0939}\x{093d}\x{0950}\x{0958}-\x{0961}\x{0971}-\x{0977}\x{0979}-\x{097f}\x{0985}-\x{098c}\x{098f}\x{0990}\x{0993}-\x{09a8}\x{09aa}-\x{09b0}\x{09b2}\x{09b6}-\x{09b9}\x{09bd}\x{09ce}\x{09dc}\x{09dd}\x{09df}-\x{09e1}\x{09f0}\x{09f1}\x{0a05}-\x{0a0a}\x{0a0f}\x{0a10}\x{0a13}-\x{0a28}\x{0a2a}-\x{0a30}\x{0a32}\x{0a33}\x{0a35}\x{0a36}\x{0a38}\x{0a39}\x{0a59}-\x{0a5c}\x{0a5e}\x{0a72}-\x{0a74}\x{0a85}-\x{0a8d}\x{0a8f}-\x{0a91}\x{0a93}-\x{0aa8}\x{0aaa}-\x{0ab0}\x{0ab2}\x{0ab3}\x{0ab5}-\x{0ab9}\x{0abd}\x{0ad0}\x{0ae0}\x{0ae1}\x{0b05}-\x{0b0c}\x{0b0f}\x{0b10}\x{0b13}-\x{0b28}\x{0b2a}-\x{0b30}\x{0b32}\x{0b33}\x{0b35}-\x{0b39}\x{0b3d}\x{0b5c}\x{0b5d}\x{0b5f}-\x{0b61}\x{0b71}\x{0b83}\x{0b85}-\x{0b8a}\x{0b8e}-\x{0b90}\x{0b92}-\x{0b95}\x{0b99}\x{0b9a}\x{0b9c}\x{0b9e}\x{0b9f}\x{0ba3}\x{0ba4}\x{0ba8}-\x{0baa}\x{0bae}-\x{0bb9}\x{0bd0}\x{0c05}-\x{0c0c}\x{0c0e}-\x{0c10}\x{0c12}-\x{0c28}\x{0c2a}-\x{0c33}\x{0c35}-\x{0c39}\x{0c3d}\x{0c58}\x{0c59}\x{0c60}\x{0c61}\x{0c85}-\x{0c8c}\x{0c8e}-\x{0c90}\x{0c92}-\x{0ca8}\x{0caa}-\x{0cb3}\x{0cb5}-\x{0cb9}\x{0cbd}\x{0cde}\x{0ce0}\x{0ce1}\x{0cf1}\x{0cf2}\x{0d05}-\x{0d0c}\x{0d0e}-\x{0d10}\x{0d12}-\x{0d3a}\x{0d3d}\x{0d4e}\x{0d60}\x{0d61}\x{0d7a}-\x{0d7f}\x{0d85}-\x{0d96}\x{0d9a}-\x{0db1}\x{0db3}-\x{0dbb}\x{0dbd}\x{0dc0}-\x{0dc6}\x{0e01}-\x{0e30}\x{0e32}\x{0e33}\x{0e40}-\x{0e46}\x{0e81}\x{0e82}\x{0e84}\x{0e87}\x{0e88}\x{0e8a}\x{0e8d}\x{0e94}-\x{0e97}\x{0e99}-\x{0e9f}\x{0ea1}-\x{0ea3}\x{0ea5}\x{0ea7}\x{0eaa}\x{0eab}\x{0ead}-\x{0eb0}\x{0eb2}\x{0eb3}\x{0ebd}\x{0ec0}-\x{0ec4}\x{0ec6}\x{0edc}-\x{0edf}\x{0f00}\x{0f40}-\x{0f47}\x{0f49}-\x{0f6c}\x{0f88}-\x{0f8c}\x{1000}-\x{102a}\x{103f}\x{1050}-\x{1055}\x{105a}-\x{105d}\x{1061}\x{1065}\x{1066}\x{106e}-\x{1070}\x{1075}-\x{1081}\x{108e}\x{10a0}-\x{10c5}\x{10c7}\x{10cd}\x{10d0}-\x{10fa}\x{10fc}-\x{1248}\x{124a}-\x{124d}\x{1250}-\x{1256}\x{1258}\x{125a}-\x{125d}\x{1260}-\x{1288}\x{128a}-\x{128d}\x{1290}-\x{12b0}\x{12b2}-\x{12b5}\x{12b8}-\x{12be}\x{12c0}\x{12c2}-\x{12c5}\x{12c8}-\x{12d6}\x{12d8}-\x{1310}\x{1312}-\x{1315}\x{1318}-\x{135a}\x{1380}-\x{138f}\x{13a0}-\x{13f4}\x{1401}-\x{166c}\x{166f}-\x{167f}\x{1681}-\x{169a}\x{16a0}-\x{16ea}\x{16ee}-\x{16f0}\x{1700}-\x{170c}\x{170e}-\x{1711}\x{1720}-\x{1731}\x{1740}-\x{1751}\x{1760}-\x{176c}\x{176e}-\x{1770}\x{1780}-\x{17b3}\x{17d7}\x{17dc}\x{1820}-\x{1877}\x{1880}-\x{18a8}\x{18aa}\x{18b0}-\x{18f5}\x{1900}-\x{191c}\x{1950}-\x{196d}\x{1970}-\x{1974}\x{1980}-\x{19ab}\x{19c1}-\x{19c7}\x{1a00}-\x{1a16}\x{1a20}-\x{1a54}\x{1aa7}\x{1b05}-\x{1b33}\x{1b45}-\x{1b4b}\x{1b83}-\x{1ba0}\x{1bae}\x{1baf}\x{1bba}-\x{1be5}\x{1c00}-\x{1c23}\x{1c4d}-\x{1c4f}\x{1c5a}-\x{1c7d}\x{1ce9}-\x{1cec}\x{1cee}-\x{1cf1}\x{1cf5}\x{1cf6}\x{1d00}-\x{1dbf}\x{1e00}-\x{1f15}\x{1f18}-\x{1f1d}\x{1f20}-\x{1f45}\x{1f48}-\x{1f4d}\x{1f50}-\x{1f57}\x{1f59}\x{1f5b}\x{1f5d}\x{1f5f}-\x{1f7d}\x{1f80}-\x{1fb4}\x{1fb6}-\x{1fbc}\x{1fbe}\x{1fc2}-\x{1fc4}\x{1fc6}-\x{1fcc}\x{1fd0}-\x{1fd3}\x{1fd6}-\x{1fdb}\x{1fe0}-\x{1fec}\x{1ff2}-\x{1ff4}\x{1ff6}-\x{1ffc}\x{2071}\x{207f}\x{2090}-\x{209c}\x{2102}\x{2107}\x{210a}-\x{2113}\x{2115}\x{2119}-\x{211d}\x{2124}\x{2126}\x{2128}\x{212a}-\x{212d}\x{212f}-\x{2139}\x{213c}-\x{213f}\x{2145}-\x{2149}\x{214e}\x{2160}-\x{2188}\x{2c00}-\x{2c2e}\x{2c30}-\x{2c5e}\x{2c60}-\x{2ce4}\x{2ceb}-\x{2cee}\x{2cf2}\x{2cf3}\x{2d00}-\x{2d25}\x{2d27}\x{2d2d}\x{2d30}-\x{2d67}\x{2d6f}\x{2d80}-\x{2d96}\x{2da0}-\x{2da6}\x{2da8}-\x{2dae}\x{2db0}-\x{2db6}\x{2db8}-\x{2dbe}\x{2dc0}-\x{2dc6}\x{2dc8}-\x{2dce}\x{2dd0}-\x{2dd6}\x{2dd8}-\x{2dde}\x{2e2f}\x{3005}-\x{3007}\x{3021}-\x{3029}\x{3031}-\x{3035}\x{3038}-\x{303c}\x{3041}-\x{3096}\x{309d}-\x{309f}\x{30a1}-\x{30fa}\x{30fc}-\x{30ff}\x{3105}-\x{312d}\x{3131}-\x{318e}\x{31a0}-\x{31ba}\x{31f0}-\x{31ff}\x{3400}-\x{4db5}\x{4e00}-\x{9fcc}\x{a000}-\x{a48c}\x{a4d0}-\x{a4fd}\x{a500}-\x{a60c}\x{a610}-\x{a61f}\x{a62a}\x{a62b}\x{a640}-\x{a66e}\x{a67f}-\x{a697}\x{a6a0}-\x{a6ef}\x{a717}-\x{a71f}\x{a722}-\x{a788}\x{a78b}-\x{a78e}\x{a790}-\x{a793}\x{a7a0}-\x{a7aa}\x{a7f8}-\x{a801}\x{a803}-\x{a805}\x{a807}-\x{a80a}\x{a80c}-\x{a822}\x{a840}-\x{a873}\x{a882}-\x{a8b3}\x{a8f2}-\x{a8f7}\x{a8fb}\x{a90a}-\x{a925}\x{a930}-\x{a946}\x{a960}-\x{a97c}\x{a984}-\x{a9b2}\x{a9cf}\x{aa00}-\x{aa28}\x{aa40}-\x{aa42}\x{aa44}-\x{aa4b}\x{aa60}-\x{aa76}\x{aa7a}\x{aa80}-\x{aaaf}\x{aab1}\x{aab5}\x{aab6}\x{aab9}-\x{aabd}\x{aac0}\x{aac2}\x{aadb}-\x{aadd}\x{aae0}-\x{aaea}\x{aaf2}-\x{aaf4}\x{ab01}-\x{ab06}\x{ab09}-\x{ab0e}\x{ab11}-\x{ab16}\x{ab20}-\x{ab26}\x{ab28}-\x{ab2e}\x{abc0}-\x{abe2}\x{ac00}-\x{d7a3}\x{d7b0}-\x{d7c6}\x{d7cb}-\x{d7fb}\x{f900}-\x{fa6d}\x{fa70}-\x{fad9}\x{fb00}-\x{fb06}\x{fb13}-\x{fb17}\x{fb1d}\x{fb1f}-\x{fb28}\x{fb2a}-\x{fb36}\x{fb38}-\x{fb3c}\x{fb3e}\x{fb40}\x{fb41}\x{fb43}\x{fb44}\x{fb46}-\x{fbb1}\x{fbd3}-\x{fd3d}\x{fd50}-\x{fd8f}\x{fd92}-\x{fdc7}\x{fdf0}-\x{fdfb}\x{fe70}-\x{fe74}\x{fe76}-\x{fefc}\x{ff21}-\x{ff3a}\x{ff41}-\x{ff5a}\x{ff66}-\x{ffbe}\x{ffc2}-\x{ffc7}\x{ffca}-\x{ffcf}\x{ffd2}-\x{ffd7}\x{ffda}-\x{ffdc}][$A-Z\_a-z\xaa\xb5\xba\xc0-\xd6\xd8-\xf6\xf8-\x{02c1}\x{02c6}-\x{02d1}\x{02e0}-\x{02e4}\x{02ec}\x{02ee}\x{0370}-\x{0374}\x{0376}\x{0377}\x{037a}-\x{037d}\x{0386}\x{0388}-\x{038a}\x{038c}\x{038e}-\x{03a1}\x{03a3}-\x{03f5}\x{03f7}-\x{0481}\x{048a}-\x{0527}\x{0531}-\x{0556}\x{0559}\x{0561}-\x{0587}\x{05d0}-\x{05ea}\x{05f0}-\x{05f2}\x{0620}-\x{064a}\x{066e}\x{066f}\x{0671}-\x{06d3}\x{06d5}\x{06e5}\x{06e6}\x{06ee}\x{06ef}\x{06fa}-\x{06fc}\x{06ff}\x{0710}\x{0712}-\x{072f}\x{074d}-\x{07a5}\x{07b1}\x{07ca}-\x{07ea}\x{07f4}\x{07f5}\x{07fa}\x{0800}-\x{0815}\x{081a}\x{0824}\x{0828}\x{0840}-\x{0858}\x{08a0}\x{08a2}-\x{08ac}\x{0904}-\x{0939}\x{093d}\x{0950}\x{0958}-\x{0961}\x{0971}-\x{0977}\x{0979}-\x{097f}\x{0985}-\x{098c}\x{098f}\x{0990}\x{0993}-\x{09a8}\x{09aa}-\x{09b0}\x{09b2}\x{09b6}-\x{09b9}\x{09bd}\x{09ce}\x{09dc}\x{09dd}\x{09df}-\x{09e1}\x{09f0}\x{09f1}\x{0a05}-\x{0a0a}\x{0a0f}\x{0a10}\x{0a13}-\x{0a28}\x{0a2a}-\x{0a30}\x{0a32}\x{0a33}\x{0a35}\x{0a36}\x{0a38}\x{0a39}\x{0a59}-\x{0a5c}\x{0a5e}\x{0a72}-\x{0a74}\x{0a85}-\x{0a8d}\x{0a8f}-\x{0a91}\x{0a93}-\x{0aa8}\x{0aaa}-\x{0ab0}\x{0ab2}\x{0ab3}\x{0ab5}-\x{0ab9}\x{0abd}\x{0ad0}\x{0ae0}\x{0ae1}\x{0b05}-\x{0b0c}\x{0b0f}\x{0b10}\x{0b13}-\x{0b28}\x{0b2a}-\x{0b30}\x{0b32}\x{0b33}\x{0b35}-\x{0b39}\x{0b3d}\x{0b5c}\x{0b5d}\x{0b5f}-\x{0b61}\x{0b71}\x{0b83}\x{0b85}-\x{0b8a}\x{0b8e}-\x{0b90}\x{0b92}-\x{0b95}\x{0b99}\x{0b9a}\x{0b9c}\x{0b9e}\x{0b9f}\x{0ba3}\x{0ba4}\x{0ba8}-\x{0baa}\x{0bae}-\x{0bb9}\x{0bd0}\x{0c05}-\x{0c0c}\x{0c0e}-\x{0c10}\x{0c12}-\x{0c28}\x{0c2a}-\x{0c33}\x{0c35}-\x{0c39}\x{0c3d}\x{0c58}\x{0c59}\x{0c60}\x{0c61}\x{0c85}-\x{0c8c}\x{0c8e}-\x{0c90}\x{0c92}-\x{0ca8}\x{0caa}-\x{0cb3}\x{0cb5}-\x{0cb9}\x{0cbd}\x{0cde}\x{0ce0}\x{0ce1}\x{0cf1}\x{0cf2}\x{0d05}-\x{0d0c}\x{0d0e}-\x{0d10}\x{0d12}-\x{0d3a}\x{0d3d}\x{0d4e}\x{0d60}\x{0d61}\x{0d7a}-\x{0d7f}\x{0d85}-\x{0d96}\x{0d9a}-\x{0db1}\x{0db3}-\x{0dbb}\x{0dbd}\x{0dc0}-\x{0dc6}\x{0e01}-\x{0e30}\x{0e32}\x{0e33}\x{0e40}-\x{0e46}\x{0e81}\x{0e82}\x{0e84}\x{0e87}\x{0e88}\x{0e8a}\x{0e8d}\x{0e94}-\x{0e97}\x{0e99}-\x{0e9f}\x{0ea1}-\x{0ea3}\x{0ea5}\x{0ea7}\x{0eaa}\x{0eab}\x{0ead}-\x{0eb0}\x{0eb2}\x{0eb3}\x{0ebd}\x{0ec0}-\x{0ec4}\x{0ec6}\x{0edc}-\x{0edf}\x{0f00}\x{0f40}-\x{0f47}\x{0f49}-\x{0f6c}\x{0f88}-\x{0f8c}\x{1000}-\x{102a}\x{103f}\x{1050}-\x{1055}\x{105a}-\x{105d}\x{1061}\x{1065}\x{1066}\x{106e}-\x{1070}\x{1075}-\x{1081}\x{108e}\x{10a0}-\x{10c5}\x{10c7}\x{10cd}\x{10d0}-\x{10fa}\x{10fc}-\x{1248}\x{124a}-\x{124d}\x{1250}-\x{1256}\x{1258}\x{125a}-\x{125d}\x{1260}-\x{1288}\x{128a}-\x{128d}\x{1290}-\x{12b0}\x{12b2}-\x{12b5}\x{12b8}-\x{12be}\x{12c0}\x{12c2}-\x{12c5}\x{12c8}-\x{12d6}\x{12d8}-\x{1310}\x{1312}-\x{1315}\x{1318}-\x{135a}\x{1380}-\x{138f}\x{13a0}-\x{13f4}\x{1401}-\x{166c}\x{166f}-\x{167f}\x{1681}-\x{169a}\x{16a0}-\x{16ea}\x{16ee}-\x{16f0}\x{1700}-\x{170c}\x{170e}-\x{1711}\x{1720}-\x{1731}\x{1740}-\x{1751}\x{1760}-\x{176c}\x{176e}-\x{1770}\x{1780}-\x{17b3}\x{17d7}\x{17dc}\x{1820}-\x{1877}\x{1880}-\x{18a8}\x{18aa}\x{18b0}-\x{18f5}\x{1900}-\x{191c}\x{1950}-\x{196d}\x{1970}-\x{1974}\x{1980}-\x{19ab}\x{19c1}-\x{19c7}\x{1a00}-\x{1a16}\x{1a20}-\x{1a54}\x{1aa7}\x{1b05}-\x{1b33}\x{1b45}-\x{1b4b}\x{1b83}-\x{1ba0}\x{1bae}\x{1baf}\x{1bba}-\x{1be5}\x{1c00}-\x{1c23}\x{1c4d}-\x{1c4f}\x{1c5a}-\x{1c7d}\x{1ce9}-\x{1cec}\x{1cee}-\x{1cf1}\x{1cf5}\x{1cf6}\x{1d00}-\x{1dbf}\x{1e00}-\x{1f15}\x{1f18}-\x{1f1d}\x{1f20}-\x{1f45}\x{1f48}-\x{1f4d}\x{1f50}-\x{1f57}\x{1f59}\x{1f5b}\x{1f5d}\x{1f5f}-\x{1f7d}\x{1f80}-\x{1fb4}\x{1fb6}-\x{1fbc}\x{1fbe}\x{1fc2}-\x{1fc4}\x{1fc6}-\x{1fcc}\x{1fd0}-\x{1fd3}\x{1fd6}-\x{1fdb}\x{1fe0}-\x{1fec}\x{1ff2}-\x{1ff4}\x{1ff6}-\x{1ffc}\x{2071}\x{207f}\x{2090}-\x{209c}\x{2102}\x{2107}\x{210a}-\x{2113}\x{2115}\x{2119}-\x{211d}\x{2124}\x{2126}\x{2128}\x{212a}-\x{212d}\x{212f}-\x{2139}\x{213c}-\x{213f}\x{2145}-\x{2149}\x{214e}\x{2160}-\x{2188}\x{2c00}-\x{2c2e}\x{2c30}-\x{2c5e}\x{2c60}-\x{2ce4}\x{2ceb}-\x{2cee}\x{2cf2}\x{2cf3}\x{2d00}-\x{2d25}\x{2d27}\x{2d2d}\x{2d30}-\x{2d67}\x{2d6f}\x{2d80}-\x{2d96}\x{2da0}-\x{2da6}\x{2da8}-\x{2dae}\x{2db0}-\x{2db6}\x{2db8}-\x{2dbe}\x{2dc0}-\x{2dc6}\x{2dc8}-\x{2dce}\x{2dd0}-\x{2dd6}\x{2dd8}-\x{2dde}\x{2e2f}\x{3005}-\x{3007}\x{3021}-\x{3029}\x{3031}-\x{3035}\x{3038}-\x{303c}\x{3041}-\x{3096}\x{309d}-\x{309f}\x{30a1}-\x{30fa}\x{30fc}-\x{30ff}\x{3105}-\x{312d}\x{3131}-\x{318e}\x{31a0}-\x{31ba}\x{31f0}-\x{31ff}\x{3400}-\x{4db5}\x{4e00}-\x{9fcc}\x{a000}-\x{a48c}\x{a4d0}-\x{a4fd}\x{a500}-\x{a60c}\x{a610}-\x{a61f}\x{a62a}\x{a62b}\x{a640}-\x{a66e}\x{a67f}-\x{a697}\x{a6a0}-\x{a6ef}\x{a717}-\x{a71f}\x{a722}-\x{a788}\x{a78b}-\x{a78e}\x{a790}-\x{a793}\x{a7a0}-\x{a7aa}\x{a7f8}-\x{a801}\x{a803}-\x{a805}\x{a807}-\x{a80a}\x{a80c}-\x{a822}\x{a840}-\x{a873}\x{a882}-\x{a8b3}\x{a8f2}-\x{a8f7}\x{a8fb}\x{a90a}-\x{a925}\x{a930}-\x{a946}\x{a960}-\x{a97c}\x{a984}-\x{a9b2}\x{a9cf}\x{aa00}-\x{aa28}\x{aa40}-\x{aa42}\x{aa44}-\x{aa4b}\x{aa60}-\x{aa76}\x{aa7a}\x{aa80}-\x{aaaf}\x{aab1}\x{aab5}\x{aab6}\x{aab9}-\x{aabd}\x{aac0}\x{aac2}\x{aadb}-\x{aadd}\x{aae0}-\x{aaea}\x{aaf2}-\x{aaf4}\x{ab01}-\x{ab06}\x{ab09}-\x{ab0e}\x{ab11}-\x{ab16}\x{ab20}-\x{ab26}\x{ab28}-\x{ab2e}\x{abc0}-\x{abe2}\x{ac00}-\x{d7a3}\x{d7b0}-\x{d7c6}\x{d7cb}-\x{d7fb}\x{f900}-\x{fa6d}\x{fa70}-\x{fad9}\x{fb00}-\x{fb06}\x{fb13}-\x{fb17}\x{fb1d}\x{fb1f}-\x{fb28}\x{fb2a}-\x{fb36}\x{fb38}-\x{fb3c}\x{fb3e}\x{fb40}\x{fb41}\x{fb43}\x{fb44}\x{fb46}-\x{fbb1}\x{fbd3}-\x{fd3d}\x{fd50}-\x{fd8f}\x{fd92}-\x{fdc7}\x{fdf0}-\x{fdfb}\x{fe70}-\x{fe74}\x{fe76}-\x{fefc}\x{ff21}-\x{ff3a}\x{ff41}-\x{ff5a}\x{ff66}-\x{ffbe}\x{ffc2}-\x{ffc7}\x{ffca}-\x{ffcf}\x{ffd2}-\x{ffd7}\x{ffda}-\x{ffdc}0-9\x{0300}-\x{036f}\x{0483}-\x{0487}\x{0591}-\x{05bd}\x{05bf}\x{05c1}\x{05c2}\x{05c4}\x{05c5}\x{05c7}\x{0610}-\x{061a}\x{064b}-\x{0669}\x{0670}\x{06d6}-\x{06dc}\x{06df}-\x{06e4}\x{06e7}\x{06e8}\x{06ea}-\x{06ed}\x{06f0}-\x{06f9}\x{0711}\x{0730}-\x{074a}\x{07a6}-\x{07b0}\x{07c0}-\x{07c9}\x{07eb}-\x{07f3}\x{0816}-\x{0819}\x{081b}-\x{0823}\x{0825}-\x{0827}\x{0829}-\x{082d}\x{0859}-\x{085b}\x{08e4}-\x{08fe}\x{0900}-\x{0903}\x{093a}-\x{093c}\x{093e}-\x{094f}\x{0951}-\x{0957}\x{0962}\x{0963}\x{0966}-\x{096f}\x{0981}-\x{0983}\x{09bc}\x{09be}-\x{09c4}\x{09c7}\x{09c8}\x{09cb}-\x{09cd}\x{09d7}\x{09e2}\x{09e3}\x{09e6}-\x{09ef}\x{0a01}-\x{0a03}\x{0a3c}\x{0a3e}-\x{0a42}\x{0a47}\x{0a48}\x{0a4b}-\x{0a4d}\x{0a51}\x{0a66}-\x{0a71}\x{0a75}\x{0a81}-\x{0a83}\x{0abc}\x{0abe}-\x{0ac5}\x{0ac7}-\x{0ac9}\x{0acb}-\x{0acd}\x{0ae2}\x{0ae3}\x{0ae6}-\x{0aef}\x{0b01}-\x{0b03}\x{0b3c}\x{0b3e}-\x{0b44}\x{0b47}\x{0b48}\x{0b4b}-\x{0b4d}\x{0b56}\x{0b57}\x{0b62}\x{0b63}\x{0b66}-\x{0b6f}\x{0b82}\x{0bbe}-\x{0bc2}\x{0bc6}-\x{0bc8}\x{0bca}-\x{0bcd}\x{0bd7}\x{0be6}-\x{0bef}\x{0c01}-\x{0c03}\x{0c3e}-\x{0c44}\x{0c46}-\x{0c48}\x{0c4a}-\x{0c4d}\x{0c55}\x{0c56}\x{0c62}\x{0c63}\x{0c66}-\x{0c6f}\x{0c82}\x{0c83}\x{0cbc}\x{0cbe}-\x{0cc4}\x{0cc6}-\x{0cc8}\x{0cca}-\x{0ccd}\x{0cd5}\x{0cd6}\x{0ce2}\x{0ce3}\x{0ce6}-\x{0cef}\x{0d02}\x{0d03}\x{0d3e}-\x{0d44}\x{0d46}-\x{0d48}\x{0d4a}-\x{0d4d}\x{0d57}\x{0d62}\x{0d63}\x{0d66}-\x{0d6f}\x{0d82}\x{0d83}\x{0dca}\x{0dcf}-\x{0dd4}\x{0dd6}\x{0dd8}-\x{0ddf}\x{0df2}\x{0df3}\x{0e31}\x{0e34}-\x{0e3a}\x{0e47}-\x{0e4e}\x{0e50}-\x{0e59}\x{0eb1}\x{0eb4}-\x{0eb9}\x{0ebb}\x{0ebc}\x{0ec8}-\x{0ecd}\x{0ed0}-\x{0ed9}\x{0f18}\x{0f19}\x{0f20}-\x{0f29}\x{0f35}\x{0f37}\x{0f39}\x{0f3e}\x{0f3f}\x{0f71}-\x{0f84}\x{0f86}\x{0f87}\x{0f8d}-\x{0f97}\x{0f99}-\x{0fbc}\x{0fc6}\x{102b}-\x{103e}\x{1040}-\x{1049}\x{1056}-\x{1059}\x{105e}-\x{1060}\x{1062}-\x{1064}\x{1067}-\x{106d}\x{1071}-\x{1074}\x{1082}-\x{108d}\x{108f}-\x{109d}\x{135d}-\x{135f}\x{1712}-\x{1714}\x{1732}-\x{1734}\x{1752}\x{1753}\x{1772}\x{1773}\x{17b4}-\x{17d3}\x{17dd}\x{17e0}-\x{17e9}\x{180b}-\x{180d}\x{1810}-\x{1819}\x{18a9}\x{1920}-\x{192b}\x{1930}-\x{193b}\x{1946}-\x{194f}\x{19b0}-\x{19c0}\x{19c8}\x{19c9}\x{19d0}-\x{19d9}\x{1a17}-\x{1a1b}\x{1a55}-\x{1a5e}\x{1a60}-\x{1a7c}\x{1a7f}-\x{1a89}\x{1a90}-\x{1a99}\x{1b00}-\x{1b04}\x{1b34}-\x{1b44}\x{1b50}-\x{1b59}\x{1b6b}-\x{1b73}\x{1b80}-\x{1b82}\x{1ba1}-\x{1bad}\x{1bb0}-\x{1bb9}\x{1be6}-\x{1bf3}\x{1c24}-\x{1c37}\x{1c40}-\x{1c49}\x{1c50}-\x{1c59}\x{1cd0}-\x{1cd2}\x{1cd4}-\x{1ce8}\x{1ced}\x{1cf2}-\x{1cf4}\x{1dc0}-\x{1de6}\x{1dfc}-\x{1dff}\x{200c}\x{200d}\x{203f}\x{2040}\x{2054}\x{20d0}-\x{20dc}\x{20e1}\x{20e5}-\x{20f0}\x{2cef}-\x{2cf1}\x{2d7f}\x{2de0}-\x{2dff}\x{302a}-\x{302f}\x{3099}\x{309a}\x{a620}-\x{a629}\x{a66f}\x{a674}-\x{a67d}\x{a69f}\x{a6f0}\x{a6f1}\x{a802}\x{a806}\x{a80b}\x{a823}-\x{a827}\x{a880}\x{a881}\x{a8b4}-\x{a8c4}\x{a8d0}-\x{a8d9}\x{a8e0}-\x{a8f1}\x{a900}-\x{a909}\x{a926}-\x{a92d}\x{a947}-\x{a953}\x{a980}-\x{a983}\x{a9b3}-\x{a9c0}\x{a9d0}-\x{a9d9}\x{aa29}-\x{aa36}\x{aa43}\x{aa4c}\x{aa4d}\x{aa50}-\x{aa59}\x{aa7b}\x{aab0}\x{aab2}-\x{aab4}\x{aab7}\x{aab8}\x{aabe}\x{aabf}\x{aac1}\x{aaeb}-\x{aaef}\x{aaf5}\x{aaf6}\x{abe3}-\x{abea}\x{abec}\x{abed}\x{abf0}-\x{abf9}\x{fb1e}\x{fe00}-\x{fe0f}\x{fe20}-\x{fe26}\x{fe33}\x{fe34}\x{fe4d}-\x{fe4f}\x{ff10}-\x{ff19}\x{ff3f}]*\b';

	/**
	 * Patterns registered for string replacements during minification.
	 *
	 * @var array
	 */
	protected $patterns = array();

	/**
	 * Reserved keywords that are treated specially to maintain code integrity.
	 *
	 * @var array
	 */
	protected $keywordsReserved = array(
		'do', 'if', 'in', 'for', 'let', 'new', 'try', 
		'var', 'case', 'else', 'enum', 'eval', 'null', 'this',
		'true', 'void', 'with', 'break', 'catch', 'class', 'const',
		'false', 'super', 'throw', 'while', 'yield', 'delete', 'export',
		'import', 'public', 'return', 'static', 'switch', 'typeof', 'default',
		'extends', 'finally', 'package', 'private', 'continue', 'debugger', 'function',
		'arguments', 'interface', 'protected', 'implements', 'instanceof', 'abstract', 'boolean',
		'byte', 'char', 'double', 'final', 'float', 'goto', 'int',
		'long', 'native', 'short', 'synchronized', 'throws', 'transient', 'volatile',
	);

	/**
	 * Keywords that may appear before certain tokens and affect parsing.
	 *
	 * @var array
	 */
	protected $keywordsBefore = array(
		'do', 'in', 'let', 'new', 'var', 'case', 'else',
		'enum', 'void', 'with', 'class', 'const', 'yield', 'delete',
		'export', 'import', 'public', 'static', 'typeof', 'extends', 'package',
		'private', 'function', 'protected', 'implements', 'instanceof',
	);

	/**
	 * Keywords that may appear after certain tokens and affect parsing.
	 *
	 * @var array
	 */
	protected $keywordsAfter = array(
		'in', 'public', 'extends', 'private', 'protected', 'implements', 'instanceof'
	);

	/**
	 * Operators used in JS and CSS that may influence whitespace or token boundaries.
	 *
	 * @var array
	 */
	protected $operators = array(
		'+', '-', '*', '/', '%', '=', '+=', '-=', '*=', '/=', '%=', '<<=', '>>=', '>>>=', '&=', '^=', '|=', '&', '|', '^',
		'~', '<<', '>>', '>>>', '==', '===', '!=', '!==', '>', '<', '>=', '<=', '&&', '||', '!', '.', '[', ']', '?', ':',
		',', ';', '(', ')', '{', '}',
	);

	/**
	 * Operators that are considered "before" tokens for whitespace handling.
	 *
	 * @var array
	 */
	protected $operatorsBefore = array(
		'+', '-', '*', '/', '%', '=', '+=', '-=', '*=', '/=', '%=', '<<=', '>>=', '>>>=', '&=', '^=', '|=', '&', '|', '^',
		'~', '<<', '>>', '>>>', '==', '===', '!=', '!==', '>', '<', '>=', '<=', '&&', '||', '!', '.', '[', '?', ':', ',',
		';', '(', '{',
	);

	/**
	 * Operators that are considered "after" tokens for whitespace handling.
	 *
	 * @var array
	 */
	protected $operatorsAfter = array(
		'+', '-', '*', '/', '%', '=', '+=', '-=', '*=', '/=', '%=', '<<=', '>>=', '>>>=', '&=', '^=', '|=', '&', '|', '^',
		'<<', '>>', '>>>', '==', '===', '!=', '!==', '>', '<', '>=', '<=', '&&', '||', '.', '[', ']', '?', ':', ',', ';',
		'(', ')', '}',
	);

	/**
	 * Extracted data (strings, comments, etc.) temporarily stored during processing.
	 *
	 * @var array
	 */
	public $extracted = array();

	/**
	 * Minify.
	 *
	 * @param $data Data to be minified.
	 * @param $type HTML, CSS, JS.
	 *
	 * return Minified data.
	 */
	public function minify($data, $type) {
		if ($type==='html' || $type==='htm') {
			return $this->html($data);
		} elseif ($type==='css') {
			return $this->css($data);
		} elseif ($type==='js') {
			return $this->js($data);
		}
		return $data;
	}

	/**
	 * Minify HTML content.
	 *
	 * Extracts strings, comments, and math, then removes unnecessary whitespace,
	 * converts legacy colors, shortens HEX colors, and restores extracted data.
	 *
	 * @param string      $html HTML content to minify.
	 * @param bool        $is_file_path If true, the HTML content is assumed to be a file path.
	 * @param string|bool $save_to If true, the minified HTML will be saved to the original file path in .min.HTML extention, 
	 *                    if output is a file path, or to a new file with the same name, content will appended.
	 * @param bool        $exception If true, exceptions will be thrown instead of false return.
	 * @return string|bool Minified HTML content, file path, or false on error.
	 */
	public function html($html, $is_file_path = false, $save_to = false, $exception = false) {
		return $html;
	}

	/**
	 * Minify CSS content.
	 *
	 * Extracts strings, comments, and math, then removes unnecessary whitespace,
	 * converts legacy colors, shortens HEX colors, and restores extracted data.
	 *
	 * @param string      $css CSS content to minify.
	 * @param bool        $is_file_path If true, the CSS content is assumed to be a file path.
	 * @param string|bool $save_to If true, the minified CSS will be saved to the original file path in .min.css extention, 
	 *                    if output is a file path, or to a new file with the same name, content will appended.
	 * @param bool        $exception If true, exceptions will be thrown instead of false return.
	 * @return string|bool Minified CSS content, file path, or false on error.
	 */
	public function css($css, $is_file_path = false, $save_to = false, $exception = false) {
		$css = $is_file_path ? $this->get_file_content($css, $exception) : $css;
		$this->extractStrings();
		$this->stripMultilineComments();
		$this->extractMath();
		$this->extractCustomProperties();
		$css = $this->replace($css);
		$css = $this->stripWhitespaceCSS($css);
		$css = $this->convertLegacyColors($css);
		$css = $this->cleanupModernColors($css);
		$css = $this->shortenHEXColors($css);
		$css = $this->shortenZeroes($css);
		$css = $this->shortenFontWeights($css);
		$css = $this->stripEmptyTags($css);
		$css = $this->restoreExtractedData($css);
		return $save_to
			? $this->save_to(true === $save_to
				? $this->create_min_file_name($css, 'css')
				: $save_to, $css, $exception)
			: $css;
	}

	/**
	 * Minify and Combine Group CSS Files.
	 *
	 * @param array       $files Array of file paths to minify and combine.
	 * @param string|bool $save_to If true, the minified CSS will be saved to the original file path in .min.css extention, 
	 *                    if output is a file path, or to a new file with the same name, content will appended.
	 * @param bool        $exception If true, exceptions will be thrown instead of false return.
	 * @return string|bool Minified JS content, file path, or false on error.
	 */
	public function group_css_files($files, $save_to = false, $exception = false) {
		$css_combine = '';
		foreach($files as $file) {
			$css = $this->css($file, true, false, $exception);
			if($css !== false) {
				$css_combine .= $css;
			} else {
				return $exception ? (throw new \Exception("{$file} - File Doesn't Exist.")) : false;
			}
		}
		return $save_to
			? $this->save_to(true === $save_to
				? 'app.min.css'
				: $save_to, $css_combine, $exception)
			: $css;
	}

	/**
	 * Minify JavaScript content.
	 *
	 * Extracts strings, comments, regex patterns, then reduces whitespace and shortens boolean values.
	 * Also handles property notation and ensures that code remains syntactically valid.
	 *
	 * @param array      $css_urls JavaScript URLs to minify.
	 * @param string|bool $save_to If true, the minified CSS will be saved to the original file path in .min.css extention, 
	 *                    if output is a file path, or to a new file with the same name, content will appended.
	 * @param bool        $exception If true, exceptions will be thrown instead of false return.
	 * @return string|bool Minified CS content, file path, or false on error.
	 */
	public function css_urls($css_urls, $save_to = false, $exception = false) {
		$css_combine = '';
		foreach($css_urls as $css_url) {
			$css = file_get_contents($css_url);
			if($css !== false) {
				$css_combine .= $css . ';';
			} else {
				return $exception ? (throw new \Exception("{$css_url} - URL Didn't Fetch.")) : false;
			}
		}
		return $this->css($css_combine, false, $save_to, $exception);
	}

	/**
	 * Minify JavaScript content.
	 *
	 * Extracts strings, comments, regex patterns, then reduces whitespace and shortens boolean values.
	 * Also handles property notation and ensures that code remains syntactically valid.
	 *
	 * @param string $js JavaScript content to minify.
	 * @param bool        $is_file_path If true, the JS content is assumed to be a file path.
	 * @param string|bool $save_to If true, the minified JS will be saved to the original file path in .min.js extention, 
	 *                    if output is a file path, or to a new file with the same name, content will appended.
	 * @param bool        $exception If true, exceptions will be thrown instead of false return.
	 * @return string|bool Minified JS content, file path, or false on error.
	 */
	public function js($js, $is_file_path = false, $save_to = false, $exception = false) {
		$js = $is_file_path ? $this->get_file_content($js, $exception) : $js;
		$this->extractStrings('\'"`');
		$this->stripMultilineComments();
		$this->registerPattern('/\/\/.*$/m', '');
		$this->extractRegex();
		$js = $this->replace($js);
		$js = $this->propertyNotation($js);
		$js = $this->shortenBools($js);
		$js = $this->stripWhitespaceJS($js);
		$js = ltrim($js, ';');
		$js = (string) substr($js, 0, -1);
		$js = $this->restoreExtractedData($js);
		return $save_to
			? $this->save_to(true === $save_to
				? $this->create_min_file_name($js, 'js')
				: $save_to, $js, $exception)
			: $js;
	}

	/**
	 * Minify and Combine Group JS Files.
	 *
	 * @param array       $files Array of file paths to minify and combine.
	 * @param string|bool $save_to If true, the minified JS will be saved to the original file path in .min.js extention, 
	 *                    if output is a file path, or to a new file with the same name, content will appended.
	 * @param bool        $exception If true, exceptions will be thrown instead of false return.
	 * @return string|bool Minified JS content, file path, or false on error.
	 */
	public function group_js_files($files, $save_to = false, $exception = false) {
		$js_combine = '';
		foreach($files as $file) {
			$js = $this->js($file, true, false, $exception);
			if($js !== false) {
				$js_combine .= $js . ';';
			} else {
				return $exception ? (throw new \Exception("{$file} - File Doesn't Exist.")) : false;
			}
		}
		return $save_to
			? $this->save_to(true === $save_to
				? 'app.min.js'
				: $save_to, $js_combine, $exception)
			: $js;
	}

	/**
	 * Minify JavaScript content.
	 *
	 * Extracts strings, comments, regex patterns, then reduces whitespace and shortens boolean values.
	 * Also handles property notation and ensures that code remains syntactically valid.
	 *
	 * @param array      $js_urls JavaScript URLs to minify.
	 * @param string|bool $save_to If true, the minified JS will be saved to the original file path in .min.js extention, 
	 *                    if output is a file path, or to a new file with the same name, content will appended.
	 * @param bool        $exception If true, exceptions will be thrown instead of false return.
	 * @return string|bool Minified JS content, file path, or false on error.
	 */
	public function js_urls($js_urls, $save_to = false, $exception = false) {
		$js_combine = '';
		foreach($js_urls as $js_url) {
			$js = file_get_contents($js_url);
			if($js !== false) {
				$js_combine .= $js . ';';
			} else {
				return $exception ? (throw new \Exception("{$js_url} - URL Didn't Fetch.")) : false;
			}
		}
		return $this->js($js_combine, false, $save_to, $exception);
	}

	/**
	 * Get File Content.
	 *
	 * @param string       $file      Path to file to fetch.
	 * @param bool         $exception Determines error handling behavior. If true, throws an exception on failure.
	 * @return string|bool String of content on success, false on failure.
	 * @throws \Exception if, File does not exist, or exception is enabled.
	 */
	protected function get_file_content($file, $exception = false) {
		if (file_exists($file)) {
			return file_get_contents($file);
		} else {
			return $exception ? (throw new \Exception("File Doesn't Exist.")) : false;
		}
	}

	/**
	 * Generates a minified file name based on the existence of the original file.
	 *
	 * @param string $file The original file path, including the filename and extension.
	 * @param string $for The extension or identifier to append after 'min.' (e.g., 'js', 'css').
	 * @return string The minified file path if the original file exists; otherwise, 'app.min.js'.
	 */
	protected function create_min_file_name($file, $for) {
		return file_exists($file) 
			? (
				dirname($file) !== '.' 
					? (mkdir(dirname($file), 0755, true) 
						? dirname($file) . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_FILENAME) . '.min.' . $for 
						: 'app.min.js')
					: pathinfo($file, PATHINFO_FILENAME) . '.min.' . $for
			  )
			: 'app.min.js';
	}

	/**
 	 * Saves content to a specified file path using ternary operators.
 	 *
 	 * @param string $file_path The full path to the file, including the filename.
 	 * @param string $content The content to write to the file.
 	 * @param bool   $exception Determines error handling behavior. If true, throws an exception on failure.
 	 * @return string|bool Returns file path on success, false on failure (if $exception is false).
 	 * @throws \Exception If $exception is true and an error occurs during directory creation or file writing.
 	 */
	protected function save_to($file_path, $content, $exception = false) {
		// Extract the directory from the file path
		$directory = dirname($file_path);
	
		// Check if the directory exists and Attempt to create the directory recursively with permissions 0755.
		$dirCreated = is_dir($directory) 
			? true 
			: (mkdir($directory, 0755, true) 
				? true 
				: ($exception ? (throw new \Exception("Failed to create directory: {$directory}")) : false));

		// Attempt to write the content to the file
		return $dirCreated 
			? (file_put_contents($file_path, $content) !== false 
				? $file_path 
				: ($exception ? (throw new \Exception("Failed to write to file: {$file_path}")) : false))
			: ($exception ? (throw new \Exception("Failed to create directory: {$directory}")) : false);
	}

	/**
	 * Extract strings from content based on specified delimiters.
	 *
	 * @param string $chars           Characters to consider as string delimiters.
	 * @param string $placeholderPrefix Prefix to prepend to placeholders.
	 * @return void
	 */
	protected function extractStrings($chars = '\'"', $placeholderPrefix = '') {
		$minifier = $this;
		$callback = function ($match) use ($minifier, $placeholderPrefix) {
			if ($match[2] === '') {
				return $match[0];
			}
			$count = count($minifier->extracted);
			$placeholder = $match[1] . $placeholderPrefix . $count . $match[1];
			$minifier->extracted[$placeholder] = $match[1] . $match[2] . $match[1];
			return $placeholder;
		};
		$this->registerPattern('/([' . $chars . '])(.*?(?<!\\\\)(\\\\\\\\)*+)\\1/s', $callback);
	}

	/**
	 * Register a pattern and its replacement or callback for use in the replace cycle.
	 *
	 * @param string          $pattern     Regex pattern to register.
	 * @param string|callable $replacement Replacement string or callback.
	 * @return void
	 */
	protected function registerPattern($pattern, $replacement = '') {
		$pattern .= 'S';
		$this->patterns[] = array($pattern, $replacement);
	}

	/**
	 * Strip multiline comments, preserving certain /*! or @license annotations.
	 *
	 * @return void
	 */
	protected function stripMultilineComments() {
		$minifier = $this;
		$callback = function ($match) use ($minifier) {
			$count = count($minifier->extracted);
			$placeholder = '/*' . $count . '*/';
			$minifier->extracted[$placeholder] = $match[0];
			return $placeholder;
		};

		// Preserve comments with ! or @license/@preserve.
		$this->registerPattern('/
			# optional newline
			\n?
			# start comment
			\/\*
			# comment content
			(?:
				# either starts with an !
				!
			|
				# or, after some number of characters which do not end the comment
				(?:(?!\*\/).)*?
				# there is either a @license or @preserve tag
				@(?:license|preserve)
			)
			# then match to the end of the comment
			.*?\*\/\n?
			/ixs', $callback);
		// Remove other comments.
		$this->registerPattern('/\/\*.*?\*\//s', '');
	}

	/**
	 * Replace all registered patterns in the given content.
	 *
	 * @param string $content Content to process.
	 * @return string Processed content.
	 */
	protected function replace($content) {
		$contentLength = strlen($content);
		$output = '';
		$processedOffset = 0;
		$positions = array_fill(0, count($this->patterns), -1);
		$matches = array();

		while ($processedOffset < $contentLength) {
			foreach ($this->patterns as $i => $pattern) {
				list($pattern, $replacement) = $pattern;
				if (!array_key_exists($i, $positions)) {
					continue;
				}
				if ($positions[$i] >= $processedOffset) {
					continue;
				}
				$match = null;
				if (preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE, $processedOffset)) {
					$matches[$i] = $match;
					$positions[$i] = $match[0][1];
				} else {
					unset($matches[$i], $positions[$i]);
				}
			}

			if (!$matches) {
				$output .= substr($content, $processedOffset);
				break;
			}

			$matchOffset = min($positions);
			$firstPattern = array_search($matchOffset, $positions);
			$match = $matches[$firstPattern];
			list(, $replacement) = $this->patterns[$firstPattern];
			$output .= substr($content, $processedOffset, $matchOffset - $processedOffset);
			$output .= $this->executeReplacement($replacement, $match);
			$processedOffset = $matchOffset + strlen($match[0][0]);
		}

		return $output;
	}

	/**
	 * Execute the replacement for a matched pattern.
	 *
	 * @param string|callable $replacement Replacement string or callback.
	 * @param array           $match       The matched results from preg_match.
	 * @return string Replacement result.
	 */
	protected function executeReplacement($replacement, $match) {
		if (!is_callable($replacement)) {
			return $replacement;
		}
		foreach ($match as &$matchItem) {
			$matchItem = $matchItem[0];
		}
		return $replacement($match);
	}

	/**
	 * Restore extracted data (strings, comments) back into the content.
	 *
	 * @param string $content Content from which data was extracted.
	 * @return string Content with data restored.
	 */
	protected function restoreExtractedData($content) {
		if (!$this->extracted) {
			return $content;
		}
		$content = strtr($content, $this->extracted);
		$this->extracted = array();
		return $content;
	}

	/**
	 * Extract math functions like calc(), clamp(), min(), max().
	 *
	 * @return void
	 */
	protected function extractMath() {
		$functions = array('calc', 'clamp', 'min', 'max');
		$pattern = '/\b(' . implode('|', $functions) . ')(\(.+?)(?=$|;|})/m';
		$minifier = $this;
		$callback = function ($match) use ($minifier, $pattern, &$callback) {
			$function = $match[1];
			$length = strlen($match[2]);
			$expr = '';
			$opened = 0;
			for ($i = 0; $i < $length; ++$i) {
				$char = $match[2][$i];
				$expr .= $char;
				if ($char === '(') {
					++$opened;
				} elseif ($char === ')' && --$opened === 0) {
					break;
				}
			}
			$count = count($minifier->extracted);
			$placeholder = 'math(' . $count . ')';
			$minifier->extracted[$placeholder] = $function . '(' . trim(substr($expr, 1, -1)) . ')';
			$rest = $minifier->str_replace_first($function . $expr, '', $match[0]);
			$rest = preg_replace_callback($pattern, $callback, $rest);
			return $placeholder . $rest;
		};
		$this->registerPattern($pattern, $callback);
	}

	/**
	 * Extract custom CSS properties.
	 *
	 * @return void
	 */
	protected function extractCustomProperties() {
		$minifier = $this;
		$this->registerPattern(
			'/(?<=^|[;}{])\s*(--[^:;{}"\'\s]+)\s*:([^;{}]+)/m',
			function ($match) use ($minifier) {
				$placeholder = '--custom-' . count($minifier->extracted) . ':0';
				$minifier->extracted[$placeholder] = $match[1] . ':' . trim($match[2]);
				return $placeholder;
			}
		);
	}

	/**
	 * Strip unnecessary whitespace from CSS content.
	 *
	 * @param string $content CSS content.
	 * @return string Minified CSS.
	 */
	protected function stripWhitespaceCSS($content) {
		// Remove leading and trailing whitespace per line.
		$content = preg_replace('/^\s*/m', '', $content);
		$content = preg_replace('/\s*$/m', '', $content);
		$content = preg_replace('/\s+/', ' ', $content);
		$content = preg_replace('/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $content);
		$content = preg_replace('/([\[(:>\+])\s+/', '$1', $content);
		$content = preg_replace('/\s+([\]\)>\+])/', '$1', $content);
		$content = preg_replace('/\s+(:)(?![^\}]*\{)/', '$1', $content);

		// Handle pseudo-classes
		$pseudos = array('nth-child', 'nth-last-child', 'nth-last-of-type', 'nth-of-type');
		$content = preg_replace('/:(' . implode('|', $pseudos) . ')\(\s*([+-]?)\s*(.+?)\s*([+-]?)\s*(.*?)\s*\)/', ':$1($2$3$4$5)', $content);
		$content = str_replace(';}', '}', $content);

		return trim($content);
	}

	/**
	 * Convert legacy color formats (like rgb()) to modern formats.
	 *
	 * @param string $content CSS content.
	 * @return string Processed CSS.
	 */
	protected function convertLegacyColors($content) {
		// Convert rgb/rgba/hsl/hsla to modern syntax.
		$content = preg_replace('/(rgb)a?\(\s*([0-9]{1,3}%?)\s*,\s*([0-9]{1,3}%?)\s*,\s*([0-9]{1,3}%?)\s*,\s*([0,1]?(?:\.[0-9]*)?)\s*\)/i', '$1($2 $3 $4 / $5)', $content);
		$content = preg_replace('/(rgb)a?\(\s*([0-9]{1,3}%?)\s*,\s*([0-9]{1,3}%?)\s*,\s*([0-9]{1,3}%?)\s*\)/i', '$1($2 $3 $4)', $content);
		$content = preg_replace('/(hsl)a?\(\s*([0-9]+(?:deg|grad|rad|turn)?)\s*,\s*([0-9]{1,3}%)\s*,\s*([0-9]{1,3}%)\s*,\s*([0,1]?(?:\.[0-9]*)?)\s*\)/i', '$1($2 $3 $4 / $5)', $content);
		$content = preg_replace('/(hsl)a?\(\s*([0-9]+(?:deg|grad|rad|turn)?)\s*,\s*([0-9]{1,3}%)\s*,\s*([0-9]{1,3}%)\s*\)/i', '$1($2 $3 $4)', $content);

		// Convert rgb() values to hex where possible.
		$dec = '([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])';
		$content = preg_replace_callback(
			"/rgb\($dec $dec $dec\)/i",
			function ($match) {
				return sprintf('#%02x%02x%02x', $match[1], $match[2], $match[3]);
			},
			$content
		);
		return $content;
	}

	/**
	 * Cleanup modern color functions (removing unnecessary alpha=1).
	 *
	 * @param string $content CSS content.
	 * @return string Processed CSS.
	 */
	protected function cleanupModernColors($content) {
		$tag = '(rgb|hsl|hwb|(?:(?:ok)?(?:lch|lab)))';
		$content = preg_replace('/' . $tag . '\(\s*([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+\/\s+1(?:(?:\.\d?)*|00%)?\s*\)/i', '$1($2 $3 $4)', $content);
		$content = preg_replace('/' . $tag . '\(\s*[^\s]+\s+[^\s]+\s+[^\s]+\s+\/\s+0(?:[\.0%]*)?\s*\)/i', '#fff0', $content);
		return $content;
	}

	/**
	 * Shorten HEX colors where possible (e.g. #ffffff to #fff).
	 *
	 * @param string $content CSS content.
	 * @return string Processed CSS.
	 */
	protected function shortenHEXColors($content) {
		$content = preg_replace('/(?<=[: ])#([0-9a-f])\\1([0-9a-f])\\2([0-9a-f])\\3(?:([0-9a-f])\\4)?(?=[; }])/i', '#$1$2$3$4', $content);
		$content = preg_replace('/(?<=[: ])#([0-9a-f]{6})ff(?=[; }])/i', '#$1', $content);
		$content = preg_replace('/(?<=[: ])#([0-9a-f]{3})f(?=[; }])/i', '#$1', $content);
		$content = preg_replace('/(?<=[: ])#[0-9a-f]{6}00(?=[; }])/i', '#fff0', $content);

		// Convert some colors to named or shorter hex variants.
		$colors = array(
			'#00f'    => 'blue',    '#dc143c' => 'crimson', '#0ff'    => 'cyan',
			'#8b0000' => 'darkred', '#696969' => 'dimgray', '#ff69b4' => 'hotpink',
			'#0f0'    => 'lime',    '#fdf5e6' => 'oldlace', '#87ceeb' => 'skyblue',
			'#d8bfd8' => 'thistle', '#f0ffff' => 'azure',   '#f5f5dc' => 'beige',
			'#ffe4c4' => 'bisque',  '#a52a2a' => 'brown',   '#ff7f50' => 'coral',
			'#ffd700' => 'gold',    '#808080' => 'gray',    '#008000' => 'green',
			'#4b0082' => 'indigo',  '#fffff0' => 'ivory',   '#f0e68c' => 'khaki',
			'#faf0e6' => 'linen',   '#800000' => 'maroon',  '#000080' => 'navy',
			'#808000' => 'olive',   '#ffa500' => 'orange',  '#da70d6' => 'orchid',
			'#cd853f' => 'peru',    '#ffc0cb' => 'pink',    '#dda0dd' => 'plum',
			'#800080' => 'purple',  '#f00'    => 'red',     '#fa8072' => 'salmon',
			'#a0522d' => 'sienna',  '#c0c0c0' => 'silver',  '#fffafa' => 'snow',
			'#d2b48c' => 'tan',     '#008080' => 'teal',    '#ff6347' => 'tomato',
			'#ee82ee' => 'violet',  '#f5deb3' => 'wheat',   'black'   => '#000',
			'fuchsia' => '#f0f',    'magenta' => '#f0f',    'white'   => '#fff',
			'yellow'  => '#ff0',    'transparent' => '#fff0', 'none'   => '#fff0'
		);

		return preg_replace_callback(
			'/(?<=[: ])(' . implode('|', array_keys($colors)) . ')(?=[; }])/i',
			function ($match) use ($colors) {
				return $colors[strtolower($match[0])];
			},
			$content
		);
	}

	/**
	 * Shorten zero values and remove unnecessary units (e.g., .00px -> 0).
	 *
	 * @param string $content CSS content.
	 * @return string Processed CSS.
	 */
	protected function shortenZeroes($content) {
		$before = '(?<=[:(, ])';
		$after = '(?=[ ,);}])';
		$units = '(em|ex|%|px|cm|mm|in|pt|pc|ch|rem|vh|vw|vmin|vmax|vm)';
		$content = preg_replace('/' . $before . '(-?0*(\.0+)?)(?<=0)px' . $after . '/', '\\1', $content);
		$content = preg_replace('/' . $before . '\.0+' . $units . '?' . $after . '/', '0\\1', $content);
		$content = preg_replace('/' . $before . '(-?[0-9]+\.[0-9]+)0+' . $units . '?' . $after . '/', '\\1\\2', $content);
		$content = preg_replace('/' . $before . '(-?[0-9]+)\.0+' . $units . '?' . $after . '/', '\\1\\2', $content);
		$content = preg_replace('/' . $before . '(-?)0+([0-9]*\.[0-9]+)' . $units . '?' . $after . '/', '\\1\\2\\3', $content);
		$content = preg_replace('/' . $before . '-?0+' . $units . '?' . $after . '/', '0\\1', $content);
		$content = preg_replace('/flex:([0-9]+\s[0-9]+\s)0([;\}])/', 'flex:${1}0%${2}', $content);
		$content = preg_replace('/flex-basis:0([;\}])/', 'flex-basis:0%${1}', $content);
		return $content;
	}

	/**
	 * Shorten font-weight names to numeric values (normal->400, bold->700).
	 *
	 * @param string $content CSS content.
	 * @return string Processed CSS.
	 */
	protected function shortenFontWeights($content) {
		$weights = array(
			'normal' => 400,
			'bold' => 700,
		);
		$callback = function ($match) use ($weights) {
			return $match[1] . $weights[$match[2]];
		};
		return preg_replace_callback('/(font-weight\s*:\s*)(' . implode('|', array_keys($weights)) . ')(?=[;}])/', $callback, $content);
	}

	/**
	 * Strip empty CSS blocks (e.g. selectors with no declarations).
	 *
	 * @param string $content CSS content.
	 * @return string Processed CSS.
	 */
	protected function stripEmptyTags($content) {
		$content = preg_replace('/(?<=^)[^\{\};]+\{\s*\}/', '', $content);
		$content = preg_replace('/(?<=(\}|;))[^\{\};]+\{\s*\}/', '', $content);
		return $content;
	}

	/**
	 * Extract JavaScript regex patterns.
	 *
	 * @return void
	 */
	protected function extractRegex() {
		$minifier = $this;
		$callback = function ($match) use ($minifier) {
			$count = count($minifier->extracted);
			$placeholder = '"' . $count . '"';
			$minifier->extracted[$placeholder] = $match[0];
			return $placeholder;
		};
		$pattern = '\\/(?!\*)(?:[^\\[\\/\\\\\n\r]++|(?:\\\\.)++|(?:\\[(?:[^\\]\\\\\n\r]++|(?:\\\\.)++)++\\])++)++\\/[gimuy]*';
		$keywords = array('do', 'in', 'new', 'else', 'throw', 'yield', 'delete', 'return', 'typeof');
		$before = '(^|[=:,;\+\-\*\?\/\}\(\{\[&\|!]|' . implode('|', $keywords) . ')\s*';
		$propertiesAndMethods = array( 'constructor', 'flags', 'global', 'ignoreCase', 'multiline', 'source', 'sticky', 'unicode', 'compile(', 'exec(', 'test(', 'toSource(', 'toString(' );
		$delimiters = array_fill(0, count($propertiesAndMethods), '/');
		$propertiesAndMethods = array_map('preg_quote', $propertiesAndMethods, $delimiters);
		$after = '(?=\s*([\.,;:\)\}&\|+]|\/\/|$|\.(' . implode('|', $propertiesAndMethods) . ')))';
		$this->registerPattern('/' . $before . '\K' . $pattern . $after . '/', $callback);

		$before = '\)\s*';
		$after = '(?=\s*\.\(' . implode('|', $propertiesAndMethods) . ')';
		$this->registerPattern('/' . $before . '\K' . $pattern . $after . '/', $callback);

		$operators = $this->getOperatorsForRegex($this->operatorsBefore, '/');
		$operators += $this->getOperatorsForRegex($this->keywordsReserved, '/');
		$after = '(?=\s*\n\s*(' . implode('|', $operators) . '))';
		$this->registerPattern('/' . $pattern . $after . '/', $callback);
	}

	/**
	 * Generate regex-friendly list of operators.
	 *
	 * @param array  $operators Operators to process.
	 * @param string $delimiter Delimiter for preg_quote.
	 * @return array Operators as regex patterns.
	 */
	protected function getOperatorsForRegex(array $operators, $delimiter = '/') {
		$delimiters = array_fill(0, count($operators), $delimiter);
		$escaped = array_map('preg_quote', $operators, $delimiters);
		$operators = array_combine($operators, $escaped);
		unset($operators['+'], $operators['-']);
		$operators['.'] = '(?<![0-9]\s)\.';
		$chars = preg_quote('+-*\=<>%&|', $delimiter);
		$operators['='] = '(?<![' . $chars . '])\=';
		return $operators;
	}

	/**
	 * Convert property access from bracket notation to dot notation when possible.
	 *
	 * @param string $content JS content.
	 * @return string Processed JS.
	 */
	protected function propertyNotation($content) {
		$minifier = $this;
		$keywords = $this->keywordsReserved;
		$callback = function ($match) use ($minifier, $keywords) {
			$property = trim($minifier->extracted[$match[1]], '\'"');
			if (in_array($property, $keywords)) {
				return $match[0];
			}
			if (!preg_match('/^' . $minifier::REGEX_VARIABLE . '$/u', $property)) {
				return $match[0];
			}
			return '.' . $property;
		};
		preg_match('/(\[[^\]]+\])[^\]]*$/', static::REGEX_VARIABLE, $previousChar);
		$previousChar = $previousChar[1];
		$keywords = $this->getKeywordsForRegex($keywords);
		$keywords = '(?<!' . implode(')(?<!', $keywords) . ')';
		return preg_replace_callback('/(?<=' . $previousChar . '|\])' . $keywords . '\[\s*(([\'"])[0-9]+\\2)\s*\]/u', $callback, $content);
	}

	/**
	 * Convert boolean literals true/false to shorter forms !0 and !1.
	 *
	 * @param string $content JS content.
	 * @return string Processed JS.
	 */
	protected function shortenBools($content) {
		$callback = function ($match) {
			if (trim($match[1]) === '.') {
				return $match[0];
			}
			return $match[1] . ($match[2] === 'true' ? '!0' : '!1');
		};
		$content = preg_replace_callback('/(^|.\s*)\b(true|false)\b(?!:)/', $callback, $content);
		$content = preg_replace('/\bwhile\(!0\){/', 'for(;;){', $content);

		// Handle do/while loops rewritten as for(;;), ensuring proper logic.
		preg_match_all('/\bdo\b/', $content, $dos, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		$dos = array_reverse($dos);
		foreach ($dos as $do) {
			$offsetDo = $do[0][1];
			preg_match_all('/\bfor\(;;\)/', $content, $whiles, PREG_OFFSET_CAPTURE | PREG_SET_ORDER, $offsetDo);
			foreach ($whiles as $while) {
				$offsetWhile = $while[0][1];
				$open = substr_count($content, '{', $offsetDo, $offsetWhile - $offsetDo);
				$close = substr_count($content, '}', $offsetDo, $offsetWhile - $offsetDo);
				if ($open === $close) {
					$content = substr_replace($content, 'while(!0)', $offsetWhile, strlen('for(;;)'));
					break;
				}
			}
		}

		return $content;
	}

	/**
	 * Strip unnecessary whitespace from JS content.
	 *
	 * @param string $content JS content.
	 * @return string Minified JS content.
	 */
	protected function stripWhitespaceJS($content) {
		$content = str_replace(array("\r\n", "\r"), "\n", $content);
		$content = preg_replace('/[^\S\n]+/', ' ', $content);
		$content = str_replace(array(" \n", "\n "), "\n", $content);
		$content = preg_replace('/\n+/', "\n", $content);

		$operatorsBefore = $this->getOperatorsForRegex($this->operatorsBefore, '/');
		$operatorsAfter = $this->getOperatorsForRegex($this->operatorsAfter, '/');
		$operators = $this->getOperatorsForRegex($this->operators, '/');
		$keywordsBefore = $this->getKeywordsForRegex($this->keywordsBefore, '/');
		$keywordsAfter = $this->getKeywordsForRegex($this->keywordsAfter, '/');

		// Adjust spacing around operators and keywords.
		$content = preg_replace(
			array('/(' . implode('|', $operatorsBefore) . ')\s+/', '/\s+(' . implode('|', $operatorsAfter) . ')/'),
			'\\1',
			$content
		);

		$content = preg_replace(
			array('/(?<![\+\-])\s*([\+\-])(?![\+\-])/', '/(?<![\+\-])([\+\-])\s*(?![\+\-])/'),
			'\\1',
			$content
		);

		$content = preg_replace('/(^|[;\}\s])\K(' . implode('|', $keywordsBefore) . ')\s+/', '\\2 ', $content);
		$content = preg_replace('/\s+(' . implode('|', $keywordsAfter) . ')(?=([;\{\s]|$))/', ' \\1', $content);

		$operatorsDiffBefore = array_diff($operators, $operatorsBefore);
		$operatorsDiffAfter = array_diff($operators, $operatorsAfter);
		$content = preg_replace('/(' . implode('|', $operatorsDiffBefore) . ')[^\S\n]+/', '\\1', $content);
		$content = preg_replace('/[^\S\n]+(' . implode('|', $operatorsDiffAfter) . ')/', '\\1', $content);

		$content = preg_replace('/\breturn\s+(["\'\/\+\-])/', 'return$1', $content);
		$content = preg_replace('/\)\s+\{/', '){', $content);
		$content = preg_replace('/}\n(else|catch|finally)\b/', '}$1', $content);
		$content = preg_replace('/\bfor\(([^;]*);;([^;]*)\)/', 'for(\\1;-;\\2)', $content);
		$content = preg_replace('/;+/', ';', $content);
		$content = preg_replace('/\bfor\(([^;]*);-;([^;]*)\)/', 'for(\\1;;\\2)', $content);

		// Additional cleanup of JS loops and conditions.
		$content = preg_replace('/(for\((?:[^;\{]*|[^;\{]*function[^;\{]*(\{([^\{\}]*(?-2))*[^\{\}]*\})?[^;\{]*);[^;\{]*;[^;\{]*\));(\}|$)/s', '\\1;;\\4', $content);
		$content = preg_replace('/(for\([^;\{]*;(?:[^;\{]*|[^;\{]*function[^;\{]*(\{([^\{\}]*(?-2))*[^\{\}]*\})?[^;\{]*);[^;\{]*\));(\}|$)/s', '\\1;;\\4', $content);
		$content = preg_replace('/(for\([^;\{]*;[^;\{]*;(?:[^;\{]*|[^;\{]*function[^;\{]*(\{([^\{\}]*(?-2))*[^\{\}]*\})?[^;\{]*)\));(\}|$)/s', '\\1;;\\4', $content);
		$content = preg_replace('/(for\([^;\{]+\s+in\s+[^;\{]+\));(\}|$)/s', '\\1;;\\2', $content);
		$content = preg_replace('/(\bif\s*\([^{;]*\));\}/s', '\\1;;}', $content);
		$content = preg_replace('/(while\([^;\{]+\));(\}|$)/s', '\\1;;\\2', $content);
		$content = preg_replace('/else;/s', '', $content);
		$content = preg_replace('/;(\}|$)/s', '\\1', $content);

		$content = ltrim($content, ';');
		return trim($content);
	}

	/**
	 * Safely replace the first occurrence of a search string with a replacement.
	 *
	 * @param string $search  Search substring.
	 * @param string $replace Replacement substring.
	 * @param string $subject String to search in.
	 * @return string Modified string.
	 */
	protected static function str_replace_first($search, $replace, $subject) {
		$pos = strpos($subject, $search);
		if ($pos !== false) {
			return substr_replace($subject, $replace, $pos, strlen($search));
		}
		return $subject;
	}

	/**
	 * Generate regex-friendly list of keywords.
	 *
	 * @param array  $keywords  Keywords array.
	 * @param string $delimiter Delimiter for preg_quote.
	 * @return array Keywords as regex patterns.
	 */
	protected function getKeywordsForRegex(array $keywords, $delimiter = '/') {
		$delimiter = array_fill(0, count($keywords), $delimiter);
		$escaped = array_map('preg_quote', $keywords, $delimiter);
		$keywords = array_combine($keywords, $escaped);
		return $keywords;
	}
}
