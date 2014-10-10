<?php
/**
 * Author: Eugine Terentev <eugine@terentev.net>
 */
namespace trntv\systeminfo;

/**
 * Class SystemInfo
 * @package trntv\systeminfo
 */
class SystemInfo {
    /**
     * @return string
     */
    public static function getPhpVersion(){
        return phpversion();
    }

    /**
     * @return string
     */
    public static function getOS(){
        return php_uname('s r v');
    }

    /**
     * @return string
     */
    public static function getLinuxOSRelease(){
        if(!self::getIsWindows()) {
            return shell_exec('/usr/bin/lsb_release -ds');
        }
    }

    /**
     * @return string
     */
    public static function getLinuxKernelVersion(){
        if(!self::getIsWindows()){
            return shell_exec('/bin/uname -r');
        }
    }

    /**
     * @return string
     */
    public static function getHostname(){
        return php_uname('n');
    }

    /**
     * @return string
     */
    public static function getArchitecture(){
        return php_uname('m');
    }

    /**
     * @return bool
     */
    public static function getIsWindows(){
        return strpos(strtolower(PHP_OS),'win') === 0;
    }

    /**
     * @return null
     */
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

    /**
     * @param bool $key
     * @return array|null
     */
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

    /**
     * @return array|null
     */
    public static function getCpuCores(){
        return self::getCpuinfo('cpu cores');
    }

    /**
     * @return mixed
     */
    public static function getServerIP(){
        return self::getIsISS() ? $_SERVER['LOCAL_ADDR'] : $_SERVER['SERVER_ADDR'];
    }

    /**
     * @return string
     */
    public static function getExternalIP(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://ipecho.net/plain");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 700);
        $address = curl_exec($ch);
        curl_close($ch);
        return $address;
    }

    /**
     * @return mixed
     */
    public static function getServerSoftware(){
        return $_SERVER['SERVER_SOFTWARE'];
    }

    /**
     * @return bool
     */
    public static function getIsISS(){
        return false; // todo: ISS
    }

    /**
     * @return bool
     */
    public static function getIsNginx(){
        return strpos(strtolower(self::getServerSoftware()), 'nginx') !== false;
    }

    /**
     * @return bool
     */
    public static function getIsApache(){
        return strpos(strtolower(self::getServerSoftware()), 'apache') !== false;
    }

    /**
     * @param int $what
     * @return string
     */
    public static function getPhpInfo($what = -1){
        ob_start();
        phpinfo($what);
        return ob_get_clean();
    }

    /**
     * @return array
     */
    public static function getPHPDisabledFunctions(){
        return array_map('trim',explode(',',ini_get('disable_functions')));
    }

    /**
     * @param array $hosts
     * @param int $count
     * @return array
     */
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
            $ping[$hosts[$i]] = isset($result[0]) ? $result[0] : false;
        }
        return $ping;
    }

    /**
     * @param integer $key
     * @return mixed string|array
     */
    public static function getLoadAverage($key = false){
        $la = array_combine([1,5,15], sys_getloadavg());
        return ($key !== false && isset($la[$key])) ? $la[$key] : $la;
    }

    /**
     * @param int $interval
     * @return array
     */
    public static function getCpuUsage($interval = 1){
        if(self::getIsWindows()){
            // todo
        } else {
            function stat(){
                $stat = @file_get_contents('/proc/stat');
                $stat = explode("\n", $stat);
                $result = [];
                foreach($stat as $v){
                    $v = explode(" ", $v);
                    if(
                        isset($v[0])
                        && strpos(strtolower($v[0]), 'cpu') === 0
                        && preg_match('/cpu[\d]/sim', $v[0])
                    ){
                        $result[] = array_slice($v, 1, 4);
                    }

                }
                return $result;
            }
            $stat1 = stat();
            usleep($interval * 1000000);
            $stat2 = stat();
            $usage = [];
            for($i = 0; $i < self::getCpuCores(); $i++){
                $total = array_sum($stat2[$i]) - array_sum($stat1[$i]);
                $idle = $stat2[$i][3] - $stat1[$i][3];
                $usage[$i] = $total !== 0 ? ($total - $idle) / $total : 0;
            }
            return $usage;
        }
    }

    /**
     * @return array
     */
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

    /**
     * @return bool|int
     */
    public static function getTotalMem(){
        if(self::getIsWindows()){
            //todo
        } else {
            $meminfo = self::getMemoryInfo();
            return isset($meminfo['MemTotal']) ? intval($meminfo['MemTotal']) * 1024 : false;
        }
    }

    /**
     * @return bool|int
     */
    public static function getFreeMem(){
        if(self::getIsWindows()){
            //todo
        } else {
            $meminfo = self::getMemoryInfo();
            return isset($meminfo['MemFree']) ? intval($meminfo['MemFree']) * 1024 : false;
        }
    }

    /**
     * @return bool|int
     */
    public static function getTotalSwap(){
        if(self::getIsWindows()){
            //todo
        } else {
            $meminfo = self::getMemoryInfo();
            return isset($meminfo['SwapTotal']) ? intval($meminfo['SwapTotal']) * 1024 : false;
        }
    }

    /**
     * @return bool|int
     */
    public static function getFreeSwap(){
        if(self::getIsWindows()){
            //todo
        } else {
            $meminfo = self::getMemoryInfo();
            return isset($meminfo['SwapFree']) ? intval($meminfo['SwapFree']) * 1024 : false;
        }
    }

    /**
     *
     */
    public static function getDiskUsage(){
        // todo: Function
    }

    /**
     * @param \PDO $connection
     * @return mixed
     */
    public static function getDbInfo(\PDO $connection){
        return $connection->getAttribute(\PDO::ATTR_SERVER_INFO);
    }

    /**
     * @param \PDO $connection
     * @return mixed
     */
    public static function getDbType(\PDO $connection){
        return $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * @param $connection
     * @return string
     */
    public static function getDbVersion($connection){
        if(is_a($connection, 'PDO')){
            return $connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } else {
            return mysqli_get_server_info($connection);
        }
    }
} 