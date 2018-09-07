<?php

/**
 *
 *  * This is an iumio Framework component
 *  *
 *  * (c) RAFINA DANY <dany.rafina@iumio.com>
 *  *
 *  * iumio Framework, an iumio component [https://iumio.com]
 *  *
 *  * To get more information about licence, please check the licence file
 *
 */

namespace ManagerApp\Masters;

use iumioFramework\Core\Server\Server;
use iumioFramework\Core\Requirement\Environment\FEnv;
use iumioFramework\Core\Exception\Server500;
use iumioFramework\Core\Routing\Js\JsRouting;
use iumioFramework\Core\Masters\MasterCore;
use iumioFramework\Core\Base\Renderer\Renderer;

/**
 * Class RoutingMaster
 * @package iumioFramework\Core\Manager
 * @category Framework
 * @licence  MIT License
 * @link https://framework.iumio.com
 * @author   RAFINA Dany <dany.rafina@iumio.com>
 */

class RoutingMaster extends MasterCore
{

    private $methodsReq = array("GET", "PUT", "DELETE", "POST",
        "PATH", "ALL", "OPTIONS", "TRACE", "HEAD", "CONNECT");
    private $keywords = array("name", "path", "activity", "m_allow", "route", "endroute", "visibility", "parameters");

    /**
     * @var array $scalar The scalar type for parameters
     */
    protected $scalar = array("string", "bool", "int", "float");

    /**
     * @var array $visibilities Routes visibilities
     */
    protected $visibilities = array("public", "private", "disabled");

    /**
     * Going to app manager
     * @throws
     */
    public function routingActivity()
    {
        return ($this->render("routingmanager", array("selected" => "routingmanager",
            "loader_msg" => "Routing (Mercure) Manager")));
    }

    /**
     * A version of in_array() that does a sub string match on $needle
     *
     * @param  mixed   $needle    The searched value
     * @param  array   $haystack  The array to search in
     * @return bool False for unknown value or the value is founded
     */

    private function strlikeInArray($needle, array $haystack):bool
    {
        foreach ($haystack as $value) {
            if (preg_match("/$value/", $needle) === 1) {
                return (true);
            }
        }
        return (false);
    }

    /** Detect any parameters in path
     * @param string $path URI path
     * @return array All parameters
     */
    private function detectParameters(string $path):array
    {
        $params = array();

        for ($i = 0; $i < strlen($path); $i++) {
            if ($path[$i] == "{") {
                $param = "";
                for (($p = $i + 1); $p < strlen($path); $p++) {
                    if ($path[$p] == "}") {
                        $p = strlen($path);
                        array_push($params, $param);
                    } else {
                        $param = $param.$path[$p];
                    }
                }
            }
        }
        return ($params);
    }

    /** Get routing statistics
     * @return array Routing statistics
     * @throws \Exception
     */
    public function getStatisticsRouting():array
    {

        $f = $this->getallRouting();
        $fc = 0;
        $fdisabled = 0;
        $fpublic = 0;

        foreach ($f as $one) {
            $fc += $one['count_route'];
            $z = $this-> getMercureContent(str_replace(".merc", "", $one['name']), $one['app']);
            foreach ($z as $two) {
                if ($two['visibility'] == "disabled") {
                    $fdisabled++;
                } elseif ($two['visibility'] == "public") {
                    $fpublic++;
                }
            }
        }

        return (array("number" => $fc, "disabled" => $fdisabled, "public" => $fpublic));
    }

    /**
     * @param string $filename
     * @param string $appname
     * @return array
     * @throws \Exception
     */
    public function getMercureContent(string $filename, string $appname):array
    {
        $scope = null;
        $routingArray = array();
        $pattern = '/\s*/m';
        $replace = '';
        $prefix = "";
        $rt = array();

        if (($router = fopen((FEnv::get("framework.apps"))
            . $appname . "/Routing/" . "$filename.merc", "r"))) {
            $appc = $this->getMaster("Apps");
            $apps = $appc->getAllApps();
            foreach ($apps as $o) {
                if ($o->name === $appname) {
                    $prefix = $o->prefix;
                    break;
                }
            }

            $rtarray = array("activity" => "", "path" => "", "name" => "", "visibility" =>
                "private", "m_allow" => "ALL", "r_parameters" => array());
            $start = 0;
            $end = 0;
            $croute = 0;
            while ($listen = fgets($router, 1024)) {
                $listen = preg_replace($pattern, $replace, $listen);

                if ($listen === "") {
                    continue;
                }
                if ($listen === "route:" && $start == 0 && $end === 0) {
                    $start = 1;
                    continue;
                } elseif ($listen === "endroute" && $start === 1 & $end === 0) {
                    $end = 1;
                    array_push($routingArray, $rtarray);
                } elseif ($this->strlikeInArray(trim($listen), $this->keywords)) {
                    $exline = $listen;
                    $listen = explode(':', $listen);
                    if (!in_array($listen[0], $this->keywords)) {
                        new Server500(new \ArrayObject(array("explain" =>
                            "Unknown keyword '$listen[0]' in $filename.merc : ".$appname,
                            "solution" => "Please add the correct keyword : ".json_encode($this->keywords))));
                    }
                    if (count($listen) > 1) {
                        $rtarray['r_parameters'] = $this->detectParametersType($exline, $listen[0]);
                    }
                    $rtarray[$listen[0]] = $listen[1];
                }
                if ($start === 1 && $end === 1) {
                    $rtarray = array("activity" => "", "path" => "", "name" => "", "visibility" =>
                        "private", "m_allow" => "ALL", "r_parameters" => array());
                    $start = $end = 0;
                    $croute++;
                }
            }
        }

        for ($i = 0; $i < count($routingArray); $i++) {
            $method = explode('%', $routingArray[$i]['activity']);
            $controller = $method[0];
            $function = $method[1];
            $params = $this->detectParameters($routingArray[$i]['path']);

            $route_gen = "";

            if (empty($params)) {
                $route_gen = $this->generateRoute($routingArray[$i]['name'], null, $appname);
            }

            if (!empty($params)) {
                array_push($rt, array("routename" =>  $routingArray[$i]['name'], "path" =>
                    $routingArray[$i]['path'], "controller" => $controller, "method" =>
                    $function . "Activity", "visibility" => $routingArray[$i]['visibility'], "params" =>
                    $params, "m_allow" => $this->methodAllowedTransform($routingArray[$i]['m_allow']),
                    "r_parameters" => $routingArray[$i]['r_parameters']));
            } else {
                array_push($rt, array("routename" =>  $routingArray[$i]['name'], "path" =>
                    $routingArray[$i]['path'], "controller" => $controller, "method" =>
                    $function . "Activity", "route_gen" => $route_gen, "visibility" =>
                    $routingArray[$i]['visibility'], "m_allow" =>
                    $this->methodAllowedTransform($routingArray[$i]['m_allow']), "r_parameters" =>
                    $routingArray[$i]['r_parameters']));
            }
        }
        return ($rt);
    }


    /**
     * Transform method allowed argument to array
     * @param string $methods Method allowed
     * @return array Method allowed array format
     * @throws Server500
     */
    private function methodAllowedTransform(string $methods):array
    {
        if (is_string($methods) || $this->isJsonMercureFormat($methods)) {
            switch ($methods) {
                case $this->isJsonMercureFormat($methods) == 1:
                    $r = $this->trsJsonMercureToArray($methods);
                    foreach ($r as $one) {
                        if ($this->checkMethodExist($one)) {
                            continue;
                        }
                    }
                    return ($r);
                    break;
                case is_string($methods):
                    if ($this->checkMethodExist($methods)) {
                        return (array($methods));
                    }
                    break;
                default:
                    new Server500(new \ArrayObject(
                        array("explain" => "Invalid format for Allowed methods request (m_allow)",
                            "solution" => "Please check the 'm_allow' tag format")
                    ));
            }
        } else {
            new Server500(new \ArrayObject(
                array("explain" => "Invalid format for Allowed methods request (m_allow)",
                    "solution" => "Please check the 'm_allow' tag format")
            ));
        }
        return (array());
    }



    /**
     * Check if request method exist
     * @param string $method Method request
     * @return int If method exist
     * @throws Server500
     */
    private function checkMethodExist(string $method):int
    {
        if (in_array($method, $this->methodsReq)) {
            return (1);
        } else {
            new Server500(new \ArrayObject(array("explain" => "Unknown method $method for Allowed method request",
                "solution" => "Allowed methods request must be ".json_encode($this->methodsReq))));
        }
        return (0);
    }

    /** Check if string is a JSON Mercure
     * @param string $string string methods request
     * @return int If it's a json mercure or not
     */
    private function isJsonMercureFormat(string $string):int
    {
        $len =  strlen($string);

        if ($len > 3 && ($string[0] == "{" && $string[$len - 1] == "}")) {
            $string = str_replace("{", "", $string);
            $string = str_replace("}", "", $string);
            $r = explode(',', $string);
            return (!in_array(" ", $r))? 1 : 0;
        }
        return (0);
    }


    /** Transform JSON Mercure Format to array
     * @param string $string string methods request
     * @return array Array contains allowed methods
     */
    private function trsJsonMercureToArray(string $string):array
    {
        $len =  strlen($string);

        if ($len > 3 && ($string[0] == "{" && $string[$len - 1] == "}")) {
            $string = str_replace("{", "", $string);
            $string = str_replace("}", "", $string);
            $r = explode(',', $string);
            return (!in_array(" ", $r))? $r : array();
        }
        return (array());
    }


    /**
     * Get all routings
     * @return array $routing get all routing contains
     * @throws \Exception
     */
    public function getallRouting():array
    {
        $routings = array();
        $appc = $this->getMaster("Apps");
        $apps = $appc->getAllApps();

        $routingArray = array();
        $pattern = '/\s*/m';
        $replace = '';

        foreach ($apps as $one) {
            $routers = scandir(FEnv::get("framework.root") . "apps/" . $one->name . "/Routing");
            foreach ($routers as $file) {
                $scope = null;
                if ($file == "." || $file == ".." || false === strpos($file, ".merc")) {
                    continue;
                }
                if (($router = fopen((FEnv::get("framework.apps")) .
                    $one->name . "/Routing/" . $file, "r"))) {
                    $rtarray = array("activity" => "", "path" => "", "name" => "");
                    $start = 0;
                    $end = 0;
                    $croute = 0;
                    while ($listen = fgets($router, 1024)) {
                        $listen = preg_replace($pattern, $replace, $listen);

                        if ($listen === "") {
                            continue;
                        }
                        if ($listen === "route:" && $start == 0 && $end === 0) {
                            $start = 1;
                            continue;
                        } elseif ($listen === "endroute" && $start === 1 & $end === 0) {
                            $end = 1;
                            array_push($routingArray, $rtarray);
                        } elseif (($this->strlikeInArray($listen, array("activity", "path", "name")) !== false) ||
                            ($this->strlikeInArray($listen, array("activity", "path",
                                    "name", "visibility")) !== false)) {
                            $listen = explode(':', $listen);
                            $rtarray[$listen[0]] = $listen[1];
                        }

                        if ($start === 1 && $end === 1) {
                            $rtarray = array("method" => "", "path" => "", "name" => "", "visibility" => "private");
                            $start = $end = 0;
                            $croute++;
                        }
                    }
                    $newfilename = str_replace(".merc", "", $file);
                    $view = $this->generateRoute(
                        'iumio_manager_routing_manager_get_one',
                        array("filename" => $newfilename, "appname" => $one->name)
                    );
                    $remove = $this->generateRoute(
                        'iumio_manager_routing_manager_remove_one',
                        array("filename" => $newfilename, "appname" => $one->name)
                    );
                    array_push($routings, array("name" => $file, "count_route" => $croute, "app" => $one->name,
                        'view' => $view, 'remove' => $remove));
                }
            }
        }

        return ($routings);
    }

    /**
     * Get all routing file
     * @return Renderer
     * @throws \Exception
     */
    public function getallActivity()
    {
        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK",
            "results" => $this->getallRouting())));
    }

    /**
     * Remove a routing file
     * @param string $filename
     * @param string $appname
     * @return Renderer
     * @throws \Exception
     */
    public function removeActivity(string $filename, string $appname)
    {
        Server::delete(FEnv::get("framework.root")."apps/$appname/Routing/$filename.merc", "file");
        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK")));
    }

    /** Rebuild Js routing file
     * @return Renderer
     * @throws \Exception
     */
    public function rebuildjsActivity()
    {
        $rt = new JsRouting();
        $rt->build();
        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK")));
    }

    /** Get a routing file
     * @param string $filename
     * @param string $appname
     * @return Renderer
     * @throws \Exception
     */
    public function getOneActivity(string $filename, string $appname)
    {
        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK", "results" =>
            $this->getMercureContent($filename, $appname))));
    }


    /**
     * Detect parameters type for a specific route
     * @param string $parameters Line this parameters contains
     * @param string $keyword_ft keyword for instruction : Check if parameters
     * @return array Return the parameters formatted
     * @throws Server500 If a delimiter is missing
     */
    private function detectParametersType(string $parameters, string $keyword_ft):array
    {
        if ($keyword_ft != "parameters") {
            return (array());
        }
        $parameters = str_replace("parameters:", "", $parameters);
        if (!(isset($parameters[0]) && $parameters[0] == "{")) {
            throw new Server500(new \ArrayObject(array("explain" => "Delimiter '{' is missing for parameters keyword",
                "solution" => "Please check Mercure file")));
        }
        if (!(isset($parameters[strlen($parameters) - 1 ]) && $parameters[strlen($parameters) - 1] == "}")) {
            throw new Server500(new \ArrayObject(array("explain" => "Delimiter '}' is missing for parameters keyword",
                "solution" => "Please check Mercure file")));
        }

        $parameters = str_replace("{", "", $parameters);
        $parameters = str_replace("}", "", $parameters);
        $e = explode(',', $parameters);

        $param = $this->splitParameters($e);
        if (count($param) == 0) {
            throw new Server500(new \ArrayObject(array("explain" => "Unknow error on parameters in Mercure file",
                "solution" => "Please check Mercure file")));
        }
        return ($param);
    }

    /**
     * Split required parameters to have the parameters name and parameters type in array
     * array (paramName => paramType)
     * @param array $params Parameters required
     * @return array Parameters formatted
     * @throws Server500 If delimiter ':' does not exist
     */
    final private function splitParameters(array $params):array
    {
        $a = array();
        foreach ($params as $one) {
            if (strpos($one, ":") !== false) {
                $u = explode(":", $one);
                if (count($u) < 2) {
                    throw new Server500(new \ArrayObject(array("explain" => "Delimiter ':' is missing ",
                        "solution" => "Please check Mercure file")));
                }
                $this->checkScalarValue($u[1]);
                array_push($a, $u);
            } else {
                throw new Server500(new \ArrayObject(array("explain" => "Delimiter ':' is missing ",
                    "solution" => "Please check Mercure file")));
            }
        }
        return ($a);
    }

    /**
     * Check if the scalar type exist in Mercure
     * @param string $scalar Scalar type
     * @return bool The result of test
     * @throws Server500 If the scalar does not exist
     */
    final private function checkScalarValue(string $scalar):bool
    {
        if ($this->strlikeInArray($scalar, $this->scalar) !== false) {
            return (true);
        } else {
            throw new Server500(new \ArrayObject(array("explain" => "Unknow type $scalar in Mercure",
                "solution" => "Type must be : ".json_encode($this->scalar))));
        }
    }
}
