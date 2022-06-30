<?php

namespace JEARDev\PHPUtils;

/**
 * Class that helps with server
 * request routing.
 * 
 * @author Juan Esteban Arboleda R.
 */
class RequestHandling {
    /**
     * Key value array that contains
     * the routing preferences. Key
     * should be the request URL, and
     * value should be the corresponding
     * file to run under that request.
     * File paths yould be relative
     * to root path
     */
    private array $routing;

    /**
     * System path of the root folder.
     * Files paths that get routed given a request
     * are relative to $rootPath
     */
    private string $rootPath;

    private string $homeSubfolder;
    
    /**
     * Class Constructor
     *
     * @param array $routing - Key value
     * array that contains
     * the routing preferences. Key
     * should be the request URL, and
     * value should be the corresponding
     * file to run under that request.
     * File paths yould be relative
     * to root path
     * 
     * @param string|null $rootPath - System path
     * of the root folder. Files paths that get 
     * routed given a request are relative to
     * $rootPath. If null, $rootPath will be asumed
     * to be three directories avobe the directory
     * of this file.
     * 
     * @param string $homeSubfolder - if application
     * is runing un a subfolder of server root (ex. www.example.com/SubFolder/)
     * $homeSubfolder should be specified (ex. "/SubFolder") to be able to route
     * requests properly.
     */
    public function __construct(array $routing, string $rootPath = null, string $homeSubfolder = "") {
        $this->routing = $routing;
        if(is_null($rootPath)) {
            $rootPath = dirname(dirname(dirname(__DIR__)));
        }
        $this->rootPath = $rootPath;
        $this->homeSubfolder = $homeSubfolder;
    }

    /**
     * Returns the routing array.
     */
    public function getRouting() : array {
        return $this->routing;
    }

    /**
     * Returns defined $rootPath
     */
    public function getRootPath() : string {
        return $this->rootPath;
    }
    
    /**
     * Returns the file corresponding to the request URL.
     * If no file corresponds to that URL (i.e. that URL
     * does not exist) it reutrns -1. @see __construct.
     * $reqURL must be relative to home URL.
     * 
     * @param string $reqURL - relative URL to route.
     * URL should be relative to home URL, and it should
     * start with "/" and end with "/". If not passed as an argument, 
     * this function will use $_SERVER["REQUEST_URI"] as
     * the request URL.
     */
    public function route(string $reqURL = null) : string | int {
        if(is_null($reqURL)) {
            $reqURL = $_SERVER["REQUEST_URL"];
            if(!$this->homeSubfolder === "") {
                $reqURL = preg_replace("/^$this->homeSubfolder/", "", $reqURL);
            }
        }
        if(!preg_match("/\/$/", $reqURL)) {
            $reqURL .= "/";
        }
        foreach ($this->route as $url => $path) {
            if($url === $reqURL) {
                return $this->rootPath . $path;
            }
        }
        return -1;
    }

    /**
     * Returns true if current request is using Https, or false
     * if not.
     */
    public static function isHttps() : bool {
        return
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
    }

    /**
     * Redirects to current adress using https
     * if current request is NOT using https.
     */
    public static function httpsRedir() : void {
        if(!RequestHandling::isHttps()) {
            $loc = "https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
            header("Location: $loc");
            die();
        }
    }

    /**
     * Redirects to a URL ending in "/" if current one
     * does not end in "/".
     */
    public static function endSlashRedir() : void  {
        $loc = explode("?", $_SERVER["REQUEST_URI"], 2);
        if(!preg_match("/\/$/", $loc[0])) {
            header("Location: " . $loc[0] . "/" . $loc[1]);
            die();
        }
    }

    /**
     * Redirects to none-www host or to www host (depending
     * on whats specified in parameters) if current
     * request is under www / none-www.
     *
     * @param boolean $includeWWW - if true, function will
     * redirect to www host. If false, function will redirect
     * to none-www host. Default is false
     * @return void
     */
    public static function wwwRedir(bool $includeWWW = false) {
        if($includeWWW) {
            if(!preg_match("/^www\./", $_SERVER["HTTP_HOST"])) {
                $host = "www." . $_SERVER["HTTP_HOST"];
                header("Location: " . $host . $_SERVER["REQUEST_URI"]);
                die();
            }
        } else {
            if(preg_match("/^www\./", $_SERVER["HTTP_HOST"])) {
                $host = preg_replace("/^www\./", "", $_SERVER["HTTP_HOST"]);
                header("Location: " . $host . $_SERVER["REQUEST_URI"]);
                die();
            }
        }
    }

    /**
     * Stops requests directly to .php files
     */
    public static function killPhpFiles() {
        $loc = explode("?", $_SERVER["REQUEST_URI"], 2);
        if(preg_match("/.php(\/)?$/", $loc[0])) {
            http_response_code(404);
            die("Silence is golden!");
        }
    }

    /**
     * Causes following actions in this order.
     * 1. Returns 404 if requests a php file directly.
     * 2. Redirects request not ending in "/" to ending in "/"
     * 3. Redirects request statrting in non-www / www to starting in www / none-www
     *      depending on whats specified as an argument.
     * 4. Forces https requests
     * 
     * @param bool $inlcudeWWW if true, redirects none-www to www. Else,
     * redirects www to none-www. Default is false
     * 
     * @param bool $forceHTTPS if true, forces HTTPS, else it does not.
     * Default is true.
     */
    public static function fullRedirect(bool $includeWWW = false, bool $forceHTTPS = true) {
        RequestHandling::killPhpFiles();
        RequestHandling::endSlashRedir();
        RequestHandling::wwwRedir($includeWWW);
        if($forceHTTPS) {
            RequestHandling::httpsRedir();
        }
    }

}

?>