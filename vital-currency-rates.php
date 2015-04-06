<?php
require_once( plugin_dir_path( __FILE__ ) . 'inc/CurrencyRateProvider.php' );
/**
 * Plugin Name: Vital currency rates.
 * Version: 0.0.1
 * Author: Alexander Permyakov
 * Author URI: http://ready2dev.ru
 * License: GPL2
 */

class VitalCurrencyRates {

  protected $currencyRateProvider;

  public function __construct()
  {
    add_action( 'init', array( $this, 'init' ) );
    $this->currencyRateProvider = new \Pedectrian\CurrencyRateProvider();
  }
  public function init()
  {
    add_filter('widget_text', 'do_shortcode');
    add_shortcode( 'vital_currency_rates', array( $this, 'vitalCurrencyRatesShortcode' ) );

    wp_register_style( 'vital-currency-rates.css', plugins_url('inc/vital-currency-rates.css', __FILE__), array(), '0.0.1' );
    wp_enqueue_style( 'vital-currency-rates.css' );
  }
  public function vitalCurrencyRatesShortcode()
  {
    $eur = $this->currencyRateProvider->get_currency();
    $usd = $this->currencyRateProvider->get_currency('USD');
    $oil = $this->currencyRateProvider->get_oil();
    return $this->renderData($eur, $usd, $oil);
  }

  public function renderData($eur, $usd, $oil) {
    $oilDirection = $oil['diff'] > 0 ? "up" : 'down';

    $html =
      "<div class='vcrates-wrapper'>" .
        "<div class='vc-rates eur'>" .
          "<div class='vc-rates-label'>&#8364;</div>" .
          "<div class='vc-rates-value'> " .
            $this->numberFormat($eur['value']) .
          "</div>" .
        "</div>" .
        "<div class='vc-rates usd'>" .
          "<div class='vc-rates-label'>&#36; </div>" .
          "<div class='vc-rates-value'>" .
            $this->numberFormat($usd['value']) .
          "</div>" .
        "</div>" .
        "<div class='vc-rates oil'>" .
          "<div class='vc-rates-label'>" .
            'Нефть&nbsp;' .
          "</div>" .
          "<div class='vc-rates-value'>" .
            $this->numberFormat($oil['value']) . '<span class="vc-rates-' . $oilDirection .'">' . $this->numberFormat($oil['diff']) . '</span>' .
          "</div>" .
        "</div>" .
      "</div>";

    return $html;
  }

  protected function numberFormat($value) {
    $value = str_replace(',', '.', $value);
    return number_format((float)$value, 2, '.', '');
  }
}

$vitalCurrencyRates = new VitalCurrencyRates();
