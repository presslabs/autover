<?php

use function Patchwork\always;
use function Patchwork\redefine;
use function Patchwork\restoreAll;


class AutoverTest extends WP_UnitTestCase {
	public function tearDown() {
		restoreAll();
		parent::tearDown();
	}

	public function data_not_css_or_js() {
		return [
			[ 'http://test.com/random.jpg?ver=1234' ],
			[ 'http://user:password@test.com/?ver=1234&foo=bar#fragment' ],
			[ '://test.com/?ver=1234&foo=bar#fragment' ],
			[ '//test.com/?fizz=buzz&ver=1234&foo=bar#fragment' ],
			[ 'https://test.com/?fizz=buzz&ver=1234&foo=bar#fragment' ],
			[ 'http://test.com/?fizz=buzz&ver=1234&foo=bar#fragment' ],
			[ 'http://test.com/?fizz=buzz&ver=1234&foo=bar' ],
			[ 'http://test.com/?ver=1234&foo=bar' ],
			[ 'http://test.com/?ver=1234' ],
			[ 'http://test.com/' ],
		];
	}

	/**
	 * @dataProvider data_not_css_or_js
	 */
	public function test_untouched_if_not_css_or_js_file( $url ) {
		$filtered_url = autover_version_filter( $url );

		$this->assertSame( $url, $filtered_url );
	}

	public function data_constants_shortcircuit() {
		return [
			[ 'http://test.com/test.css', 'AUTOVER_DISABLE_CSS' ],
			[ 'http://test.com/test.js', 'AUTOVER_DISABLE_JS' ],
		];
	}


	/**
	 * @dataProvider data_constants_shortcircuit
	 */
	public function test_untouched_if_constants_defined( $url, $expected_constant ) {
		$defined_properly_called = false;
		redefine( 'defined', function ( $name ) use ( $expected_constant, &$defined_properly_called ) {
			$result = ( $expected_constant === $name );
			if ( $result ) {
				$defined_properly_called = true;
			}

			return $result;
		} );

		$filtered_url = autover_version_filter( $url );

		$this->assertSame( $url, $filtered_url );
		$this->assertTrue( $defined_properly_called );
	}

	public function data_css_or_js() {
		return [
			[
				'http://user:password@test.com/test/test.js?ver=1234&foo=bar#fragment',
				'http://user:password@test.com/test/test.js?foo=bar&ver=9999#fragment'
			],
			[
				'://test.com/test.js?ver=1234&foo=bar#fragment',
				'://test.com/test.js?foo=bar&ver=9999#fragment'
			],
			[
				'//test.com/test.js?fizz=buzz&ver=1234&foo=bar#fragment',
				'//test.com/test.js?fizz=buzz&foo=bar&ver=9999#fragment'
			],
			[
				'https://test.com/test.js?fizz=buzz&ver=1234&foo=bar#fragment',
				'https://test.com/test.js?fizz=buzz&foo=bar&ver=9999#fragment'
			],
			[
				'http://test.com/test.js?fizz=buzz&ver=1234&foo=bar#fragment',
				'http://test.com/test.js?fizz=buzz&foo=bar&ver=9999#fragment'
			],
			[
				'http://test.com/test.js?fizz=buzz&ver=1234&foo=bar',
				'http://test.com/test.js?fizz=buzz&foo=bar&ver=9999'
			],
			[
				'http://test.com/test.js?ver=1234&foo=bar',
				'http://test.com/test.js?foo=bar&ver=9999'
			],
			[
				'http://test.com/test.js?ver=1234',
				'http://test.com/test.js?ver=9999'
			],
			[
				'//test.com/test.js?ver=1234',
				'//test.com/test.js?ver=9999'
			],
			[
				'/test.js?ver=1234',
				'/test.js?ver=9999'
			],
			[
				'test.js?ver=1234',
				'test.js?ver=9999'
			],
			[
				'http://test.com/test+1.js',
				'http://test.com/test+1.js?ver=9999'
			],
			[
				'http://test.com/test.js?v=1234',
				'http://test.com/test.js?ver=9999'
			],
			[
				'http://user:password@test.com/test/test.css?ver=1234&foo=bar#fragment',
				'http://user:password@test.com/test/test.css?foo=bar&ver=9999#fragment'
			],
			[
				'://test.com/test.css?ver=1234&foo=bar#fragment',
				'://test.com/test.css?foo=bar&ver=9999#fragment'
			],
			[
				'//test.com/test.css?fizz=buzz&ver=1234&foo=bar#fragment',
				'//test.com/test.css?fizz=buzz&foo=bar&ver=9999#fragment'
			],
			[
				'https://test.com/test.css?fizz=buzz&ver=1234&foo=bar#fragment',
				'https://test.com/test.css?fizz=buzz&foo=bar&ver=9999#fragment'
			],
			[
				'http://test.com/test.css?fizz=buzz&ver=1234&foo=bar#fragment',
				'http://test.com/test.css?fizz=buzz&foo=bar&ver=9999#fragment'
			],
			[
				'http://test.com/test.css?fizz=buzz&ver=1234&foo=bar',
				'http://test.com/test.css?fizz=buzz&foo=bar&ver=9999'
			],
			[
				'http://test.com/test.css?ver=1234&foo=bar',
				'http://test.com/test.css?foo=bar&ver=9999'
			],
			[
				'http://test.com/test.css?ver=1234',
				'http://test.com/test.css?ver=9999'
			],
			[
				'//test.com/test.css?ver=1234',
				'//test.com/test.css?ver=9999'
			],
			[
				'/test.css?ver=1234',
				'/test.css?ver=9999'
			],
			[
				'test.css?ver=1234',
				'test.css?ver=9999'
			],
			[
				'http://test.com/test.css?v=1234',
				'http://test.com/test.css?ver=9999'
			],
			[
				'http://test.com/test+1.css',
				'http://test.com/test+1.css?ver=9999'
			],
		];
	}

	/**
	 * @dataProvider data_css_or_js
	 */
	public function test_version_changed_if_css_or_js_file( $url, $expected ) {
		redefine( 'filemtime', always( 9999 ) );
		redefine( 'is_file', always( true ) );

		$filtered_url = autover_version_filter( $url );

		$this->assertSame( $expected, $filtered_url );
	}

	public function data_url_to_path() {
		return [
			[ 'http://test.com/test_folder/test.css', ABSPATH . 'test_folder/test.css' ],
			[ 'http://test.com/test+1.css', ABSPATH . 'test 1.css' ],
		];
	}

	/**
	 * @dataProvider data_url_to_path
	 */
	public function test_version_acquisition_from_proper_file( $url, $expected_path ) {
		redefine( 'filemtime', function ( $file_path ) use ( $expected_path ) {
			return $expected_path === $file_path ? 9999 : 0;
		} );

		redefine( 'is_file', function ( $file_path ) use ( $expected_path ) {
			return $expected_path === $file_path;
		} );

		autover_version_filter( $url );
	}
}
