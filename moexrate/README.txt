=== MoExRate ===
Plugin Name: MoEx Rate
Plugin URI: http://selikoff.ru/tag/moexrate/
Description: Виджет курса валют ЦБ РФ на текущий день c динамикой.
Version: 1.0
Author: Selikoff Andrey
Author URI: http://www.selikoff.ru/
Contributors: AndreyS.
Donate link: http://www.selikoff.ru/
Tags: rate, currency, exchange, rouble, Moscow Exchange, RUB, EUR, USD
Requires at least: 4.0
Tested up to: 4.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Show currency rate of Moscow Exchange
Виджет курса валют МБ РФ на текущий день.

== Description ==

* This script load hourly by cron XML with currency rate of USD/RUR and EUR/RUR from "Moscow Interbank Currency Exchange" (http://www.micex.ru/issrpc/marketdata/currency/selt/daily/download/micex_currency_selt_$date.xml)
* Курс валют Московской Биржи показывает динамические значения дня для USD/RUR и EUR/RUR. Для показа ежеднвного официального курса используйте модуль CbrRate.

**Supported Languages:**

* RU Russian (default)

== Screenshots ==

1. This screen shot screenshot-1.jpg of use widget MoExRate on same theme

== Installation ==

1. Unzip archive to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. To add the MoExRate widget to the sidebar go to 'Appearance->Widgets', and add the MoExRate to your blog.
4. You must run script for give currency rate info. This script contains three test url:
   /moextest - test reading external xml file
   /moexup - update currency rate date from external xmlfile
   /moexread - test reading saved currency rate date
5. wp-cron run this script hourly if your cron success configured.
   Start wp-cron if it is not running, examples:
   GET http://site.ru/wp-cron.php
   or
   wget -q -O - http://site.ru/wp-cron.php > /dev/null 2>&1
   or
   /opt/php/5.1/bin/php-cgi -f /var/www/user_id/data/www/site.ru/wp-cron.php


* Для включения виджета, после активации плагина переходим в:
* Внешний вид
* Виджеты
* Перетаскиваем виджет MoExRate на панель сайдбара
* Запускаем вручную для первоначального получения данных /moexup
* конфигурируем крон если он не сконфигурирован


== Frequently Asked Questions ==
* Please, send your questions about this widget to my e-mail: selffmail@gmail.com
* Пожалуйста, все вопросы по работе виджета направляйте на: selffmail@gmail.com
