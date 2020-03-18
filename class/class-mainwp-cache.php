<?php
/**
 * Search Cache Handler
 */
class MainWP_Cache {

	public static function init_session() {
		if ( '' === session_id() ) {
			session_start();
		}
	}

	public static function init_cache( $page ) {
		$_SESSION[ 'MainWP' . $page . 'Search' ]        = '';
		$_SESSION[ 'MainWP' . $page . 'SearchContext' ] = '';
		$_SESSION[ 'MainWP' . $page . 'SearchResult' ]  = '';
	}

	public static function add_context( $page, $context ) {
		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$context['time']                                = time();
		$_SESSION[ 'MainWP' . $page . 'SearchContext' ] = $context;
	}

	public static function add_body( $page, $body ) {
		$_SESSION[ 'MainWP' . $page . 'Search' ] .= $body;
	}

	public static function get_cached_context( $page ) {
		$cachedSearch = ( isset( $_SESSION[ 'MainWP' . $page . 'SearchContext' ] ) && is_array( $_SESSION[ 'MainWP' . $page . 'SearchContext' ] ) ? $_SESSION[ 'MainWP' . $page . 'SearchContext' ] : null );

		if ( null != $cachedSearch ) {
			if ( ( time() - ( 2 * 60 * 60 ) ) > $cachedSearch['time'] ) {
				unset( $_SESSION[ 'MainWP' . $page . 'SearchContext' ] );
				unset( $_SESSION[ 'MainWP' . $page . 'Search' ] );
				unset( $_SESSION[ 'MainWP' . $page . 'SearchResult' ] );
				$cachedSearch = null;
			}
		}
		if ( null != $cachedSearch && isset( $cachedSearch['status'] ) ) {
			$cachedSearch['status'] = explode( ',', $cachedSearch['status'] );
		}

		return $cachedSearch;
	}

	public static function echo_body( $page ) {
		if ( isset( $_SESSION[ 'MainWP' . $page . 'Search' ] ) ) {
			echo $_SESSION[ 'MainWP' . $page . 'Search' ];
		}
	}

	public static function add_result( $page, $result ) {
		$_SESSION[ 'MainWP' . $page . 'SearchResult' ] = $result;
	}

	public static function get_cached_result( $page ) {
		if ( isset( $_SESSION[ 'MainWP' . $page . 'SearchResult' ] ) ) {
			return $_SESSION[ 'MainWP' . $page . 'SearchResult' ];
		}
	}

}
