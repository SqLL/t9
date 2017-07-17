<?php
/**
 * User: sqll
 * Date: 15/07/17
 * Time: 16:42
 */
require_once('search.php');

libxml_use_internal_errors(true);
$search9 = new SynoDLMSearchTorrent9();
$curl = curl_init();
$search9->prepare($curl,"ubuntu");
$response = curl_exec($curl);
curl_close($curl);
$plugin = new plugin;

$count = $search9->parse($plugin,$response);
echo "Number of result : ".$plugin->count() . "\n";

var_dump($plugin);

class plugin {
    private $results;
    public function addResult($title, $download, $size, $datetime, $page, $hash, $seeds, $leechs, $category) {
        $this->results[] = array(
            'title' => $title,
            'download' => $download,
            'size' => $size,
            'datetime' => $datetime,
            'page' => $page,
            'hash' => $hash,
            'seeds' => $seeds,
            'leechs' => $leechs,
            'category' => $category
        );
    }
    public function count() {
        return count($this->results);
    }
}


?>