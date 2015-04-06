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
  }
  public function vitalCurrencyRatesShortcode()
  {
    $eur = $this->currencyRateProvider->get_currency();
    $usd = $this->currencyRateProvider->get_currency('USD');
    $oil = $this->currencyRateProvider->get_oil();
    return $this->renderData($eur, $usd, $oil);
  }

  public function renderData($eur, $usd, $oil) {
    $html =
      "<div class='vcrates-wrapper'>" .
        "<div class='vc-rates-eur'>" .
          "<div class='vc-rates-label'>" .
            $eur['currency'] .
          "</div>" .
          "<div class='vc-rates-value'>" .
          number_format($eur['value'], 2, '.', '') .
          "</div>" .
        "</div>" .
        "<div class='vc-rates-usd'>" .
          "<div class='vc-rates-label'>" .
            $usd['currency'] .
          "</div>" .
          "<div class='vc-rates-value'>" .
          number_format($usd['value'], 2, '.', '') .
          "</div>" .
        "</div>" .
        "<div class='vc-rates-oil'>" .
          "<div class='vc-rates-label'>" .
            'Нефть' .
          "</div>" .
          "<div class='vc-rates-value'>" .
          number_format($oil['value'], 2, '.', '') .
          "</div>" .
        "</div>" .
      "</div>";

    return $html;
  }
}

$vitalCurrencyRates = new VitalCurrencyRates();
