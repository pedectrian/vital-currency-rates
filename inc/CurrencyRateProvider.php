<?php
namespace Pedectrian;

class CurrencyRateProvider
{
    private $money_url = '';
    private $oil_url = '';
    private $save_path = '';
    private $upd_dates = array();
    private $rates = array();
    private $oil_price = 0;
    private $oil_diff = 0;

    public function __construct()
    {
        $date_str = date('d/m/Y');
        $this->oil_url = 'http://news.yandex.ru/quotes/1006.xml';
        $this->money_url = 'http://www.cbr.ru/scripts/XML_daily.asp?date_req='.$date_str;
        $this->save_path = plugin_dir_path( __FILE__ ) . '/rates.txt';
        $this->load_file();
    }

    public function get_oil()
    {
        if ( !isset( $this->upd_dates['oil'] ) ) {
            return false;
        }

        return array(
          'date' => $this->upd_dates['oil']['day'] .
              '.' . $this->upd_dates['oil']['month'] .
              '.' . $this->upd_dates['oil']['year'],
          'value' => $this->oil_price,
          'diff' => $this->oil_diff
        );
    }

    public function get_currency( $type='EUR' )
    {
        if ( !isset( $this->rates[$type] ) ) {
            return false;
        }

        return array(
            'date' => $this->upd_dates['money']['day'] .
                '.' . $this->upd_dates['money']['month'] .
                '.' . $this->upd_dates['money']['year'],
            'value' => $this->rates[$type]['value'],
            'currency' => $type,
            'nominal' => $this->rates[$type]['nominal']
        );
    }

    private function load_file()
    {
        @$handle = fopen($this->save_path, "r");
        if ( !$handle ) {
            $this->remake_file();
            return false;
        }

        @flock( $handle, LOCK_SH );
        $buffer = "";

        while ( !feof( $handle ) ) {
            $buffer .= fgets( $handle, 4096 );
        }
        @flock( $handle, LOCK_UN );
        fclose($handle);

        if ( strlen( $buffer ) < 5 ) {
            $this->remake_file();
            return false;
        }

        @$data = unserialize($buffer);

        if( !is_array( $data ) ) {
            $this->remake_file();
            return false;
        }

        if ( isset( $data['dates'] ) ) {
            $this->upd_dates = $data['dates'];
        }

        if ( isset( $data['rates'] ) ) {
            $this->rates = $data['rates'];
        }

        if ( isset( $data['oil'] ) ) {
            $this->oil_price = $data['oil'];
            $this->oil_diff = $data['oil_diff'];
        }

        return true;
    }

    public function remake_file(){
        $need2upd = false;
        $upd_content = array();

        if ( isset( $this->upd_dates['money'] ) ) {
            $int_date_now = $this->date2int($this->upd_dates['money']);
            $new_data_money = $this->load_money();
            $int_date_money_new = $this->date2int($new_data_money['update']);

            if ( $int_date_now !== false &&
                $int_date_money_new !==false &&
                $int_date_money_new > $int_date_now ) {
                $need2upd = true;
            }
        } else {
            $need2upd = true;
            $new_data_money = $this->load_money();
        }

        if ( isset( $this->upd_dates['oil'] ) ) {
            $int_date_now = $this->date2int( $this->upd_dates['oil'] );
            $new_data_oil = $this->load_oil();
            $int_date_oil_new = $this->date2int($new_data_oil['update']);

            if ( $int_date_now !== false && $int_date_oil_new !== false && $int_date_oil_new > $int_date_now ) {
              $need2upd = true;
            }
        } else {
            $new_data_oil = $this->load_oil();
            $need2upd = true;
        }

        if ( $need2upd ) {

            $upd_content['dates'] = array();
            $upd_content['dates']['money'] = $new_data_money['update'];
            $upd_content['dates']['oil'] = $new_data_oil['update'];
            $upd_content['rates'] = $new_data_money['data'];
            $upd_content['oil'] = $new_data_oil['data'];
            $upd_content['oil_diff'] = $new_data_oil['diff'];

            $this->upd_dates = $upd_content['dates'];

            $this->rates = $upd_content['rates'];
            $this->oil_price = $upd_content['oil'];
            $this->oil_diff = $upd_content['oil_diff'];

            $data = serialize($upd_content);

            $handle = fopen($this->save_path, "w");

            @flock ($handle, LOCK_EX);
            fwrite ($handle, $data);

            @flock ($handle, LOCK_UN);
            fclose($handle);
        }

        return true;
    }

    private function date2int($date = array()){
        if(!is_array($date)){
                return false;
        }
        if(!isset($date['year']) or !isset($date['month']) or !isset($date['day'])){
                return false;
        }
        $int_val = $date['year']*365*24 + $date['month']*30*24 + $date['day']*24;
        if(isset($date['hour'])){
                $int_val += $date['hour'];
        }
        return $int_val;
    }

    private function load_money(){
//              $res = simplexml_load_file($this->money_url);
        $res = simplexml_load_string( $this->curl_load($this->money_url ) );
        $date_update = $res->xpath('/ValCurs/@Date');
        $date_update = iconv( 'utf-8','windows-1251',$date_update[0] );

        if ( preg_match( "/^([0-9]{2})[\/\.]{1}([0-9]{2})[\/\.]{1}([0-9]{4})$/i", $date_update, $matches ) ){
                $date = array();
                $date['day'] = $matches[1];
                $date['month'] = $matches[2];
                $date['year'] = $matches[3];
        }

        $return = array();
        $return['update'] = $date;
        $list = $res->xpath('/ValCurs/Valute');
        $valutes = array();
        foreach($list as $valute){
                $code = iconv('utf-8', 'windows-1251', $valute->CharCode);
                $valutes[$code] = array();
                $valutes[$code]['nominal'] = iconv('utf-8', 'windows-1251', $valute->Nominal);
                $valutes[$code]['value'] = iconv('utf-8', 'windows-1251', $valute->Value);
        }
        $return['data'] = $valutes;
        return $return;
    }

    private function load_oil(){
        $res = simplexml_load_string($this->curl_load($this->oil_url));
        $list = $res->xpath('/stock/sdt');
        $needed = $list[0];
        $prev = $list[1];

        $date_update = iconv('utf-8','windows-1251',$needed['date']);

        if ( preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/i", $date_update, $matches ) ){
            $date = array();
            $date['day'] = $matches[3];
            $date['month'] = $matches[2];
            $date['year'] = $matches[1];
            $date['hour'] = $matches[1];
        }

        $time_update = iconv('utf-8','windows-1251',$needed['time']);

        $date['hour'] = intval(substr($time_update, 0, 2));

        $return = array();
        $return['update'] = $date;
        $return['data'] = iconv('utf-8','windows-1251',$needed->value);
        $return['diff'] = (float)$needed->value - (float)$prev->value;

        return $return;
    }

    private function curl_load($url=''){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
