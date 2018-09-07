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
use iumioFramework\Core\Base\Renderer\Renderer;
use iumioFramework\Core\Requirement\Environment\FEnv;
use iumioFramework\Core\Exception\AbstractServer;
use iumioFramework\Core\Exception\Server404;
use iumioFramework\Core\Exception\Server500;
use iumioFramework\Core\Masters\MasterCore;
use iumioFramework\Core\Base\Json\JsonListener as JL;
use PHPMailer\PHPMailer\Exception;

/**
 * Class LogsMaster
 * @package iumioFramework\Core\Manager
 * @category Framework
 * @licence  MIT License
 * @link https://framework.iumio.com
 * @author   RAFINA Dany <dany.rafina@iumio.com>
 */

class LogsMaster extends MasterCore
{
    /**
     * Start FGM dashboard
     * @return Renderer
     * @throws Server500
     * @throws \Exception
     */
    public function logsActivity():Renderer
    {
        $file = JL::open(FEnv::get("framework.config.core.config.file"));
        $date =  new \DateTime($file->installation->date);
        $file->installation = $date->format('Y/m/d');

        return($this->render("logs", array("selected" => "logsmanager",
            "env" => strtolower(FEnv::get("framework.env")), "loader_msg" => "Logs Manager")));
    }

    /**
     * Get log details
     * @param string $uidie Unique identifier of iumio Exception
     * @param string $env Environment name
     * @throws Server404 If uidie does not exist
     * @throws Server500 If environement does not exist
     * @return Renderer
     * @throws \Exception
     */
    public function logsdetailsActivity(string $uidie, string $env):Renderer
    {
        if (!in_array($env, array("dev", "prod"))) {
            throw new Server500(new \ArrayObject(array("explain" => "Bad environment name $env",
                "solution" => "Environment must be 'dev' or 'prod' ")));
        }
        $onelogs = AbstractServer::getLogs($env, 0, $uidie);

        if (empty($onelogs)) {
            throw new Server404(new \ArrayObject(array("explain" => "The error with uidie [".$uidie."] does not exist",
                "solution" => "Check the Uidie")));
        }
        return($this->render("logsdetails", array("details" => $onelogs, "selected" =>
            "logsmanager", "env" => strtolower($env), "loader_msg" => "Log with Uidie : $uidie")));
    }

    /** Get logs statistics for all
     * @return array Logs statistics
     * @throws
     */
    public function getStatisticsLogs():array
    {
        return (array("dev" => $this->getStatisticsLogsDev(), "prod" => $this->getStatisticsLogsProd()));
    }

    /** Get logs statistics for dev
     * @return array Logs dev statistics
     * @throws \Exception
     */
    public function getStatisticsLogsDev():array
    {
        $last = array_values((AbstractServer::getLogs("dev")));
        $success = 0;
        $critical = 0;
        $others = 0;
        $errors = 0;
        for ($i = 0; $i < count($last); $i++) {
            $one = $last[$i];
            if ($one['code'] == 200) {
                $success++;
            } else {
                $errors++;
                if ($one['code'] == 500) {
                    $critical++;
                } else {
                    $others++;
                }
            }
        }
        return (array("errors" => $errors, "critical" => $critical,
            "success" => $success, "others" => $others));
    }

    /** Get logs statistics for prod
     * @return array Logs prod statistics
     * @throws
     */
    public function getStatisticsLogsProd():array
    {
        $last = array_values((AbstractServer::getLogs("prod")));
        $success = 0;
        $critical = 0;
        $others = 0;
        $errors = 0;
        for ($i = 0; $i < count($last); $i++) {
            $one = $last[$i];
            if ($one['code'] == 200) {
                $success++;
            } else {
                $errors++;
                if ($one['code'] == 500) {
                    $critical++;
                } else {
                    $others++;
                }
            }
        }
        return (array("errors" => $errors, "critical" => $critical,
            "success" => $success, "others" => $others));
    }

    /** Get the last debug logs (unlimited) with min and max position
     * @param $env string environment name
     * @return Renderer JSON response log list
     * @throws Server404
     * @throws Server500
     * @throws \Exception
     */
    public function getlogActivity(string $env):Renderer
    {
        if (!in_array($env, array("dev", "prod"))) {
            throw new Server500(new \ArrayObject(array("explain" => "Bad environment name $env",
                "solution" => "Environment must be 'dev' or 'prod' ")));
        }
        $last = array_values((AbstractServer::getLogs($env)));
        $lastn = array();
        $request = $this->get('request');
        $loglastpos =  $request->get('pos');
        $orderby = 29;
        if ($loglastpos == null) {
            return ((new Renderer())->jsonRenderer(array("code" => 500, "results" => "Cannot get the last position")));
        }
        $loglastpos = (int)$loglastpos;
        $max = $loglastpos + $orderby;
        for ($i = $loglastpos; $i <= $max; $i++) {
            if (!isset($last[$i])) {
                continue;
            }
            $one = $last[$i];
            $last["env"] = strtoupper($env);
            $last[$i]['time'] = strtotime($last[$i]['time']);
            $last[$i]['log_url'] = $this->generateRoute(
                "iumio_manager_logs_manager_get_one",
                array("uidie" => $one['uidie'], "env" => $env)
            );
            array_push($lastn, $last[$i]);
        }
        return ((new Renderer())->jsonRenderer(array("code" => 200, "results" => $lastn)));
    }

    /** clear log of dev or prod environment
     * @param $env string Environment
     * @return Renderer JSON response
     * @throws Server500
     * @throws \Exception
     */
    public function clearActivity(string $env):Renderer
    {
        if (!in_array($env, array("dev", "prod"))) {
            throw new Server500(new \ArrayObject(array("explain" => "Bad environment name $env",
                "solution" => "Environment must be 'dev' or 'prod' ")));
        }
        Server::delete(FEnv::get("framework.logs").strtolower($env).".log", 'file');
        @Server::create(FEnv::get("framework.logs").strtolower($env).".log", 'file');

        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK")));
    }
}
