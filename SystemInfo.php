<?php
/**
 * Author: Eugine Terentev <eugine@terentev.net>
 */
namespace trntv\systeminfo;

class SystemInfo {
    public static function getPhpVersion(){
        return phpversion();
    }

    public static function getOS(){
        return php_uname('s r v');
    }

    public static function getLinuxOSRelease(){
        if(!self::getIsWindows()) {
            return shell_exec('/usr/bin/lsb_release -ds');
        }
    }

    public static function getLinuxKernelVersion(){
        if(!self::getIsWindows()){
            return shell_exec('/bin/uname -r');
        }
    }

    public static function getHostname(){
        return php_uname('n');
    }

    public static function getArchitecture(){
        return php_uname('m');
    }

    public static function getIsWindows(){
        return strpos(strtolower(PHP_OS),'win') === 0;
    }

    public static function getUptime(){
        if(self::getIsWindows()){
            return null; // todo: Windows
        } else {
            $uptime = @file_get_contents('/proc/uptime');
            if($uptime){
                $uptime = explode('.', $uptime);
                return isset($uptime[0]) ? $uptime[0] : null;
            }
        }
    }

    public static function getCpuinfo($key = false){
        if(self::getIsWindows()){
            return null; // todo: Windows
        } else {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            if($cpuinfo){
                $cpuinfo = explode("\n", $cpuinfo);
                $values = [];
                foreach($cpuinfo as $v){
                    $v = array_map("trim", explode(':', $v));
                    if(isset($v[0]) && isset($v[1])) {
                        $values[$v[0]] = $v[1];
                    }
                }
                return $key ?
                    (isset($values[$key]) ? $values[$key] : null)
                    : $values;
            }
        }
    }

    public static function getServerIP(){
        return self::getIsISS() ? $_SERVER['LOCAL_ADDR'] : $_SERVER['SERVER_ADDR'];
    }

    public static function getExternalIP(){
        return @file_get_contents('http://ipecho.net/plain');
    }

    public static function getServerSoftware(){
        return $_SERVER['SERVER_SOFTWARE'];
    }

    public static function getIsISS(){
        return false; // todo: ISS
    }
    public static function getIsNginx(){
        return strpos(strtolower(self::getServerSoftware()), 'nginx') !== false;
    }
    public static function getIsApache(){
        return strpos(strtolower(self::getServerSoftware()), 'apache') !== false;
    }

    public static function getPhpInfo($what = -1){
        ob_start();
        phpinfo($what);
        return ob_get_clean();
    }

    public static function getPHPDisabledFunctions(){
        return array_map('trim',explode(',',ini_get('disable_functions')));
    }

    public static function getPing(array $hosts = null, $count = 2){
        if(!$hosts){
            $hosts = array("gnu.org", "github.com", "wikipedia.org");
        }
        $ping = [];
        for ($i = 0; $i < count($hosts); $i++) {
            $command = self::getIsWindows()
                ? 'ping' // todo: Windows
                : "/bin/ping -qc {$count} {$hosts[$i]} | awk -F/ '/^rtt/ { print $5 }'";
            $result = array();
            exec($command, $result);
            $ping[$hosts[$i]] = $result[0];
        }
        return $ping;
    }

    public static function getLoadAverage($key = 0){
        return sys_getloadavg(); // todo: key
    }

    public static function getMemoryInfo(){
        if(self::getIsWindows()){
            return null; // todo: Windows
        } else {
            $data = explode("\n", file_get_contents("/proc/meminfo"));
            $meminfo = array();
            foreach ($data as $line) {
                $line = explode(":", $line);
                if(isset($line[0]) && isset($line[1])){
                    $meminfo[$line[0]] = trim($line[1]);
                }
            }
            return $meminfo;
        }
    }

    public static function getDiskUsage(){
        // todo: Function
    }
} 