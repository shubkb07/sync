<?php

define( 'OBJECT', 'OBJECT' );
define( 'OBJECT_K', 'OBJECT_K' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'ARRAY_N', 'ARRAY_N' );

#[AllowDynamicProperties]
class db {

	public $show_errors = false;

	public $suppress_errors = false;

	public $last_error = '';

	public $num_queries = 0;

	public $num_rows = 0;

	public $rows_affected = 0;

	public $insert_id = 0;

	public $last_query;

	public $last_result;

	protected $result;

	protected $col_meta = array();

	protected $table_charset = array();

	protected $check_current_query = true;

	private $checking_collation = false;

	protected $col_info;

	public $queries;

	protected $reconnect_retries = 5;

	public $prefix = '';

	public $base_prefix;

	public $ready = false;

	public $blogid = 0;

	public $siteid = 0;

	public $tables = array(
		'posts',
		'comments',
		'links',
		'options',
		'postmeta',
		'terms',
		'term_taxonomy',
		'term_relationships',
		'termmeta',
		'commentmeta',
	);

	public $old_tables = array( 'categories', 'post2cat', 'link2cat' );

	public $global_tables = array( 'users', 'usermeta' );

	public $ms_global_tables = array(
		'blogs',
		'blogmeta',
		'signups',
		'site',
		'sitemeta',
		'registration_log',
	);

	public $old_ms_global_tables = array( 'sitecategories' );

	public $comments;

	public $commentmeta;

	public $links;

	public $options;

	public $postmeta;

	public $posts;

	public $terms;

	public $term_relationships;

	public $term_taxonomy;

	public $termmeta;

	public $usermeta;

	public $users;

	public $blogs;

	public $blogmeta;

	public $registration_log;

	public $signups;

	public $site;

	public $sitecategories;

	public $sitemeta;

	public $field_types = array();

	public $charset;

	public $collate;

	protected $dbuser;

	protected $dbpassword;

	protected $dbname;

	protected $dbhost;

	protected $dbh;

	public $func_call;

	public $is_mysql = null;

	protected $incompatible_modes = array(
		'NO_ZERO_DATE',
		'ONLY_FULL_GROUP_BY',
		'STRICT_TRANS_TABLES',
		'STRICT_ALL_TABLES',
		'TRADITIONAL',
		'ANSI',
	);

	private $allow_unsafe_unquoted_parameters = true;

	private $use_mysqli = true;

	private $has_connected = false;

	public $time_start = null;

	public $error = null;

	public function __construct( $dbuser, $dbpassword, $dbname, $dbhost ) {
		if ( DEBUG && DEBUG_DISPLAY ) {
			$this->show_errors();
		}

		$this->dbuser     = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname     = $dbname;
		$this->dbhost     = $dbhost;

		if ( defined( 'SETUP_CONFIG' ) ) {
			return;
		}

		$this->db_connect();
	}

	public function __get( $name ) {
		if ( 'col_info' === $name ) {
			$this->load_col_info();
		}

		return $this->$name;
	}

	public function __set( $name, $value ) {
		$protected_members = array(
			'col_meta',
			'table_charset',
			'check_current_query',
			'allow_unsafe_unquoted_parameters',
		);
		if ( in_array( $name, $protected_members, true ) ) {
			return;
		}
		$this->$name = $value;
	}

	public function __isset( $name ) {
		return isset( $this->$name );
	}

	public function __unset( $name ) {
		unset( $this->$name );
	}

	public function init_charset() {
		$charset = '';
		$collate = '';

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$charset = 'utf8';
			if ( defined( 'DB_COLLATE' ) && DB_COLLATE ) {
				$collate = DB_COLLATE;
			} else {
				$collate = 'utf8_general_ci';
			}
		} elseif ( defined( 'DB_COLLATE' ) ) {
			$collate = DB_COLLATE;
		}

		if ( defined( 'DB_CHARSET' ) ) {
			$charset = DB_CHARSET;
		}

		$charset_collate = $this->determine_charset( $charset, $collate );

		$this->charset = $charset_collate['charset'];
		$this->collate = $charset_collate['collate'];
	}

	public function determine_charset( $charset, $collate ) {
		if ( ( ! ( $this->dbh instanceof mysqli ) ) || empty( $this->dbh ) ) {
			return compact( 'charset', 'collate' );
		}

		if ( 'utf8' === $charset ) {
			$charset = 'utf8mb4';
		}

		if ( 'utf8mb4' === $charset ) {

			if ( ! $collate || 'utf8_general_ci' === $collate ) {
				$collate = 'utf8mb4_unicode_ci';
			} else {
				$collate = str_replace( 'utf8_', 'utf8mb4_', $collate );
			}
		}

		if ( $this->has_cap( 'utf8mb4_520' ) && 'utf8mb4_unicode_ci' === $collate ) {
			$collate = 'utf8mb4_unicode_520_ci';
		}

		return compact( 'charset', 'collate' );
	}

	public function set_charset( $dbh, $charset = null, $collate = null ) {
		if ( ! isset( $charset ) ) {
			$charset = $this->charset;
		}
		if ( ! isset( $collate ) ) {
			$collate = $this->collate;
		}
		if ( $this->has_cap( 'collation' ) && ! empty( $charset ) ) {
			$set_charset_succeeded = true;

			if ( function_exists( 'mysqli_set_charset' ) && $this->has_cap( 'set_charset' ) ) {
				$set_charset_succeeded = mysqli_set_charset( $dbh, $charset );
			}

			if ( $set_charset_succeeded ) {
				$query = $this->prepare( 'SET NAMES %s', $charset );
				if ( ! empty( $collate ) ) {
					$query .= $this->prepare( ' COLLATE %s', $collate );
				}
				mysqli_query( $dbh, $query );
			}
		}
	}

	public function set_sql_mode( $modes = array() ) {
		if ( empty( $modes ) ) {
			$res = mysqli_query( $this->dbh, 'SELECT @@SESSION.sql_mode' );

			if ( empty( $res ) ) {
				return;
			}

			$modes_array = mysqli_fetch_array( $res );

			if ( empty( $modes_array[0] ) ) {
				return;
			}

			$modes_str = $modes_array[0];

			if ( empty( $modes_str ) ) {
				return;
			}

			$modes = explode( ',', $modes_str );
		}

		$modes = array_change_key_case( $modes, CASE_UPPER );

		$incompatible_modes = (array) apply_filters( 'incompatible_sql_modes', $this->incompatible_modes );

		foreach ( $modes as $i => $mode ) {
			if ( in_array( $mode, $incompatible_modes, true ) ) {
				unset( $modes[ $i ] );
			}
		}

		$modes_str = implode( ',', $modes );

		mysqli_query( $this->dbh, "SET SESSION sql_mode='$modes_str'" );
	}

	public function set_prefix( $prefix, $set_table_names = true ) {

		if ( preg_match( '|[^a-z0-9_]|i', $prefix ) ) {
			return new Sync_Error( 'invalid_db_prefix', 'Invalid database prefix' );
		}

		$old_prefix = is_multisite() ? '' : $prefix;

		if ( isset( $this->base_prefix ) ) {
			$old_prefix = $this->base_prefix;
		}

		$this->base_prefix = $prefix;

		if ( $set_table_names ) {
			foreach ( $this->tables( 'global' ) as $table => $prefixed_table ) {
				$this->$table = $prefixed_table;
			}

			if ( is_multisite() && empty( $this->blogid ) ) {
				return $old_prefix;
			}

			$this->prefix = $this->get_blog_prefix();

			foreach ( $this->tables( 'blog' ) as $table => $prefixed_table ) {
				$this->$table = $prefixed_table;
			}

			foreach ( $this->tables( 'old' ) as $table => $prefixed_table ) {
				$this->$table = $prefixed_table;
			}
		}
		return $old_prefix;
	}

	public function set_blog_id( $blog_id, $network_id = 0 ) {
		if ( ! empty( $network_id ) ) {
			$this->siteid = $network_id;
		}

		$old_blog_id  = $this->blogid;
		$this->blogid = $blog_id;

		$this->prefix = $this->get_blog_prefix();

		foreach ( $this->tables( 'blog' ) as $table => $prefixed_table ) {
			$this->$table = $prefixed_table;
		}

		foreach ( $this->tables( 'old' ) as $table => $prefixed_table ) {
			$this->$table = $prefixed_table;
		}

		return $old_blog_id;
	}

	public function get_blog_prefix( $blog_id = null ) {
		if ( is_multisite() ) {
			if ( null === $blog_id ) {
				$blog_id = $this->blogid;
			}

			$blog_id = (int) $blog_id;

			if ( defined( 'MULTISITE' ) && ( 0 === $blog_id || 1 === $blog_id ) ) {
				return $this->base_prefix;
			} else {
				return $this->base_prefix . $blog_id . '_';
			}
		} else {
			return $this->base_prefix;
		}
	}

	public function tables( $scope = 'all', $prefix = true, $blog_id = 0 ) {
		switch ( $scope ) {
			case 'all':
				$tables = array_merge( $this->global_tables, $this->tables );
				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->ms_global_tables );
				}
				break;
			case 'blog':
				$tables = $this->tables;
				break;
			case 'global':
				$tables = $this->global_tables;
				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->ms_global_tables );
				}
				break;
			case 'ms_global':
				$tables = $this->ms_global_tables;
				break;
			case 'old':
				$tables = $this->old_tables;
				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->old_ms_global_tables );
				}
				break;
			default:
				return array();
		}

		if ( $prefix ) {
			if ( ! $blog_id ) {
				$blog_id = $this->blogid;
			}
			$blog_prefix   = $this->get_blog_prefix( $blog_id );
			$base_prefix   = $this->base_prefix;
			$global_tables = array_merge( $this->global_tables, $this->ms_global_tables );
			foreach ( $tables as $k => $table ) {
				if ( in_array( $table, $global_tables, true ) ) {
					$tables[ $table ] = $base_prefix . $table;
				} else {
					$tables[ $table ] = $blog_prefix . $table;
				}
				unset( $tables[ $k ] );
			}

			if ( isset( $tables['users'] ) && defined( 'CUSTOM_USER_TABLE' ) ) {
				$tables['users'] = CUSTOM_USER_TABLE;
			}

			if ( isset( $tables['usermeta'] ) && defined( 'CUSTOM_USER_META_TABLE' ) ) {
				$tables['usermeta'] = CUSTOM_USER_META_TABLE;
			}
		}

		return $tables;
	}

	public function select( $db, $dbh = null ) {
		if ( is_null( $dbh ) ) {
			$dbh = $this->dbh;
		}

		$success = mysqli_select_db( $dbh, $db );

		if ( ! $success ) {
			$this->ready = false;
			if ( ! did_action( 'template_redirect' ) ) {
				load_translations_early();

				$message = '<h1>' . __( 'Cannot select database' ) . "</h1>\n";

				$message .= '<p>' . sprintf(

					__( 'The database server could be connected to (which means your username and password is okay) but the %s database could not be selected.' ),
					'<code>' . htmlspecialchars( $db, ENT_QUOTES ) . '</code>'
				) . "</p>\n";

				$message .= "<ul>\n";
				$message .= '<li>' . __( 'Are you sure it exists?' ) . "</li>\n";

				$message .= '<li>' . sprintf(

					__( 'Does the user %1$s have permission to use the %2$s database?' ),
					'<code>' . htmlspecialchars( $this->dbuser, ENT_QUOTES ) . '</code>',
					'<code>' . htmlspecialchars( $db, ENT_QUOTES ) . '</code>'
				) . "</li>\n";

				$message .= '<li>' . sprintf(

					__( 'On some systems the name of your database is prefixed with your username, so it would be like <code>username_%1$s</code>. Could that be the problem?' ),
					htmlspecialchars( $db, ENT_QUOTES )
				) . "</li>\n";

				$message .= "</ul>\n";

				$message .= '<p>' . __( 'If you do not know how to set up a database you should <strong>contact your host</strong>.' ) . "</p>\n";

				$this->bail( $message, 'db_select_fail' );
			}
		}
	}

	public function _weak_escape( $data ) {
		if ( func_num_args() === 1 && function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __METHOD__, '3.6.0', 'db::prepare() or esc_sql()' );
		}
		return addslashes( $data );
	}

	public function _real_escape( $data ) {
		if ( ! is_scalar( $data ) ) {
			return '';
		}

		if ( $this->dbh ) {
			$escaped = mysqli_real_escape_string( $this->dbh, $data );
		} else {
			$class = get_class( $this );

			load_translations_early();

			_doing_it_wrong( $class, sprintf( __( '%s must set a database connection for use with escaping.' ), $class ), '3.6.0' );

			$escaped = addslashes( $data );
		}

		return $this->add_placeholder_escape( $escaped );
	}

	public function _escape( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				if ( is_array( $v ) ) {
					$data[ $k ] = $this->_escape( $v );
				} else {
					$data[ $k ] = $this->_real_escape( $v );
				}
			}
		} else {
			$data = $this->_real_escape( $data );
		}

		return $data;
	}

	public function escape( $data ) {
		if ( func_num_args() === 1 && function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __METHOD__, '3.6.0', 'db::prepare() or esc_sql()' );
		}
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				if ( is_array( $v ) ) {
					$data[ $k ] = $this->escape( $v, 'recursive' );
				} else {
					$data[ $k ] = $this->_weak_escape( $v, 'internal' );
				}
			}
		} else {
			$data = $this->_weak_escape( $data, 'internal' );
		}

		return $data;
	}

	public function escape_by_ref( &$data ) {
		if ( ! is_float( $data ) ) {
			$data = $this->_real_escape( $data );
		}
	}

	public function quote_identifier( $identifier ) {
		return '`' . $this->_escape_identifier_value( $identifier ) . '`';
	}

	private function _escape_identifier_value( $identifier ) {
		return str_replace( '`', '``', $identifier );
	}

	public function prepare( $query, ...$args ) {
		if ( is_null( $query ) ) {
			return;
		}

		if ( false === strpos( $query, '%' ) ) {
			load_translations_early();
			_doing_it_wrong(
				'db::prepare',
				sprintf(

					__( 'The query argument of %s must have a placeholder.' ),
					'db::prepare()'
				),
				'3.9.0'
			);
		}

		$allowed_format = '(?:[1-9][0-9]*[$])?[-+0-9]*(?: |0|\'.)?[-+0-9]*(?:\.[0-9]+)?';

		$query = str_replace( "'%s'", '%s', $query ); 
		$query = str_replace( '"%s"', '%s', $query ); 

		$query = preg_replace( "/%(?:%|$|(?!($allowed_format)?[sdfFi]))/", '%%\\1', $query );

		$split_query = preg_split( "/(^|[^%]|(?:%%)+)(%(?:$allowed_format)?[sdfFi])/", $query, -1, PREG_SPLIT_DELIM_CAPTURE );

		$split_query_count = count( $split_query );

		$placeholder_count = ( ( $split_query_count - 1 ) / 3 );

		$passed_as_array = ( isset( $args[0] ) && is_array( $args[0] ) && 1 === count( $args ) );
		if ( $passed_as_array ) {
			$args = $args[0];
		}

		$new_query       = '';
		$key             = 2; 
		$arg_id          = 0;
		$arg_identifiers = array();
		$arg_strings     = array();

		while ( $key < $split_query_count ) {
			$placeholder = $split_query[ $key ];

			$format = substr( $placeholder, 1, -1 );
			$type   = substr( $placeholder, -1 );

			if ( 'f' === $type && true === $this->allow_unsafe_unquoted_parameters

				&& '%' === substr( $split_query[ $key - 1 ], -1, 1 )
			) {

				$s = $split_query[ $key - 2 ] . $split_query[ $key - 1 ];
				$k = 1;
				$l = strlen( $s );
				while ( $k <= $l && '%' === $s[ $l - $k ] ) {
					++$k;
				}

				$placeholder = '%' . ( $k % 2 ? '%' : '' ) . $format . $type;

				--$placeholder_count;

			} else {

				if ( 'f' === $type ) {
					$type        = 'F';
					$placeholder = '%' . $format . $type;
				}

				if ( 'i' === $type ) {
					$placeholder = '`%' . $format . 's`';

					$argnum_pos = strpos( $format, '$' );

					if ( false !== $argnum_pos ) {

						$arg_identifiers[] = ( ( (int) substr( $format, 0, $argnum_pos ) ) - 1 );
					} else {
						$arg_identifiers[] = $arg_id;
					}
				} elseif ( 'd' !== $type && 'F' !== $type ) {

					$argnum_pos = strpos( $format, '$' );

					if ( false !== $argnum_pos ) {
						$arg_strings[] = ( ( (int) substr( $format, 0, $argnum_pos ) ) - 1 );
					} else {
						$arg_strings[] = $arg_id;
					}

					if ( true !== $this->allow_unsafe_unquoted_parameters

						|| ( '' === $format && '%' !== substr( $split_query[ $key - 1 ], -1, 1 ) )
					) {
						$placeholder = "'%" . $format . "s'";
					}
				}
			}

			$new_query .= $split_query[ $key - 2 ] . $split_query[ $key - 1 ] . $placeholder;

			$key += 3;
			++$arg_id;
		}

		$query = $new_query . $split_query[ $key - 2 ];

		$dual_use = array_intersect( $arg_identifiers, $arg_strings );

		if ( count( $dual_use ) > 0 ) {
			load_translations_early();

			$used_placeholders = array();

			$key    = 2;
			$arg_id = 0;

			while ( $key < $split_query_count ) {
				$placeholder = $split_query[ $key ];

				$format = substr( $placeholder, 1, -1 );

				$argnum_pos = strpos( $format, '$' );

				if ( false !== $argnum_pos ) {
					$arg_pos = ( ( (int) substr( $format, 0, $argnum_pos ) ) - 1 );
				} else {
					$arg_pos = $arg_id;
				}

				$used_placeholders[ $arg_pos ][] = $placeholder;

				$key += 3;
				++$arg_id;
			}

			$conflicts = array();
			foreach ( $dual_use as $arg_pos ) {
				$conflicts[] = implode( ' and ', $used_placeholders[ $arg_pos ] );
			}

			_doing_it_wrong(
				'db::prepare',
				sprintf(

					__( 'Arguments cannot be prepared as both an Identifier and Value. Found the following conflicts: %s' ),
					implode( ', ', $conflicts )
				),
				'6.2.0'
			);

			return;
		}

		$args_count = count( $args );

		if ( $args_count !== $placeholder_count ) {
			if ( 1 === $placeholder_count && $passed_as_array ) {

				load_translations_early();
				_doing_it_wrong(
					'db::prepare',
					__( 'The query only expected one placeholder, but an array of multiple placeholders was sent.' ),
					'4.9.0'
				);

				return;
			} else {

				load_translations_early();
				_doing_it_wrong(
					'db::prepare',
					sprintf(

						__( 'The query does not contain the correct number of placeholders (%1$d) for the number of arguments passed (%2$d).' ),
						$placeholder_count,
						$args_count
					),
					'4.8.3'
				);

				if ( $args_count < $placeholder_count ) {
					$max_numbered_placeholder = 0;

					for ( $i = 2, $l = $split_query_count; $i < $l; $i += 3 ) {

						$argnum = (int) substr( $split_query[ $i ], 1 );

						if ( $max_numbered_placeholder < $argnum ) {
							$max_numbered_placeholder = $argnum;
						}
					}

					if ( ! $max_numbered_placeholder || $args_count < $max_numbered_placeholder ) {
						return '';
					}
				}
			}
		}

		$args_escaped = array();

		foreach ( $args as $i => $value ) {
			if ( in_array( $i, $arg_identifiers, true ) ) {
				$args_escaped[] = $this->_escape_identifier_value( $value );
			} elseif ( is_int( $value ) || is_float( $value ) ) {
				$args_escaped[] = $value;
			} else {
				if ( ! is_scalar( $value ) && ! is_null( $value ) ) {
					load_translations_early();
					_doing_it_wrong(
						'db::prepare',
						sprintf(

							__( 'Unsupported value type (%s).' ),
							gettype( $value )
						),
						'4.8.2'
					);

					$value = '';
				}

				$args_escaped[] = $this->_real_escape( $value );
			}
		}

		$query = vsprintf( $query, $args_escaped );

		return $this->add_placeholder_escape( $query );
	}

	public function esc_like( $text ) {
		return addcslashes( $text, '_%\\' );
	}

	public function print_error( $str = '' ) {
		global $EZSQL_ERROR;

		if ( ! $str ) {
			$str = mysqli_error( $this->dbh );
		}

		$EZSQL_ERROR[] = array(
			'query'     => $this->last_query,
			'error_str' => $str,
		);

		if ( $this->suppress_errors ) {
			return false;
		}

		$caller = $this->get_caller();
		if ( $caller ) {

			$error_str = sprintf( 'Sync Site database error %1$s for query %2$s made by %3$s', $str, $this->last_query, $caller );
		} else {
			$error_str = sprintf( 'Sync Site database error %1$s for query %2$s', $str, $this->last_query );
		}

		error_log( $error_str );

		if ( ! $this->show_errors ) {
			return false;
		}

		load_translations_early();

		if ( is_multisite() ) {
			$msg = sprintf(
				"%s [%s]\n%s\n",
				__( 'Sync Site database error:' ),
				$str,
				$this->last_query
			);

			if ( defined( 'ERRORLOGFILE' ) ) {
				error_log( $msg, 3, ERRORLOGFILE );
			}
			if ( defined( 'DIEONDBERROR' ) ) {
				sync_die( $msg );
			}
		} else {
			$str   = htmlspecialchars( $str, ENT_QUOTES );
			$query = htmlspecialchars( $this->last_query, ENT_QUOTES );

			printf(
				'<div id="error"><p class="dberror"><strong>%s</strong> [%s]<br /><code>%s</code></p></div>',
				__( 'Sync Site database error:' ),
				$str,
				$query
			);
		}
	}

	public function show_errors( $show = true ) {
		$errors            = $this->show_errors;
		$this->show_errors = $show;
		return $errors;
	}

	public function hide_errors() {
		$show              = $this->show_errors;
		$this->show_errors = false;
		return $show;
	}

	public function suppress_errors( $suppress = true ) {
		$errors                = $this->suppress_errors;
		$this->suppress_errors = (bool) $suppress;
		return $errors;
	}

	public function flush() {
		$this->last_result   = array();
		$this->col_info      = null;
		$this->last_query    = null;
		$this->rows_affected = 0;
		$this->num_rows      = 0;
		$this->last_error    = '';

		if ( $this->result instanceof mysqli_result ) {
			mysqli_free_result( $this->result );
			$this->result = null;

			if ( empty( $this->dbh ) || ! ( $this->dbh instanceof mysqli ) ) {
				return;
			}

			while ( mysqli_more_results( $this->dbh ) ) {
				mysqli_next_result( $this->dbh );
			}
		}
	}

	public function db_connect( $allow_bail = true ) {
		$this->is_mysql = true;

		$client_flags = defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0;

		mysqli_report( MYSQLI_REPORT_OFF );

		$this->dbh = mysqli_init();

		$host    = $this->dbhost;
		$port    = null;
		$socket  = null;
		$is_ipv6 = false;

		$host_data = $this->parse_db_host( $this->dbhost );
		if ( $host_data ) {
			list( $host, $port, $socket, $is_ipv6 ) = $host_data;
		}

		if ( $is_ipv6 && extension_loaded( 'mysqlnd' ) ) {
			$host = "[$host]";
		}

		if ( DEBUG ) {
			mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags );
		} else {
			@mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags );
		}

		if ( $this->dbh->connect_errno ) {
			$this->dbh = null;
		}

		if ( ! $this->dbh && $allow_bail ) {
			load_translations_early();

			if ( file_exists( CONTENT_DIR . '/db-error.php' ) ) {
				require_once CONTENT_DIR . '/db-error.php';
				die();
			}

			$message = '<h1>' . __( 'Error establishing a database connection' ) . "</h1>\n";

			$message .= '<p>' . sprintf(

				__( 'This either means that the username and password information in your %1$s file is incorrect or that contact with the database server at %2$s could not be established. This could mean your host&#8217;s database server is down.' ),
				'<code>sync-config.php</code>',
				'<code>' . htmlspecialchars( $this->dbhost, ENT_QUOTES ) . '</code>'
			) . "</p>\n";

			$message .= "<ul>\n";
			$message .= '<li>' . __( 'Are you sure you have the correct username and password?' ) . "</li>\n";
			$message .= '<li>' . __( 'Are you sure you have typed the correct hostname?' ) . "</li>\n";
			$message .= '<li>' . __( 'Are you sure the database server is running?' ) . "</li>\n";
			$message .= "</ul>\n";

			$message .= '<p>' . __( 'If you are unsure what these terms mean you should probably contact your host.' ) . "</p>\n";

			$this->bail( $message, 'db_connect_fail' );

			return false;
		} elseif ( $this->dbh ) {
			if ( ! $this->has_connected ) {
				$this->init_charset();
			}

			$this->has_connected = true;

			$this->set_charset( $this->dbh );

			$this->ready = true;
			$this->set_sql_mode();
			$this->select( $this->dbname, $this->dbh );

			return true;
		}

		return false;
	}

	public function parse_db_host( $host ) {
		$socket  = null;
		$is_ipv6 = false;

		$socket_pos = strpos( $host, ':/' );
		if ( false !== $socket_pos ) {
			$socket = substr( $host, $socket_pos + 1 );
			$host   = substr( $host, 0, $socket_pos );
		}

		if ( substr_count( $host, ':' ) > 1 ) {
			$pattern = '#^(?:\[)?(?P<host>[0-9a-fA-F:]+)(?:\]:(?P<port>[\d]+))?#';
			$is_ipv6 = true;
		} else {

			$pattern = '#^(?P<host>[^:/]*)(?::(?P<port>[\d]+))?#';
		}

		$matches = array();
		$result  = preg_match( $pattern, $host, $matches );

		if ( 1 !== $result ) {

			return false;
		}

		$host = ! empty( $matches['host'] ) ? $matches['host'] : '';

		$port = ! empty( $matches['port'] ) ? absint( $matches['port'] ) : null;

		return array( $host, $port, $socket, $is_ipv6 );
	}

	public function check_connection( $allow_bail = true ) {
		if ( ! empty( $this->dbh ) && mysqli_ping( $this->dbh ) ) {
			return true;
		}

		$error_reporting = false;

		if ( DEBUG ) {
			$error_reporting = error_reporting();
			error_reporting( $error_reporting & ~E_WARNING );
		}

		for ( $tries = 1; $tries <= $this->reconnect_retries; $tries++ ) {

			if ( $this->reconnect_retries === $tries && DEBUG ) {
				error_reporting( $error_reporting );
			}

			if ( $this->db_connect( false ) ) {
				if ( $error_reporting ) {
					error_reporting( $error_reporting );
				}

				return true;
			}

			sleep( 1 );
		}

		if ( did_action( 'template_redirect' ) ) {
			return false;
		}

		if ( ! $allow_bail ) {
			return false;
		}

		load_translations_early();

		$message = '<h1>' . __( 'Error reconnecting to the database' ) . "</h1>\n";

		$message .= '<p>' . sprintf(

			__( 'This means that the contact with the database server at %s was lost. This could mean your host&#8217;s database server is down.' ),
			'<code>' . htmlspecialchars( $this->dbhost, ENT_QUOTES ) . '</code>'
		) . "</p>\n";

		$message .= "<ul>\n";
		$message .= '<li>' . __( 'Are you sure the database server is running?' ) . "</li>\n";
		$message .= '<li>' . __( 'Are you sure the database server is not under particularly heavy load?' ) . "</li>\n";
		$message .= "</ul>\n";

		$message .= '<p>' . __( 'If you are unsure what these terms mean you should probably contact your host..' ) . "</p>\n";

		$this->bail( $message, 'db_connect_fail' );

		dead_db();
	}

	public function query( $query ) {
		if ( ! $this->ready ) {
			$this->check_current_query = true;
			return false;
		}

		$query = apply_filters( 'query', $query );

		if ( ! $query ) {
			$this->insert_id = 0;
			return false;
		}

		$this->flush();

		$this->func_call = "\$db->query(\"$query\")";

		if ( $this->check_current_query && ! $this->check_ascii( $query ) ) {
			$stripped_query = $this->strip_invalid_text_from_query( $query );

			$this->flush();
			if ( $stripped_query !== $query ) {
				$this->insert_id  = 0;
				$this->last_query = $query;

				load_translations_early();

				$this->last_error = __( 'Sync Site database error: Could not perform query because it contains invalid data.' );

				return false;
			}
		}

		$this->check_current_query = true;

		$this->last_query = $query;

		$this->_do_query( $query );

		$mysql_errno = 0;

		if ( $this->dbh instanceof mysqli ) {
			$mysql_errno = mysqli_errno( $this->dbh );
		} else {

			$mysql_errno = 2006;
		}

		if ( empty( $this->dbh ) || 2006 === $mysql_errno ) {
			if ( $this->check_connection() ) {
				$this->_do_query( $query );
			} else {
				$this->insert_id = 0;
				return false;
			}
		}

		if ( $this->dbh instanceof mysqli ) {
			$this->last_error = mysqli_error( $this->dbh );
		} else {
			$this->last_error = __( 'Unable to retrieve the error message from MySQL' );
		}

		if ( $this->last_error ) {

			if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = 0;
			}

			$this->print_error();
			return false;
		}

		if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) ) {
			$return_val = $this->result;
		} elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
			$this->rows_affected = mysqli_affected_rows( $this->dbh );

			if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = mysqli_insert_id( $this->dbh );
			}

			$return_val = $this->rows_affected;
		} else {
			$num_rows = 0;

			if ( $this->result instanceof mysqli_result ) {
				while ( $row = mysqli_fetch_object( $this->result ) ) {
					$this->last_result[ $num_rows ] = $row;
					++$num_rows;
				}
			}

			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}

		return $return_val;
	}

	private function _do_query( $query ) {
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$this->timer_start();
		}

		if ( ! empty( $this->dbh ) ) {
			$this->result = mysqli_query( $this->dbh, $query );
		}

		++$this->num_queries;

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$this->log_query(
				$query,
				$this->timer_stop(),
				$this->get_caller(),
				$this->time_start,
				array()
			);
		}
	}

	public function log_query( $query, $query_time, $query_callstack, $query_start, $query_data ) {

		$query_data = apply_filters( 'log_query_custom_data', $query_data, $query, $query_time, $query_callstack, $query_start );

		$this->queries[] = array(
			$query,
			$query_time,
			$query_callstack,
			$query_start,
			$query_data,
		);
	}

	public function placeholder_escape() {
		static $placeholder;

		if ( ! $placeholder ) {

			$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
			$salt = defined( 'AUTH_SALT' ) && AUTH_SALT ? AUTH_SALT : (string) rand();

			$placeholder = '{' . hash_hmac( $algo, uniqid( $salt, true ), $salt ) . '}';
		}

		if ( false === has_filter( 'query', array( $this, 'remove_placeholder_escape' ) ) ) {
			add_filter( 'query', array( $this, 'remove_placeholder_escape' ), 0 );
		}

		return $placeholder;
	}

	public function add_placeholder_escape( $query ) {

		return str_replace( '%', $this->placeholder_escape(), $query );
	}

	public function remove_placeholder_escape( $query ) {
		return str_replace( $this->placeholder_escape(), '%', $query );
	}

	public function insert( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'INSERT' );
	}

	public function replace( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'REPLACE' );
	}

	public function _insert_replace_helper( $table, $data, $format = null, $type = 'INSERT' ) {
		$this->insert_id = 0;

		if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ), true ) ) {
			return false;
		}

		$data = $this->process_fields( $table, $data, $format );
		if ( false === $data ) {
			return false;
		}

		$formats = array();
		$values  = array();
		foreach ( $data as $value ) {
			if ( is_null( $value['value'] ) ) {
				$formats[] = 'NULL';
				continue;
			}

			$formats[] = $value['format'];
			$values[]  = $value['value'];
		}

		$fields  = '`' . implode( '`, `', array_keys( $data ) ) . '`';
		$formats = implode( ', ', $formats );

		$sql = "$type INTO `$table` ($fields) VALUES ($formats)";

		$this->check_current_query = false;
		return $this->query( $this->prepare( $sql, $values ) );
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( ! is_array( $data ) || ! is_array( $where ) ) {
			return false;
		}

		$data = $this->process_fields( $table, $data, $format );
		if ( false === $data ) {
			return false;
		}
		$where = $this->process_fields( $table, $where, $where_format );
		if ( false === $where ) {
			return false;
		}

		$fields     = array();
		$conditions = array();
		$values     = array();
		foreach ( $data as $field => $value ) {
			if ( is_null( $value['value'] ) ) {
				$fields[] = "`$field` = NULL";
				continue;
			}

			$fields[] = "`$field` = " . $value['format'];
			$values[] = $value['value'];
		}
		foreach ( $where as $field => $value ) {
			if ( is_null( $value['value'] ) ) {
				$conditions[] = "`$field` IS NULL";
				continue;
			}

			$conditions[] = "`$field` = " . $value['format'];
			$values[]     = $value['value'];
		}

		$fields     = implode( ', ', $fields );
		$conditions = implode( ' AND ', $conditions );

		$sql = "UPDATE `$table` SET $fields WHERE $conditions";

		$this->check_current_query = false;
		return $this->query( $this->prepare( $sql, $values ) );
	}

	public function delete( $table, $where, $where_format = null ) {
		if ( ! is_array( $where ) ) {
			return false;
		}

		$where = $this->process_fields( $table, $where, $where_format );
		if ( false === $where ) {
			return false;
		}

		$conditions = array();
		$values     = array();
		foreach ( $where as $field => $value ) {
			if ( is_null( $value['value'] ) ) {
				$conditions[] = "`$field` IS NULL";
				continue;
			}

			$conditions[] = "`$field` = " . $value['format'];
			$values[]     = $value['value'];
		}

		$conditions = implode( ' AND ', $conditions );

		$sql = "DELETE FROM `$table` WHERE $conditions";

		$this->check_current_query = false;
		return $this->query( $this->prepare( $sql, $values ) );
	}

	protected function process_fields( $table, $data, $format ) {
		$data = $this->process_field_formats( $data, $format );
		if ( false === $data ) {
			return false;
		}

		$data = $this->process_field_charsets( $data, $table );
		if ( false === $data ) {
			return false;
		}

		$data = $this->process_field_lengths( $data, $table );
		if ( false === $data ) {
			return false;
		}

		$converted_data = $this->strip_invalid_text( $data );

		if ( $data !== $converted_data ) {

			$problem_fields = array();
			foreach ( $data as $field => $value ) {
				if ( $value !== $converted_data[ $field ] ) {
					$problem_fields[] = $field;
				}
			}

			load_translations_early();

			if ( 1 === count( $problem_fields ) ) {
				$this->last_error = sprintf(

					__( 'Sync Site database error: Processing the value for the following field failed: %s. The supplied value may be too long or contains invalid data.' ),
					reset( $problem_fields )
				);
			} else {
				$this->last_error = sprintf(

					__( 'Sync Site database error: Processing the values for the following fields failed: %s. The supplied values may be too long or contain invalid data.' ),
					implode( ', ', $problem_fields )
				);
			}

			return false;
		}

		return $data;
	}

	protected function process_field_formats( $data, $format ) {
		$formats          = (array) $format;
		$original_formats = $formats;

		foreach ( $data as $field => $value ) {
			$value = array(
				'value'  => $value,
				'format' => '%s',
			);

			if ( ! empty( $format ) ) {
				$value['format'] = array_shift( $formats );
				if ( ! $value['format'] ) {
					$value['format'] = reset( $original_formats );
				}
			} elseif ( isset( $this->field_types[ $field ] ) ) {
				$value['format'] = $this->field_types[ $field ];
			}

			$data[ $field ] = $value;
		}

		return $data;
	}

	protected function process_field_charsets( $data, $table ) {
		foreach ( $data as $field => $value ) {
			if ( '%d' === $value['format'] || '%f' === $value['format'] ) {

				$value['charset'] = false;
			} else {
				$value['charset'] = $this->get_col_charset( $table, $field );
				if ( is_error( $value['charset'] ) ) {
					return false;
				}
			}

			$data[ $field ] = $value;
		}

		return $data;
	}

	protected function process_field_lengths( $data, $table ) {
		foreach ( $data as $field => $value ) {
			if ( '%d' === $value['format'] || '%f' === $value['format'] ) {

				$value['length'] = false;
			} else {
				$value['length'] = $this->get_col_length( $table, $field );
				if ( is_error( $value['length'] ) ) {
					return false;
				}
			}

			$data[ $field ] = $value;
		}

		return $data;
	}

	public function get_var( $query = null, $x = 0, $y = 0 ) {
		$this->func_call = "\$db->get_var(\"$query\", $x, $y)";

		if ( $query ) {
			if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
				$this->check_current_query = false;
			}

			$this->query( $query );
		}

		if ( ! empty( $this->last_result[ $y ] ) ) {
			$values = array_values( get_object_vars( $this->last_result[ $y ] ) );
		}

		return ( isset( $values[ $x ] ) && '' !== $values[ $x ] ) ? $values[ $x ] : null;
	}

	public function get_row( $query = null, $output = OBJECT, $y = 0 ) {
		$this->func_call = "\$db->get_row(\"$query\",$output,$y)";

		if ( $query ) {
			if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
				$this->check_current_query = false;
			}

			$this->query( $query );
		} else {
			return null;
		}

		if ( ! isset( $this->last_result[ $y ] ) ) {
			return null;
		}

		if ( OBJECT === $output ) {
			return $this->last_result[ $y ] ? $this->last_result[ $y ] : null;
		} elseif ( ARRAY_A === $output ) {
			return $this->last_result[ $y ] ? get_object_vars( $this->last_result[ $y ] ) : null;
		} elseif ( ARRAY_N === $output ) {
			return $this->last_result[ $y ] ? array_values( get_object_vars( $this->last_result[ $y ] ) ) : null;
		} elseif ( OBJECT === strtoupper( $output ) ) {

			return $this->last_result[ $y ] ? $this->last_result[ $y ] : null;
		} else {
			$this->print_error( ' $db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N' );
		}
	}

	public function get_col( $query = null, $x = 0 ) {
		if ( $query ) {
			if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
				$this->check_current_query = false;
			}

			$this->query( $query );
		}

		$new_array = array();

		if ( $this->last_result ) {
			for ( $i = 0, $j = count( $this->last_result ); $i < $j; $i++ ) {
				$new_array[ $i ] = $this->get_var( null, $x, $i );
			}
		}
		return $new_array;
	}

	public function get_results( $query = null, $output = OBJECT ) {
		$this->func_call = "\$db->get_results(\"$query\", $output)";

		if ( $query ) {
			if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
				$this->check_current_query = false;
			}

			$this->query( $query );
		} else {
			return null;
		}

		$new_array = array();
		if ( OBJECT === $output ) {

			return $this->last_result;
		} elseif ( OBJECT_K === $output ) {

			if ( $this->last_result ) {
				foreach ( $this->last_result as $row ) {
					$var_by_ref = get_object_vars( $row );
					$key        = array_shift( $var_by_ref );
					if ( ! isset( $new_array[ $key ] ) ) {
						$new_array[ $key ] = $row;
					}
				}
			}
			return $new_array;
		} elseif ( ARRAY_A === $output || ARRAY_N === $output ) {

			if ( $this->last_result ) {
				if ( ARRAY_N === $output ) {
					foreach ( (array) $this->last_result as $row ) {

						$new_array[] = array_values( get_object_vars( $row ) );
					}
				} else {
					foreach ( (array) $this->last_result as $row ) {

						$new_array[] = get_object_vars( $row );
					}
				}
			}
			return $new_array;
		} elseif ( strtoupper( $output ) === OBJECT ) {

			return $this->last_result;
		}
		return null;
	}

	protected function get_table_charset( $table ) {
		$tablekey = strtolower( $table );

		$charset = apply_filters( 'pre_get_table_charset', null, $table );
		if ( null !== $charset ) {
			return $charset;
		}

		if ( isset( $this->table_charset[ $tablekey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		$charsets = array();
		$columns  = array();

		$table_parts = explode( '.', $table );
		$table       = '`' . implode( '`.`', $table_parts ) . '`';
		$results     = $this->get_results( "SHOW FULL COLUMNS FROM $table" );
		if ( ! $results ) {
			return new Sync_Error( 'db_get_table_charset_failure', __( 'Could not retrieve table charset.' ) );
		}

		foreach ( $results as $column ) {
			$columns[ strtolower( $column->Field ) ] = $column;
		}

		$this->col_meta[ $tablekey ] = $columns;

		foreach ( $columns as $column ) {
			if ( ! empty( $column->Collation ) ) {
				list( $charset ) = explode( '_', $column->Collation );

				$charsets[ strtolower( $charset ) ] = true;
			}

			list( $type ) = explode( '(', $column->Type );

			if ( in_array( strtoupper( $type ), array( 'BINARY', 'VARBINARY', 'TINYBLOB', 'MEDIUMBLOB', 'BLOB', 'LONGBLOB' ), true ) ) {
				$this->table_charset[ $tablekey ] = 'binary';
				return 'binary';
			}
		}

		if ( isset( $charsets['utf8mb3'] ) ) {
			$charsets['utf8'] = true;
			unset( $charsets['utf8mb3'] );
		}

		$count = count( $charsets );
		if ( 1 === $count ) {
			$charset = key( $charsets );
		} elseif ( 0 === $count ) {

			$charset = false;
		} else {

			unset( $charsets['latin1'] );
			$count = count( $charsets );
			if ( 1 === $count ) {

				$charset = key( $charsets );
			} elseif ( 2 === $count && isset( $charsets['utf8'], $charsets['utf8mb4'] ) ) {

				$charset = 'utf8';
			} else {

				$charset = 'ascii';
			}
		}

		$this->table_charset[ $tablekey ] = $charset;
		return $charset;
	}

	public function get_col_charset( $table, $column ) {
		$tablekey  = strtolower( $table );
		$columnkey = strtolower( $column );

		$charset = apply_filters( 'pre_get_col_charset', null, $table, $column );
		if ( null !== $charset ) {
			return $charset;
		}

		if ( empty( $this->is_mysql ) ) {
			return false;
		}

		if ( empty( $this->table_charset[ $tablekey ] ) ) {

			$table_charset = $this->get_table_charset( $table );
			if ( is_error( $table_charset ) ) {
				return $table_charset;
			}
		}

		if ( empty( $this->col_meta[ $tablekey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ]->Collation ) ) {
			return false;
		}

		list( $charset ) = explode( '_', $this->col_meta[ $tablekey ][ $columnkey ]->Collation );
		return $charset;
	}

	public function get_col_length( $table, $column ) {
		$tablekey  = strtolower( $table );
		$columnkey = strtolower( $column );

		if ( empty( $this->is_mysql ) ) {
			return false;
		}

		if ( empty( $this->col_meta[ $tablekey ] ) ) {

			$table_charset = $this->get_table_charset( $table );
			if ( is_error( $table_charset ) ) {
				return $table_charset;
			}
		}

		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ] ) ) {
			return false;
		}

		$typeinfo = explode( '(', $this->col_meta[ $tablekey ][ $columnkey ]->Type );

		$type = strtolower( $typeinfo[0] );
		if ( ! empty( $typeinfo[1] ) ) {
			$length = trim( $typeinfo[1], ')' );
		} else {
			$length = false;
		}

		switch ( $type ) {
			case 'char':
			case 'varchar':
				return array(
					'type'   => 'char',
					'length' => (int) $length,
				);

			case 'binary':
			case 'varbinary':
				return array(
					'type'   => 'byte',
					'length' => (int) $length,
				);

			case 'tinyblob':
			case 'tinytext':
				return array(
					'type'   => 'byte',
					'length' => 255,        
				);

			case 'blob':
			case 'text':
				return array(
					'type'   => 'byte',
					'length' => 65535,      
				);

			case 'mediumblob':
			case 'mediumtext':
				return array(
					'type'   => 'byte',
					'length' => 16777215,   
				);

			case 'longblob':
			case 'longtext':
				return array(
					'type'   => 'byte',
					'length' => 4294967295, 
				);

			default:
				return false;
		}
	}

	protected function check_ascii( $input_string ) {
		if ( function_exists( 'mb_check_encoding' ) ) {
			if ( mb_check_encoding( $input_string, 'ASCII' ) ) {
				return true;
			}
		} elseif ( ! preg_match( '/[^\x00-\x7F]/', $input_string ) ) {
			return true;
		}

		return false;
	}

	protected function check_safe_collation( $query ) {
		if ( $this->checking_collation ) {
			return true;
		}

		$query = ltrim( $query, "\r\n\t (" );
		if ( preg_match( '/^(?:SHOW|DESCRIBE|DESC|EXPLAIN|CREATE)\s/i', $query ) ) {
			return true;
		}

		if ( $this->check_ascii( $query ) ) {
			return true;
		}

		$table = $this->get_table_from_query( $query );
		if ( ! $table ) {
			return false;
		}

		$this->checking_collation = true;
		$collation                = $this->get_table_charset( $table );
		$this->checking_collation = false;

		if ( false === $collation || 'latin1' === $collation ) {
			return true;
		}

		$table = strtolower( $table );
		if ( empty( $this->col_meta[ $table ] ) ) {
			return false;
		}

		$safe_collations = array(
			'utf8_bin',
			'utf8_general_ci',
			'utf8mb3_bin',
			'utf8mb3_general_ci',
			'utf8mb4_bin',
			'utf8mb4_general_ci',
		);

		foreach ( $this->col_meta[ $table ] as $col ) {
			if ( empty( $col->Collation ) ) {
				continue;
			}

			if ( ! in_array( $col->Collation, $safe_collations, true ) ) {
				return false;
			}
		}

		return true;
	}

	protected function strip_invalid_text( $data ) {
		$db_check_string = false;

		foreach ( $data as &$value ) {
			$charset = $value['charset'];

			if ( is_array( $value['length'] ) ) {
				$length                  = $value['length']['length'];
				$truncate_by_byte_length = 'byte' === $value['length']['type'];
			} else {
				$length = false;

				$truncate_by_byte_length = false;
			}

			if ( false === $charset ) {
				continue;
			}

			if ( ! is_string( $value['value'] ) ) {
				continue;
			}

			$needs_validation = true;
			if (

				'latin1' === $charset
			||

				( ! isset( $value['ascii'] ) && $this->check_ascii( $value['value'] ) )
			) {
				$truncate_by_byte_length = true;
				$needs_validation        = false;
			}

			if ( $truncate_by_byte_length ) {
				mbstring_binary_safe_encoding();
				if ( false !== $length && strlen( $value['value'] ) > $length ) {
					$value['value'] = substr( $value['value'], 0, $length );
				}
				reset_mbstring_encoding();

				if ( ! $needs_validation ) {
					continue;
				}
			}

			if ( ( 'utf8' === $charset || 'utf8mb3' === $charset || 'utf8mb4' === $charset ) && function_exists( 'mb_strlen' ) ) {
				$regex = '/
					(
						(?: [\x00-\x7F]                  # single-byte sequences   0xxxxxxx
						|   [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
						|   \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
						|   [\xE1-\xEC][\x80-\xBF]{2}
						|   \xED[\x80-\x9F][\x80-\xBF]
						|   [\xEE-\xEF][\x80-\xBF]{2}';

				if ( 'utf8mb4' === $charset ) {
					$regex .= '
						|    \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
						|    [\xF1-\xF3][\x80-\xBF]{3}
						|    \xF4[\x80-\x8F][\x80-\xBF]{2}
					';
				}

				$regex         .= '){1,40}                          # ...one or more times
					)
					| .                                  # anything else
					/x';
				$value['value'] = preg_replace( $regex, '$1', $value['value'] );

				if ( false !== $length && mb_strlen( $value['value'], 'UTF-8' ) > $length ) {
					$value['value'] = mb_substr( $value['value'], 0, $length, 'UTF-8' );
				}
				continue;
			}

			$value['db']     = true;
			$db_check_string = true;
		}
		unset( $value ); 

		if ( $db_check_string ) {
			$queries = array();
			foreach ( $data as $col => $value ) {
				if ( ! empty( $value['db'] ) ) {

					if ( isset( $value['length']['type'] ) && 'byte' === $value['length']['type'] ) {

						$charset = 'binary';
					} else {
						$charset = $value['charset'];
					}

					if ( $this->charset ) {
						$connection_charset = $this->charset;
					} else {
						$connection_charset = mysqli_character_set_name( $this->dbh );
					}

					if ( is_array( $value['length'] ) ) {
						$length          = sprintf( '%.0f', $value['length']['length'] );
						$queries[ $col ] = $this->prepare( "CONVERT( LEFT( CONVERT( %s USING $charset ), $length ) USING $connection_charset )", $value['value'] );
					} elseif ( 'binary' !== $charset ) {

						$queries[ $col ] = $this->prepare( "CONVERT( CONVERT( %s USING $charset ) USING $connection_charset )", $value['value'] );
					}

					unset( $data[ $col ]['db'] );
				}
			}

			$sql = array();
			foreach ( $queries as $column => $query ) {
				if ( ! $query ) {
					continue;
				}

				$sql[] = $query . " AS x_$column";
			}

			$this->check_current_query = false;
			$row                       = $this->get_row( 'SELECT ' . implode( ', ', $sql ), ARRAY_A );
			if ( ! $row ) {
				return new Sync_Error( 'db_strip_invalid_text_failure', __( 'Could not strip invalid text.' ) );
			}

			foreach ( array_keys( $data ) as $column ) {
				if ( isset( $row[ "x_$column" ] ) ) {
					$data[ $column ]['value'] = $row[ "x_$column" ];
				}
			}
		}

		return $data;
	}

	protected function strip_invalid_text_from_query( $query ) {

		$trimmed_query = ltrim( $query, "\r\n\t (" );
		if ( preg_match( '/^(?:SHOW|DESCRIBE|DESC|EXPLAIN|CREATE)\s/i', $trimmed_query ) ) {
			return $query;
		}

		$table = $this->get_table_from_query( $query );
		if ( $table ) {
			$charset = $this->get_table_charset( $table );
			if ( is_error( $charset ) ) {
				return $charset;
			}

			if ( 'binary' === $charset ) {
				return $query;
			}
		} else {
			$charset = $this->charset;
		}

		$data = array(
			'value'   => $query,
			'charset' => $charset,
			'ascii'   => false,
			'length'  => false,
		);

		$data = $this->strip_invalid_text( array( $data ) );
		if ( is_error( $data ) ) {
			return $data;
		}

		return $data[0]['value'];
	}

	public function strip_invalid_text_for_column( $table, $column, $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		$charset = $this->get_col_charset( $table, $column );
		if ( ! $charset ) {

			return $value;
		} elseif ( is_error( $charset ) ) {

			return $charset;
		}

		$data = array(
			$column => array(
				'value'   => $value,
				'charset' => $charset,
				'length'  => $this->get_col_length( $table, $column ),
			),
		);

		$data = $this->strip_invalid_text( $data );
		if ( is_error( $data ) ) {
			return $data;
		}

		return $data[ $column ]['value'];
	}

	protected function get_table_from_query( $query ) {

		$query = rtrim( $query, ';/-#' );

		$query = ltrim( $query, "\r\n\t (" );

		$query = preg_replace( '/\((?!\s*select)[^(]*?\)/is', '()', $query );

		if ( preg_match(
			'/^\s*(?:'
				. 'SELECT.*?\s+FROM'
				. '|INSERT(?:\s+LOW_PRIORITY|\s+DELAYED|\s+HIGH_PRIORITY)?(?:\s+IGNORE)?(?:\s+INTO)?'
				. '|REPLACE(?:\s+LOW_PRIORITY|\s+DELAYED)?(?:\s+INTO)?'
				. '|UPDATE(?:\s+LOW_PRIORITY)?(?:\s+IGNORE)?'
				. '|DELETE(?:\s+LOW_PRIORITY|\s+QUICK|\s+IGNORE)*(?:.+?FROM)?'
			. ')\s+((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)/is',
			$query,
			$maybe
		) ) {
			return str_replace( '`', '', $maybe[1] );
		}

		if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES).+WHERE\s+Name\s*=\s*("|\')((?:[0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)\\1/is', $query, $maybe ) ) {
			return $maybe[2];
		}

		if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES)\s+(?:WHERE\s+Name\s+)?LIKE\s*("|\')((?:[\\\\0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)%?\\1/is', $query, $maybe ) ) {
			return str_replace( '\\_', '_', $maybe[2] );
		}

		if ( preg_match(
			'/^\s*(?:'
				. '(?:EXPLAIN\s+(?:EXTENDED\s+)?)?SELECT.*?\s+FROM'
				. '|DESCRIBE|DESC|EXPLAIN|HANDLER'
				. '|(?:LOCK|UNLOCK)\s+TABLE(?:S)?'
				. '|(?:RENAME|OPTIMIZE|BACKUP|RESTORE|CHECK|CHECKSUM|ANALYZE|REPAIR).*\s+TABLE'
				. '|TRUNCATE(?:\s+TABLE)?'
				. '|CREATE(?:\s+TEMPORARY)?\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?'
				. '|ALTER(?:\s+IGNORE)?\s+TABLE'
				. '|DROP\s+TABLE(?:\s+IF\s+EXISTS)?'
				. '|CREATE(?:\s+\w+)?\s+INDEX.*\s+ON'
				. '|DROP\s+INDEX.*\s+ON'
				. '|LOAD\s+DATA.*INFILE.*INTO\s+TABLE'
				. '|(?:GRANT|REVOKE).*ON\s+TABLE'
				. '|SHOW\s+(?:.*FROM|.*TABLE)'
			. ')\s+\(*\s*((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)\s*\)*/is',
			$query,
			$maybe
		) ) {
			return str_replace( '`', '', $maybe[1] );
		}

		return false;
	}

	protected function load_col_info() {
		if ( $this->col_info ) {
			return;
		}

		$num_fields = mysqli_num_fields( $this->result );

		for ( $i = 0; $i < $num_fields; $i++ ) {
			$this->col_info[ $i ] = mysqli_fetch_field( $this->result );
		}
	}

	public function get_col_info( $info_type = 'name', $col_offset = -1 ) {
		$this->load_col_info();

		if ( $this->col_info ) {
			if ( -1 === $col_offset ) {
				$i         = 0;
				$new_array = array();
				foreach ( (array) $this->col_info as $col ) {
					$new_array[ $i ] = $col->{$info_type};
					++$i;
				}
				return $new_array;
			} else {
				return $this->col_info[ $col_offset ]->{$info_type};
			}
		}
	}

	public function timer_start() {
		$this->time_start = microtime( true );
		return true;
	}

	public function timer_stop() {
		return ( microtime( true ) - $this->time_start );
	}

	public function bail( $message, $error_code = '500' ) {
		if ( $this->show_errors ) {
			$error = '';

			if ( $this->dbh instanceof mysqli ) {
				$error = mysqli_error( $this->dbh );
			} elseif ( mysqli_connect_errno() ) {
				$error = mysqli_connect_error();
			}

			if ( $error ) {
				$message = '<p><code>' . $error . "</code></p>\n" . $message;
			}

			sync_die( $message );
		} else {
			if ( class_exists( 'Sync_Error', false ) ) {
				$this->error = new Sync_Error( $error_code, $message );
			} else {
				$this->error = $message;
			}

			return false;
		}
	}

	public function close() {
		if ( ! $this->dbh ) {
			return false;
		}

		$closed = mysqli_close( $this->dbh );

		if ( $closed ) {
			$this->dbh           = null;
			$this->ready         = false;
			$this->has_connected = false;
		}

		return $closed;
	}

	public function check_database_version() {
		global $version, $required_mysql_version;

		if ( version_compare( $this->db_version(), $required_mysql_version, '<' ) ) {
			return new Sync_Error( 'database_version', sprintf( __( '<strong>Error:</strong> Sync Site %1$s requires MySQL %2$s or higher' ), $version, $required_mysql_version ) );
		}
	}

	public function supports_collation() {
		_deprecated_function( __FUNCTION__, '3.5.0', 'db::has_cap( \'collation\' )' );
		return $this->has_cap( 'collation' );
	}

	public function get_charset_collate() {
		$charset_collate = '';

		if ( ! empty( $this->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $this->charset";
		}
		if ( ! empty( $this->collate ) ) {
			$charset_collate .= " COLLATE $this->collate";
		}

		return $charset_collate;
	}

	public function has_cap( $db_cap ) {
		$db_version     = $this->db_version();
		$db_server_info = $this->db_server_info();

		if ( '5.5.5' === $db_version && false !== strpos( $db_server_info, 'MariaDB' )
			&& PHP_VERSION_ID < 80016 
		) {

			$db_server_info = preg_replace( '/^5\.5\.5-(.*)/', '$1', $db_server_info );
			$db_version     = preg_replace( '/[^0-9.].*/', '', $db_server_info );
		}

		switch ( strtolower( $db_cap ) ) {
			case 'collation':    
			case 'group_concat': 
			case 'subqueries':   
				return version_compare( $db_version, '4.1', '>=' );
			case 'set_charset':
				return version_compare( $db_version, '5.0.7', '>=' );
			case 'utf8mb4':      
				return true;
			case 'utf8mb4_520': 
				return version_compare( $db_version, '5.6', '>=' );
			case 'identifier_placeholders': 

				return true;
		}

		return false;
	}

	public function get_caller() {
		return DEBUG_backtrace_summary( __CLASS__ );
	}

	public function db_version() {
		return preg_replace( '/[^0-9.].*/', '', $this->db_server_info() );
	}

	public function db_server_info() {
		return mysqli_get_server_info( $this->dbh );
	}
}

$db = new db( $dbuser, $dbpassword, $dbname, $dbhost );