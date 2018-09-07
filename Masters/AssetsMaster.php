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

use iumioFramework\Core\Requirement\Environment\FEnv;
use iumioFramework\Core\Exception\Server500;
use iumioFramework\Core\Masters\MasterCore;
use iumioFramework\Core\Base\Renderer\Renderer;
use iumioFramework\Core\Server\Server;
use ManagerApp\Masters\Libs\Diff;

/**
 * Class AssetsMaster
 * @package iumioFramework\Core\Manager
 * @category Framework
 * @licence  MIT License
 * @link https://framework.iumio.com
 * @author   RAFINA Dany <dany.rafina@iumio.com>
 */

class AssetsMaster extends MasterCore
{
    /**
     * Going to assets manager
     * @return Renderer
     * @throws \Exception
     */
    public function assetsActivity()
    {
        return ($this->render("assetsmanager", array("selected" => "assetsmanager",
            "loader_msg" => "Assets Manager")));
    }

    /** Publish assets
     * @param string $appname App name
     * @param string $env Environment
     * @return Renderer
     * @throws \Exception
     */
    public function publishActivity(string $appname, string $env):Renderer
    {
        $this->publish($appname, $env);
        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK")));
    }


    /** Call Server publish assets
     * @param string $appname App name
     * @param string $env Environment
     * @throws \Exception
     */
    public function publish(string $appname, string $env)
    {
        $this->clear($appname, $env);
        if ($appname == "_all") {
            $dirs = scandir(FEnv::get("framework.apps"));
            foreach ($dirs as $dir) {
                if ($dir == ".") {
                    continue;
                }
                if ($dir == "..") {
                    continue;
                }
                if (!is_dir(FEnv::get("framework.root") . "apps/" . $dir)) {
                    continue;
                }
                if (in_array(strtolower($env), array("dev", "prod", "all"))) {
                    if (file_exists(FEnv::get("framework.root") . "apps/" . $dir . "/Front/Resources/")) {
                        if (strtolower($env) === "dev") {
                            Server::copy(
                                FEnv::get("framework.apps") . $dir . "/Front/Resources/",
                                FEnv::get("framework.root") . "public/components/apps/dev/" . strtolower($dir),
                                'directory',
                                true
                            );
                        } elseif (strtolower($env) == "prod") {
                            Server::copy(
                                FEnv::get("framework.apps"). $dir . "/Front/Resources/",
                                FEnv::get("framework.root") .
                                "public/components/apps/prod/" . strtolower($dir),
                                'directory',
                                false
                            );
                        } else {
                            if (Server::exist(FEnv::get("framework.root") .
                                "apps/" . $dir . "/Front/Resources/")) {
                                Server::copy(FEnv::get("framework.root") .
                                    "apps/" . $dir . "/Front/Resources/", FEnv::get("framework.root") .
                                    "public/components/apps/dev/" . strtolower($dir), 'directory', true);
                                Server::copy(FEnv::get("framework.root") .
                                    "apps/" . $dir . "/Front/Resources/", FEnv::get("framework.root") .
                                    "public/components/apps/prod/" . strtolower($dir), 'directory', false);
                            }
                        }
                    }
                }
            }
        } elseif ($appname !== "") {
            if (in_array(strtolower($env), array("dev", "prod", "all"))) {
                if (file_exists(FEnv::get("framework.root")."apps/" . ($appname) . "/Front/Resources/")) {
                    if (strtolower($env) === "dev") {
                        Server::copy(FEnv::get("framework.root") .
                            "apps/" . ($appname) . "/Front/Resources/", FEnv::get("framework.root") .
                            "public/components/apps/dev/" . strtolower($appname), 'directory', true);
                    } elseif (strtolower($env) == "prod") {
                        Server::copy(FEnv::get("framework.root") .
                            "apps/" . ($appname) . "/Front/Resources/", FEnv::get("framework.root") .
                            "public/components/apps/prod/" . strtolower($appname), 'directory', false);
                    } else {
                        Server::copy(FEnv::get("framework.root") .
                            "apps/" . ($appname) . "/Front/Resources/", FEnv::get("framework.root") .
                            "public/components/apps/dev/" . strtolower($appname), 'directory', true);
                        Server::copy(FEnv::get("framework.root") .
                            "apps/" . ($appname) . "/Front/Resources/", FEnv::get("framework.root") .
                            "public/components/apps/prod/" . strtolower($appname), 'directory', false);
                    }
                }
            }
        }
    }

    /** clear assets of all or one app
     * @param string $appname App name
     * @param string $env Environment
     * @return Renderer JSON response
     * @throws \Exception
     */
    public function clearActivity(string $appname, string $env):Renderer
    {
        $this->clear($appname, $env);
        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK")));
    }

    /** clear assets of all or one app
     * @param string $appname App name
     * @param string $env Environment
     * @return int JSON response
     * @throws \Exception
     */
    public function clear(string $appname, string $env):int
    {
        if ($appname == "_all" && in_array(strtolower($env), array("dev", "prod", "all"))) {
            if (in_array(strtolower($env), array("dev", "prod"))) {
                Server::delete(FEnv::get("framework.web.components.apps").
                    strtolower($env), 'directory');
                Server::create(FEnv::get("framework.web.components.apps").
                    strtolower($env), 'directory');
            } else {
                Server::delete(FEnv::get("framework.web.components.apps").("dev"), 'directory');
                Server::create(FEnv::get("framework.web.components.apps").("dev"), 'directory');

                Server::delete(FEnv::get("framework.web.components.apps").("prod"), 'directory');
                Server::create(FEnv::get("framework.web.components.apps").("prod"), 'directory');
            }
        }
        if ($appname != "" && in_array(strtolower($env), array("dev", "prod", "all"))) {
            if (in_array(strtolower($env), array("dev", "prod"))) {
                Server::delete(FEnv::get("framework.web.components.apps").
                    strtolower($env)."/".strtolower($appname), 'directory');
            } else {
                Server::delete(FEnv::get("framework.web.components.apps").
                    ("dev")."/".strtolower($appname), 'directory');
                Server::delete(
                    FEnv::get("framework.web.components.apps").
                    ("prod")."/".strtolower($appname),
                    'directory'
                );
            }
        }
        return (1);
    }

    /** Get all info for each app assets
     * @return Renderer
     * @throws Server500
     * @throws \Exception
     */
    public function assetsinfoAllActivity():Renderer
    {
        /**
         * 0 : AppName
         * 1 : Have assets (1 ==> contains assets, 0 ==> not exist,  2 ==> Empty)
         * 3 : Perms on Dev
         * 4 : Perms on Prod
         * 5 : Status dev (0==> "Need to publish (redColor)", 1==> "OK (Green Color)")
         * 6 : Status prod (0==> "Need to publish (redColor)", 1==> "OK (Green Color)")
         * 7 : Action (Modal with url clear prod and dev , url publish prod and dev)
         */

        $appm = $this->getMaster("Apps");
        $apps = $appm->getAllApps();
        $diff = new Diff();
        $assetsapp = array();
        foreach ($apps as $app => $val) {
            $appname = $val->name;
            $app_assets = FEnv::get("framework.root") . "apps/" . ($appname) . "/Front/Resources/";
            $app_webassets_dev = FEnv::get("framework.root") . "public/components/apps/dev/" . strtolower($appname);
            $app_webassets_prod = FEnv::get("framework.root") . "public/components/apps/prod/" . strtolower($appname);

            $hassets = 0;
            $prod_hassets = 0;
            $dev_hassets = 0;

            if (is_dir($app_assets)) {
                $hassets++;
            }
            if (is_dir($app_webassets_dev)) {
                $dev_hassets++;
            }
            if (is_dir($app_webassets_prod)) {
                $prod_hassets++;
            }
            if ($hassets == 1 && $this->dirIsEmpty($app_assets)) {
                $hassets = 2;
            }


            $perm_dev =
                ($dev_hassets == 1)? substr(sprintf('%o', fileperms($app_webassets_dev)), -4) : 0000;
            $perm_prod =
                ($prod_hassets == 1)? substr(sprintf('%o', fileperms($app_webassets_prod)), -4) : 0000;

            $clear_dev = $this->generateRoute(
                "iumio_manager_assets_manager_clear",
                array("appname" => $appname, "env" => "dev"),
                null,
                true
            );
            $publish_dev = $this->generateRoute(
                "iumio_manager_assets_manager_publish",
                array("appname" => $appname, "env" => "dev"),
                null,
                true
            );
            ;
            $clear_prod = $this->generateRoute(
                "iumio_manager_assets_manager_clear",
                array("appname" => $appname, "env" => "prod"),
                null,
                true
            );
            ;
            $publish_prod = $this->generateRoute(
                "iumio_manager_assets_manager_publish",
                array("appname" => $appname, "env" => "prod"),
                null,
                true
            );

            $clear_all = $this->generateRoute(
                "iumio_manager_assets_manager_clear",
                array("appname" => $appname, "env" => "all"),
                null,
                true
            );
            ;
            $publish_all = $this->generateRoute(
                "iumio_manager_assets_manager_publish",
                array("appname" => $appname, "env" => "all"),
                null,
                true
            );

            $status_dev = 1;
            $status_prod = 1;


            if ($hassets == 1) {
                $webapp = ($this->recursiveScandir($app_assets));

                foreach ($webapp as $key => $value) {
                    $webapp[$key] = str_replace(FEnv::get("framework.root") . "apps/" . ($appname) .
                        "/Front/Resources/", "", $value);
                }
            }

            //// DEV ///

            if ($dev_hassets == 1 && $hassets == 1) {
                $webdev = ($this->recursiveScandir($app_webassets_dev));

                foreach ($webdev as $key => $value) {
                    $webdev[$key] = str_replace(FEnv::get("framework.root") . "public/components/apps/dev/" .
                        strtolower($appname), "", $value);
                }

                if (count(array_diff($webapp, $webdev)) > 0) {
                    $status_dev = 0;
                }

                if ($dev_hassets == 1 && $status_dev == 1 && $hassets == 1) {
                    for ($i = 0; $i < count($webapp); $i++) {
                        if (!file_exists(FEnv::get("framework.root") .
                            "public/components/apps/dev/" . strtolower($appname) .
                            "/" . $webdev[$i])) {
                            $status_dev = 0;
                            break;
                        }
                        $def = $diff::compareFiles(FEnv::get("framework.root") .
                            "public/components/apps/dev/" . strtolower($appname) .
                            "/" . $webdev[$i], FEnv::get("framework.root") .
                            "apps/" . ($appname) . "/Front/Resources/" . $webapp[$i]);
                        for ($u = 0; $u < count($def); $u++) {
                            if ($def[$u][1] > 0) {
                                $status_dev = 0;
                                break;
                            }
                        }
                        if ($status_dev == 0) {
                            break;
                        }
                    }
                }
            } else {
                $status_dev = 0;
            }

            /// END ///


            //// PROD ///

            if ($prod_hassets == 1 &&  $hassets == 1) {
                $webprod = ($this->recursiveScandir($app_webassets_prod));

                foreach ($webprod as $key => $value) {
                    $webprod[$key] = str_replace(FEnv::get("framework.root") .
                        "public/components/apps/prod/" . strtolower($appname), "", $value);
                }

                if (count(array_diff($webapp, $webprod)) > 0) {
                    $status_prod = 0;
                }

                if ($prod_hassets == 1 && $status_prod == 1) {
                    for ($i = 0; $i < count($webapp); $i++) {
                        if (!file_exists(FEnv::get("framework.root") . "public/components/apps/prod/" .
                            strtolower($appname) . "/" . $webprod[$i])) {
                            $status_dev = 0;
                            break;
                        }
                        $def = $diff::compareFiles(FEnv::get("framework.root") .
                            "public/components/apps/prod/" . strtolower($appname) .
                            "/" . $webprod[$i], FEnv::get("framework.root") . "apps/" .
                            ($appname) . "/Front/Resources/" . $webprod[$i]);
                        for ($u = 0; $u < count($def); $u++) {
                            if ($def[$u][1] > 0) {
                                $status_prod = 0;
                                break;
                            }
                        }
                        if ($status_prod == 0) {
                            break;
                        }
                    }
                }
            } else {
                $status_prod = 0;
            }

            /// END ///
            ///
            array_push(
                $assetsapp,
                array("name" => $appname,
                    "haveassets" => $hassets,
                    "dev_perms" => $perm_dev,
                    "prod_perms" => $perm_prod,
                    "status_dev" => $status_dev,
                    "status_prod" => $status_prod,
                    "clear" => array("dev" => $clear_dev, "prod" => $clear_prod, "all" => $clear_all),
                    "publish" => array("dev" => $publish_dev, "prod" => $publish_prod, "all" => $publish_all)
                )
            );
        }

        return ((new Renderer())->jsonRenderer(array("code" => 200, "msg" => "OK", "results" => $assetsapp)));
    }

    /** Scan directory and subdirectory
     * @param string $dir Path
     * @return array
     */
    public function recursiveScandir(string $dir)
    {
        $contents = array();

        foreach (scandir($dir) as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$file;

            if (is_dir($path)) {
                $contents = array_merge($contents, $this->recursiveScandir($path));
            } else {
                $contents[] = $path;
            }
        }
        return ($contents);
    }

    /**
     * Check if a directory is empty (a directory with just '.svn' or '.git' is empty)
     *
     * @param string $dirname
     * @return bool
     */
    public function dirIsEmpty($dirname)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        foreach (scandir($dirname) as $file) {
            if (!in_array($file, array('.','..','.svn','.git'))) {
                return false;
            }
        }
        return true;
    }
}
