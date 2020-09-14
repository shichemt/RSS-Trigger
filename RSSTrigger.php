<?php

class RSSFeedTrigger {

    // private $oldFileName = "";
    // private $newFileName = "";
    private $filePrefix = "";
    private $feedUrl = "";
    private const CACHE_DIR = __DIR__ . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR;


    public function __construct($url, $filePrefix) {
        $this->filePrefix = $filePrefix;
        $this->feedUrl = $url;

        // Create cache directory
        if (!mkdir(CACHE_DIR, 0777, true)) {
            throw("Error: Cannot create cache directory.");
        }
    }

    private function findOldFile() {
        $fileArray = array();
        foreach (glob(CACHE_DIR . $filePrefix . "*") as $file) {
            $fileArray[$file] = filemtime($file);
        }
        $fileArray = asort($fileArray);
        return (isset($fileArray[0]) ? $fileArray[0] : false);
    }


    private function fetchFeed($func_name) {
        $feed = file_get_contents($this->feedUrl);
        if($feed === FALSE) {
            throw("Error: Cannot fetch the url");
        } else {
            




            if (file_put_contents(CACHE_DIR . $filePrefix . date("YmdHis"), $feed) === FALSE) {
                throw("Error: Cannot save file.");
            } 
        }
    }

    /**
     * 
     * @param string $newFeed: Loads content from file_get_contents 
     * @param string $xpath: Path of the xml need to be parsed. example /a/b/c
     * @return array of strings: New unique elements.
     */
    private function getNewUniqueElements($newFeed, $xpath) {

        $newFeedXml = simplexml_load_string($newFeed, 'SimpleXMLElement', LIBXML_NOCDATA);
        $newFeedItems = (array) $newFeedXml->xpath($xpath);

        $oldFeed = $this->findOldFile();
        if (!!$oldFeed) {
            $oldFeedXml = simplexml_load_file($oldFeed, 'SimpleXMLElement', LIBXML_NOCDATA);
            $oldFeedItems = (array) $oldFeedXml->xpath($xpath);
            $newfinalArray = array();
            foreach ($newFeedItems as $newItem) {
                if (!in_array($newItem, $oldFeedItems)) {
                    array_push($newfinalArray, $newItem);
                }
            }

            // delete old feed once done fetching new unique elements.
            unlink(CACHE_DIR . $oldFeed);

            return $newfinalArray;
        }

        return $newFeedItems;

    }



}



?>