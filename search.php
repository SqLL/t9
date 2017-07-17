<?php

// Sample : http://www.torrent9.cc/search_torrent/series/ncis.html


class SynoDLMSearchTorrent9 {
    private $qurl = 'http://torrent9.cc/';
    private $context = 'search_torrent/';

    private $t9helper;

    // Constructor
    public function __construct() {
    }


    private function DebugLog($str) {
        if ($this->debug==1) {
            echo $str . "\n";
        }
    }

    // do the request
    public function prepare($curl, $query) {

        $url = $this->qurl. $this->context . urlencode($query) . '.html';
        $this->t9helper = new Torrent9Helper($url, $query);
        curl_setopt($curl, CURLOPT_URL, $url);
        # Silent
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    }

    // Parse the result
    public function parse($plugin, $response) {
        $results = $this->t9helper->loadResult($plugin,$response);
    }

}
/**
 * Simple class helper
 * Class Torrent9Helper
 */
class Torrent9Helper {


    private $base = "http://www.torrent9.cc";
    private $base_link;
    private $url;
    private $query;
    private $result;

    // Constructor
    public function __construct($url, $query) {
        $this->url = $url;
        $this->query = $query;
        $this->base_link = $this->base."/torrent/";
    }



    public function loadResult($plugin,$response) {

        $doc = new DOMDocument();
        @$doc->loadHTML($response);
        $xpath = new DOMXPath($doc);

        // May change with the design of the HTML website

        $titleRows = $xpath->query('//body//section//tbody//tr//td//a');
        $sizeRows = $xpath->query('//body//section//tbody//tr//td[2]');
        $seedersRows = $xpath->query('//body//section//tbody//tr//td[3]');
        $leechersRows = $xpath->query('//body//section//tbody//tr//td[4]');



        // Title
        for ($x = 0; $x < $titleRows->length; $x++) {

            $title = $titleRows->item($x)->nodeValue;
            $pageLink = $this->getPageLink($title);

            $additionalInfos = $this->getInfoFromPageLink($pageLink);

            $plugin->addResult(
                $title,
                $this->base . $additionalInfos['downloadlink'],
                $this->convertToBytes($sizeRows->item($x)->nodeValue),
                $this->dateTime($additionalInfos['date']),
                $pageLink,
                '',
                preg_replace('/\s+/', '', $seedersRows->item($x)->nodeValue),
                preg_replace('/\s+/', '', $leechersRows->item($x)->nodeValue),
                $additionalInfos['category']
            );


        }

        // Many results here?
        /** will be too slow for more result i think
        $pages = $xpath->evaluate('div[@id="pagination-mian"]');


        if($pages) {
        // Nombre de page
        //echo " Nombre de pages : ".$xpath->evaluate('count(//div[@id="pagination-mian"]//ul/li)');

        } */
    }

    private function getPageLink($title){
        // remove parenthesis
        $title = preg_replace('/[\(\)]/', '', $title);

        $title = strtolower(preg_replace('/[\t\s\.]+/', '-', $title));

        $result = $this->base_link.$title;
        return $result;
    }

    //2010-12-30 13:20:10
    private function dateTime($string) {
        // seconds
        if(strpos($string, 'seconde') !== false){
            $int = filter_var($string, FILTER_SANITIZE_NUMBER_INT);
            return date('Y-m-d H:i:s', time() - $int);
        }

        // minute
        if(strpos($string, "minute") !== false){
            $int = filter_var($string, FILTER_SANITIZE_NUMBER_INT);
            return date('Y-m-d H:i:s', strtotime("-".$int." minute"));
        }

        // hour
        if(strpos($string, "heure") !== false){
            $int = filter_var($string, FILTER_SANITIZE_NUMBER_INT);
            return date('Y-m-d H:i:s', strtotime("-".$int." hour"));
        }

        // semaine
        if(strpos($string, "semaine") !== false){
            $int = filter_var($string, FILTER_SANITIZE_NUMBER_INT);
            return date('Y-m-d H:i:s', strtotime("-".$int." week"));
        }
        // mois
        if(strpos($string, "mois") !== false){
            $int = filter_var($string, FILTER_SANITIZE_NUMBER_INT);
            return date('Y-m-d H:i:s', strtotime("-".$int." month"));
        }
        // year
        if(strpos($string, "ans") !== false){
            $int = filter_var($string, FILTER_SANITIZE_NUMBER_INT);
            return date('Y-m-d H:i:s', strtotime("-".$int." year"));
        }
    }

    private function convertToBytes($from){
            $number=trim(substr($from,0,-2));
            switch(strtoupper(substr($from,-2))){
                case "KO":
                    return $number*1024;
                case "MO":
                    return $number*pow(1024,2);
                case "GO":
                    return $number*pow(1024,3);
                case "TO":
                    return $number*pow(1024,4);
                case "PO":
                    return $number*pow(1024,5);
                default:
                    return $from;
            }
        }


    private function getInfoFromPageLink($link) {

        // Curl call
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $link);
        # Silent
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);

;
        $doc = new DOMDocument();
        @$doc->loadHTML($result);
        $xpath = new DOMXPath($doc);


        $resultA = array(
            'date' => $xpath->query("//body//div[contains(@class, 'left-tab-section')]//div[contains(@class, 'movie-information')]//ul[3]//li[3]")->item(0)->nodeValue,
            'category' => utf8_decode($xpath->query('//body//div[contains(@class, "left-tab-section")]//div[contains(@class, "movie-information")]//ul[4]//li[3]')->item(0)->nodeValue),
            'downloadlink' => $xpath->query('//body//div[contains(@class, "left-tab-section")]//div[1][contains(@class, "download-btn")]//a[contains(@class, "download")]/@href')->item(0)->nodeValue
        );

        return $resultA;
    }
}
?>
