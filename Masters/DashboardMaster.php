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

use iumioFramework\Core\Base\Http\HttpListener;
use iumioFramework\Core\Base\Server\GlobalServer;
use iumioFramework\Core\Base\Renderer\Renderer;
use iumioFramework\Core\Requirement\Environment\FEnv;
use iumioFramework\Core\Exception\AbstractServer;
use iumioFramework\Core\Exception\Server500;
use iumioFramework\Core\Masters\MasterCore;
use iumioFramework\Core\Base\Json\JsonListener as JL;

/**
 * Class DashboardMaster
 * @package iumioFramework\Core\Manager
 * @category Framework
 * @licence  MIT License
 * @link https://framework.iumio.com
 * @author   RAFINA Dany <dany.rafina@iumio.com>
 */

class DashboardMaster extends MasterCore
{
    /**
     * Start FGM dashboard
     * @return Renderer
     * @throws \Exception
     */
    public function indexActivity()
    {
        $file = JL::open(FEnv::get("framework.config.core.config.file"));
        $date =  new \DateTime($file->installation->date);
        $file->installation = $date->format('Y/m/d');
        $serv = new GlobalServer();
        return($this->render("index", array("env" => strtolower(FEnv::get("framework.env")),
            "selected" => "dashboard", "fi" => $file,
            'https' => null !== $serv->get("HTTPS") && 'on' === $serv->get("HTTPS"),
            "loader_msg" => "Framework Graphic Manager - Dashboard")));
    }

    /**
     * Edit framework U3i
     * @param string $u3i Unique identifier of iumio instance
     * @return Renderer
     * @throws \Exception
     */
    public function editU3iActivity()
    {
        $request = HttpListener::createFromGlobals();
        $u3i = ($request->get("u3i"));
        $file = JL::open(FEnv::get("framework.config.core.config.file"));
        $file->u3i = $u3i;
        JL::put(FEnv::get("framework.config.core.config.file"), json_encode($file, JSON_PRETTY_PRINT));
        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK")));
    }

    /** Get the last debug logs (limited by 10)
     * @return Renderer JSON response log list
     * @throws Server500
     * @throws \Exception
     */
    public function getlastlogActivity():Renderer
    {
        $last = array_values((AbstractServer::getLogs("", 10)));
        $lastn = array();
        for ($i = 0; $i < count($last); $i++) {
            $last[$i]['log_url'] = $this->generateRoute(
                "iumio_manager_logs_manager_get_one",
                array("uidie" => $last[$i]['uidie'], "env" => strtolower(FEnv::get("framework.env")))
            );
            $last[$i]['time'] =  strtotime($last[$i]['time']);
            array_push($lastn, $last[$i]);
        }

        return ((new Renderer())->jsonRenderer(array("code" => 200, "results" => $lastn)));
    }

    /**
     * Edit framework 200 event
     * @return Renderer
     * @throws \Exception
     */
    public function edit200EventActivity()
    {
        $request = HttpListener::createFromGlobals();
        $mode = ($request->get("mode"));
        $file = JL::open(FEnv::get("framework.config.core.config.file"));
        if (!in_array($mode, ["true", "false"])) {
            return ((new Renderer())->jsonRenderer(array("code" => 500,
                "results" => "Undefined value for 200 Event : $mode ")));
        }
        $file->{"200_log"} = (bool)$mode;
        JL::put(FEnv::get("framework.config.core.config.file"), json_encode($file, JSON_PRETTY_PRINT));
        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK")));
    }

    /**
     * Edit framework 404 event
     * @return Renderer
     * @throws \Exception
     */
    public function edit404EventActivity()
    {
        $request = HttpListener::createFromGlobals();
        $mode = ($request->get("mode"));
        $file = JL::open(FEnv::get("framework.config.core.config.file"));
        if (!in_array($mode, [true, false])) {
            return ((new Renderer())->jsonRenderer(array("code" => 500,
                "results" => "Undefined value for 404 Event : $mode ")));
        }
        $file->{"404_log"} = (bool)$mode;
        JL::put(FEnv::get("framework.config.core.config.file"), json_encode($file, JSON_PRETTY_PRINT));
        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK")));
    }

    /**
     * Get default App
     * @return Renderer JSON response log list
     * @throws Server500
     */
    public function getDefaultAppActivity():Renderer
    {
        $default = array();
        $file = (array) JL::open(FEnv::get("framework.config.core.apps.file"));
        foreach ($file as $one) {
            if ($one->isdefault == "yes") {
                $default = $one;
                break;
            }
        }
        return ((new Renderer())->jsonRenderer(array("code" => 200, "results" => $default)));
    }

    /**
     * Get the framework statistics
     * @return Renderer JSON response log list
     * @throws Server500
     */
    public function getFrameworkStatisticsActivity():Renderer
    {

        $appmaster = $this->getMaster('Apps');
        $appstats = $appmaster->getStatisticsApp();

        // ROUTING STATS IS TOO LONG - CHECK IT TO OPTIMIZE
        $routiningmaster = $this->getMaster('Routing');
        $routingstats = $routiningmaster->getStatisticsRouting();
        
        $dbmaster = $this->getMaster('Databases');
        $dbstats = $dbmaster->getStatisticsDatabases();


        $logsmaster = $this->getMaster('Logs');
        $logsstats = $logsmaster->getStatisticsLogs();

        $servicemaster = $this->getMaster('Services');
        $servicestats = $servicemaster->getStatisticsServices();



        return ((new Renderer())->jsonRenderer(array("code" => 200, "results" => array("apps" => $appstats,
            "routes" => $routingstats, "dbs" => $dbstats, "logs" => $logsstats, "services" => $servicestats))));
    }
}
