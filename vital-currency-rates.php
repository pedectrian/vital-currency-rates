<?php
/**
 * Plugin Name: Vital currency rates.
 * Version: 0.0.1
 * Author: Alexander Permyakov
 * Author URI: http://ready2dev.ru
 * License: GPL2
 */
require_once( plugin_dir_path( __FILE__ ) . 'inc/CurrencyRateProvider.php' );
function moex_load2( $date1 ) {
	$doc           = new DOMDocument();
	$doc->encoding = "utf-8";
	$doc->load( "http://www.micex.ru/issrpc/marketdata/currency/selt/daily/download/micex_currency_selt_" . $date1 . ".xml?collection_id=64&board_group_id=13&start=0&limit=10000&lang=ru" );
	$data = $doc->getElementsByTagName( 'row' );
	foreach ( $data as $k => $node ) {
		if ( $node->getAttribute( 'WAPRICE' ) ) {
			$name                  = $node->getAttribute( 'SHORTNAME' );
			$cur[ $name ]['price'] = $node->getAttribute( 'WAPRICE' );
			$cur[ $name ]['delta'] = $node->getAttribute( 'LASTCHANGEPRCNT' );
			preg_match( "|([\d]{4})-([\d]{2})-([\d]{2})|", $node->getAttribute( 'PREVDATE' ), $dt );
			preg_match( "|([\d]{2}):([\d]{2}):([\d]{2})|", $node->getAttribute( 'UPDATETIME' ), $tm );
			$cur[ $name ]['timestamp'] = mktime( $tm[1], $tm[2], $tm[3], $dt[2], $dt[3], $dt[1] );
		}
	}

	return $cur;
}

function moex_update2() {
	$codes    = array( 'USDRUB_TOM', 'EURRUB_TOM', 'EURRUB_TOD', 'USDRUB_TOD' );
	$currency = array();
	$date1    = date( "Y_m_d" );
	$rate     = moex_load2( $date1 );
	foreach ( $codes as $code ) {
		preg_match( "|([\w]+)_([\w]+)|i", $code, $mm );
		if ( $rate[ $code ]['price'] ) {
			$currency[ $mm[1] ]['price'] = sprintf( "%.2f", $rate[ $code ]['price'] );
			moex_cache_set( $mm[1] . "_price", sprintf( "%.2f", $rate[ $code ]['price'] ) );
			$currency[ $mm[1] ]['time'] = $rate[ $code ]['timestamp'];
			moex_cache_set( $mm[1] . "_time", $rate[ $code ]['timestamp'] );
			$currency[ $mm[1] ]['delta'] = $rate[ $code ]['delta'];
			moex_cache_set( $mm[1] . "_delta", $rate[ $code ]['delta'] );
		}
	}

	return $currency;
}

function moex_cache_delete( $name ) {
	return delete_option( "moexrate_" . $name );
}

function moex_cache_get( $name ) {
	return get_option( "moexrate_" . $name );
}

function moex_cache_set( $name, $value, $upd = 1 ) {
	if ( $upd ) {
		return update_option( "moexrate_" . $name, $value );
	} else {
		return add_option( "moexrate_" . $name, $value );
	}
}

register_activation_hook( __FILE__, 'moexrate_activation' );
function moexrate_activation() {
	wp_schedule_event( time(), 'hourly', 'moexrate_hourly_event' );
}

add_action( 'moexrate_hourly_event', 'moexrate_do_this_hourly' );
function moexrate_do_this_hourly() {
	moex_update2();
}

register_deactivation_hook( __FILE__, 'moexrate_deactivation' );
function moexrate_deactivation() {
	wp_clear_scheduled_hook( 'moexrate_hourly_event' );
}

add_action( 'init', 'register_moexrate_script' );
function register_moexrate_script() {
	moex_update2();
	add_shortcode( 'vital_currency_rates', 'vitalCurrencyRatesShortcode' );
	wp_register_style( 'vital-currency-rates.css', plugins_url('inc/vital-currency-rates.css', __FILE__), array(), '0.0.1' );
	wp_enqueue_style( 'vital-currency-rates.css' );
}


function vitalCurrencyRatesShortcode() {
	$currency = array();

	$codes = array( 'USDRUB_TOM', 'EURRUB_TOM', 'EURRUB_TOD', 'USDRUB_TOD' );
	foreach ( $codes as $code ) {
		preg_match( "|([\w]+)_([\w]+)|i", $code, $mm );
		$currency[ $mm[1] ]['price'] = moex_cache_get( $mm[1] . "_price" );
		$currency[ $mm[1] ]['delta'] = moex_cache_get( $mm[1] . "_delta" );
		$currency[ $mm[1] ]['time']  = moex_cache_get( $mm[1] . "_time" );
	}

	if ( empty( $currency['USDRUB']['price'] ) ) {
		return "111";
	}//"data empty ";
	else {

//		return '
//		  <div id="currency">
//			<div class="itemmoex">
//				<div class="moexname"><img width="25" height="30" border="0" alt="USD" src="' . WP_PLUGIN_URL . '/moexrate/img/dollar.png"></div>
//				<div class="moexvalue">' . $currency['USDRUB']['price'] . '</div>
//				<div class="moexdif"><img width="9" height="9" src="' . WP_PLUGIN_URL . '/moexrate/img/' . ( $currency['USDRUB']['delta'] > 0 ? 'up' : 'dn' ) . '.gif"><span style="font-size:12px;color:' . ( $currency['USDRUB']['delta'] > 0 ? 'green' : 'red' ) . '">' . $currency['USDRUB']['delta'] . '</span></div>
//			</div>
//			<div class="itemmoex">
//				<div class="moexname"><img width="25" height="32" border="0" alt="EUR" src="' . WP_PLUGIN_URL . '/moexrate/img/euro.png"></div>
//				<div class="moexvalue">' . $currency['EURRUB']['price'] . '</div>
//				<div class="moexdif"><img width="9" height="9" src="' . WP_PLUGIN_URL . '/moexrate/img/' . ( $currency['EURRUB']['delta'] > 0 ? 'up' : 'dn' ) . '.gif"><span style="font-size:12px;color:' . ( $currency['EURRUB']['delta'] > 0 ? 'green' : 'red' ) . '">' . $currency['EURRUB']['delta'] . '</span></div>
//			</div>
//			' . ( $currency['USDRUB']['price'] ? '
//			<div class="moexlegend">Курс МБ на ' . date( "H:i:s", $currency['USDRUB']['time'] ) . '</div>
//			' : '' ) . '
//		  </div>
//			';

		$currencyRateProvider = new \Pedectrian\CurrencyRateProvider();
		$oil = $currencyRateProvider->get_oil();
		$oilDirection = $oil['diff'] > 0 ? "up" : 'down';
		$html =
			"<div class='vcrates-wrapper'>" .
				"<div class='vc-rates usd'>" .
					"<div class='vc-rates-label'>&#8364;</div>" .
					"<div class='vc-rates-value'> " .
						$currency['USDRUB']['price'] . '<span class="vc-rates-' . ( $currency['USDRUB']['delta'] > 0 ? 'up' : 'down' ) . '">' .
					"</div>" .
				"</div>" .
				"<div class='vc-rates eur'>" .
					"<div class='vc-rates-label'>&#36; </div>" .
					"<div class='vc-rates-value'>" .
						$currency['EURRUB']['price'] . '<span class="vc-rates-' . ( $currency['EURRUB']['delta'] > 0 ? 'up' : 'down' ) . '">' .
					"</div>" .
				"</div>" .
				"<div class='vc-rates oil'>" .
					"<div class='vc-rates-label'>" .
						"<span class='vc-rates-oil'></span>" .
					"</div>" .
					"<div class='vc-rates-value'>" .

						number_format($oil['value'], 2, '.') . '<span class="vc-rates-' . $oilDirection . '"></span>' .
					"</div>" .
				"</div>" .
			"</div>";

		return $html;
	}
}

add_action( 'parse_request', 'moexrate_custom_url_handler' );

function moexrate_custom_url_handler() {
	$codes = array( 'USDRUB_TOM', 'EURRUB_TOM', 'EURRUB_TOD', 'USDRUB_TOD' );
	if ( $_SERVER["REQUEST_URI"] == '/moextest' ) {
		$cur = array();
		// Test xml reading
		$date1 = date( "Y_m_d" );

		$file  = "http://www.micex.ru/issrpc/marketdata/currency/selt/daily/download/micex_currency_selt_" . $date1 . ".xml?collection_id=64&board_group_id=13&start=0&limit=10000&lang=ru";
		$lines = file( $file );
		echo $file . "<br>";
		foreach ( $lines as $line_num => $line ) {
			echo "line #<b>{$line_num}</b> : " . htmlspecialchars( $line ) . "<br />\n";
		}

		exit();
	} elseif ( $_SERVER["REQUEST_URI"] == '/moexread' ) {

		// Test read saved rate info

		$currency = array();
		$codes    = array( 'USDRUB_TOM', 'EURRUB_TOM', 'EURRUB_TOD', 'USDRUB_TOD' );

		foreach ( $codes as $code ) {
			preg_match( "|([\w]+)_([\w]+)|i", $code, $mm );
			$currency['price'] = moex_cache_get( $mm[1] . "_price" );
			$currency['delta'] = moex_cache_get( $mm[1] . "_delta" );
			$currency['time']  = moex_cache_get( $mm[1] . "_time" );
			echo $mm[1] . " " . $mm[2] . " ";
			print_r( $currency );
			print( "<br>" );
		}

		exit;
	} elseif ( $_SERVER["REQUEST_URI"] == '/moexdel' ) {

		// Test read saved rate info

		$currency = array();
		$codes    = array( 'USDRUB_TOM', 'EURRUB_TOM', 'EURRUB_TOD', 'USDRUB_TOD' );
		$i        = 0;
		foreach ( $codes as $code ) {
			preg_match( "|([\w]+)_([\w]+)|i", $code, $mm );
			moex_cache_delete( $mm[1] . "_price" );
			moex_cache_delete( $mm[1] . "_delta" );
			moex_cache_delete( $mm[1] . "_time" );
			$i ++;
		}
		if ( $i ) {
			echo "$i deleted";
		}
		exit;

	} elseif ( $_SERVER["REQUEST_URI"] == '/moexup' ) {

		//Test update rate info

		$codes = array( 'USDRUB_TOM', 'EURRUB_TOM', 'EURRUB_TOD', 'USDRUB_TOD' );
		$date1 = date( "Y_m_d" );

		$rate = moex_load2( $date1 );

		foreach ( $codes as $code ) {
			preg_match( "|([\w]+)_([\w]+)|i", $code, $mm );
			echo $code . " = " . $rate[ $code ]['price'] . " " . $rate[ $code ]['delta'] . " " . date( "Y-m-d H:i:s", $rate[ $code ]['timestamp'] ) . "<br>";
			if ( $rate[ $code ]['price'] ) {
				moex_cache_set( $mm[1] . "_price", sprintf( "%.2f", $rate[ $code ]['price'] ) );
				moex_cache_set( $mm[1] . "_time", $rate[ $code ]['timestamp'] );
				moex_cache_set( $mm[1] . "_delta", $rate[ $code ]['delta'] );

			}
		}
		exit();
	}

}