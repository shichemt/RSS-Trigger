<?php

class RSSTrigger {

    private $CACHE_DIR = __DIR__ . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR;

    private $filePrefix = "";
    private $feedUrl = "";
    


    public function __construct($url, $filePrefix) {
        $this->filePrefix = $filePrefix;
        $this->feedUrl = $url;

        // Create cache directory if it does not exist.
        if (!file_exists($this->CACHE_DIR)) {
            if (!mkdir($this->CACHE_DIR, 0777, true)) {
                throw new Exception("Error: Cannot create cache directory.");
            }
        }
    }

    private function findOldFile() {
        $fileArray = array();
        $files = glob($this->CACHE_DIR . $this->filePrefix . "*");

        foreach ($files as $file) {
            $fileArray[$file] = filemtime($file);
        }
        arsort($fileArray);

        return (!empty($fileArray) ? array_key_first($fileArray) : false);
    }


    /**
     * Saves the new xml file to local drive, and return the value of the user-defined function.
     * @param string $xpath: Path of the xml need to be parsed. example /a/b/c
     * @param function comparisonMethod: user-predefined-function that compares values to be extracted.
     * @param function func_name: user-predefined-function that needs to be executed on the new elements.
     * @return mixed: Return value of func_name
     */
    public function fetchFeed($xpath, $comparisonMethod, $func_name) {
        $feed = file_get_contents($this->feedUrl);
        if($feed === FALSE) {
            throw new Exception("Error: Cannot fetch the url");
        } else {
            $newElements = $this->getNewUniqueElements($feed, $xpath, $comparisonMethod);
            $newfileName = $this->CACHE_DIR . $this->filePrefix . date("YmdHis");
            if (file_put_contents($newfileName, $feed) === FALSE) {
                throw new Exception("Error: Cannot save file.");
            } 

            chmod($newfileName, 0777);

            return $func_name($newElements);
        }
    }

    /**
     * 
     * @param string $newFeed: Loads content from file_get_contents 
     * @param string $xpath: Path of the xml need to be parsed. example /a/b/c
     * @param function func_name: user-predefined-function that compares values to be extracted.
     * @return array of strings: New unique elements.
     */
    private function getNewUniqueElements($newFeed, $xpath, $func_name) {

        $newFeedXml = simplexml_load_string($newFeed, 'SimpleXMLElement', LIBXML_NOCDATA);
        $newFeedXml->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom');
        $newFeedItems = (array) $newFeedXml->xpath($xpath);

        $oldFeed = $this->findOldFile();

        if (!!$oldFeed) {
            $oldFeedXml = simplexml_load_file($oldFeed, 'SimpleXMLElement', LIBXML_NOCDATA);
            $oldFeedXml->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom');
            $oldFeedItems = (array) $oldFeedXml->xpath($xpath);

            $newfinalArray = array();
            $newfinalArray = $func_name($newFeedItems, $oldFeedItems);


            // delete old feed once done fetching new unique elements.
            unlink($oldFeed);

            return $newfinalArray;
        }

        return $newFeedItems;

    }



}



?>