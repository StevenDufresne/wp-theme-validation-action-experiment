<?php

class WPORG_Themes_Test {

	/**
	 * Separates files in three buckets, PHP files, CSS files, and others.
	 *
	 * Used in preparation for the Theme Check plugin.
	 *
	 * @param array $files Files to separate.
	 * @return array
	 */
	public function separate_files( $files ) {
		$php_files = $css_files = $other_files = array();

		foreach ( $files as $file ) {
			// PHP files.
			if ( true === fnmatch( "*.php", $file ) ) {
				$php_files[ $file ] = php_strip_whitespace( $file );

				// CSS files.
			} else if ( true === fnmatch( "*.css", $file ) ) {
				$css_files[ $file ] = file_get_contents( $file );

				// All the rest.
			} else {
				$other_files[ $file ] = file_get_contents( $file );
			}
		}

		return array( $php_files, $css_files, $other_files );
	}

	/**
	 * Sends a theme through Theme Check.
	 *
	 * @param array $files All theme files to check.
	 * @return bool Whether the theme passed the checks.
	 */
	public function check_theme( $files ) {
		// Load the theme checking code.
		if ( ! function_exists( 'run_themechecks' ) ) {
			include_once WP_PLUGIN_DIR . '/downloads.wordpress.org%2Fplugins%2Ftheme-check/checkbase.php';
		}
		

		list( $php_files, $css_files, $other_files ) = $this->separate_files( $files );

		// Run the checks.
		$result = run_themechecks( $php_files, $css_files, $other_files );

		return $result;
	}

	public function clean_error( $error_string ) {
		$cleaned = str_replace([ '<span class="tc-lead tc-required">', '</span>', "<span class='tc-lead tc-required'>", '<span class="tc-lead tc-recommended">', "<span class='tc-lead tc-recommended'>"], '', $error_string);
		$cleaned = str_replace([ '<strong>', '</strong>'], '`', $cleaned);
		$cleaned = preg_replace( '!<a href="([^"]+)".+</a>!i', '$1', $cleaned);
		$cleaned = html_entity_decode( strip_tags( $cleaned ) );
		return $cleaned;
	}

	public function clean_errors( $errors ) {
		$cleaned_errors = [];

		foreach ($errors as $error) {
			array_push($cleaned_errors, $this->clean_error( $error ) );
		}

		return $cleaned_errors;
	}

	public function print_message($type, $errors) {
		var_dump(  $errors );
		echo "::" . $type . "::";
		foreach ($errors as $key=>$val) {
			echo  "[ " . $key . " ] %0A" . implode( '%0A', $val );;
			echo '%0A';
			echo '%0A';
		}
	}

	public function log_errors() {
		global $themechecks; // global that exists in the theme-check plugin

		$error_array = array();
		foreach ($themechecks as $check) {
			if ($check instanceof themecheck) {
				$error = $check->getError();
				$error_type = get_class( $check );

				if ( count($error) > 0) {
					$error_array[ $error_type ] = $this->clean_errors($error);
				}
			}
		}
		$this->print_message( "error", $error_array);
	}

	/**
	 * Get set up to run tests on the uploaded theme.
	 */
	public function __construct() {
		$theme_files = $this->get_all_files( './test-theme/' );
		$passes = $this->check_theme( $theme_files );
	
		if( ! $passes ) {
			$this->log_errors();
		}
	}

	/**
	 * Returns all (usable) files of a given directory.
	 *
	 * @param string $dir Path to directory to search.
	 * @return array All files within the passed directory.
	 */
	public function get_all_files( $dir ) {
		$files        = array();
		$dir_iterator = new RecursiveDirectoryIterator( $dir );
		$iterator     = new RecursiveIteratorIterator( $dir_iterator, RecursiveIteratorIterator::SELF_FIRST );

		foreach ( $iterator as $file ) {
			// Only return files that are no directory references or Mac resource forks.
			if ( $file->isFile() && ! in_array( $file->getBasename(), array( '..', '.' ) ) && ! stristr( $file->getPathname(), '__MACOSX' ) ) {
				array_push( $files, $file->getPathname() );
			}
		}

		return $files;
	}
}

// run the test
$w = new WPORG_Themes_Test();

?>