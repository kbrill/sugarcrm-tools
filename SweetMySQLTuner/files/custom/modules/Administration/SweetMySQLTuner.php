<?php
//if the host is not local then ask for memory
//if the user isn root then ask for logon credentials
$version = "1.0.0";
$mysql = new mySQL_Tuner();
$mysql->db = DBManagerFactory::getInstance();

$le = $mysql->getLineEnding();
echo "<p style=\"font-family:'Lucida Console', monospace\">SweetMySQLTuner ($version) - MySQL High Performance Tuning Script for SugarCRM" . $le;
echo "Based on MySQLTuner 1.3.0 - Major Hayden <major@mhtx.net>" . $le;
echo "------------------------------------------------------------------------------" . $le;
echo 'Important Usage Guidelines:' . $le .
    '  >> Allow the MySQL server to run for at least 24-48 hours before trusting suggestions' . $le .
    "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;as the server will need to build up a history before the calculations in this" . $le .
    "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;program will yeild reliable results." . $le .
    '  >> Some routines may require root level privileges (program will provide warnings)' . $le;
echo "------------------------------------------------------------------------------" . $le;
$mysql->loadOpts();
$mysql->mysql_setup();
$mysql->os_setup();
$mysql->check_architecture();
$mysql->validate_mysql_version();
$mysql->check_storage_engines();
$mysql->security_recommendations();
$mysql->calculations();
$mysql->mysql_stats();
$mysql->make_recommendations();
echo "</p>";


//mysql_setup;				# Gotta login first
//os_setup;				# Set up some OS variables
//get_all_vars;				# Toss variables/status into hashes
//validate_mysql_version;			# Check current MySQL version
//check_architecture;			# Suggest 64-bit upgrade
//check_storage_engines;			# Show enabled storage engines
//security_recommendations;		# Display some security recommendations
//calculations;				# Calculate everything we need
//mysql_stats;				# Print the server stats
//make_recommendations;			# Make recommendations based on stats

class mySQL_Tuner
{
    protected $opts;
    protected $physical_memory;
    protected $swap_memory;
    protected $version;
    protected $myVar;
    protected $myStat;
    protected $myCalc = array();
    protected $os;
    protected $arch = 32;
    protected $remote;
    protected $generalRecommendation = array();
    protected $engineStats;
    protected $adjustVars = array();
    protected $majorVersion = 0;
    protected $minorVersion = 0;
    protected $revision = 0;
    public $db;

    /**
     * Returns system information
     * @return string
     */
    function getSys()
    {
        $os = strtolower(substr(PHP_OS, 0, 3));
        $web = strtolower(PHP_SAPI) !== "cli";

        if ($web)
            return 'web';
        else
            return $os;
    }

    /**
     * Returns Line Ending character(s) based on current usage of PHP
     * @return string
     */
    function getLineEnding()
    {
        $sys = $this->getSys();

        if ($sys === 'web')
            return "<br />";

        if ($sys === "win")
            return "\r\n";

        return "\n";
    }

    /**
     * Returns A set of tab character(s) based on current usage of PHP
     * @return string
     */
    function getTab()
    {
        $sys = $this->getSys();

        if ($sys === 'web')
            return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

        if ($sys === "win")
            return "\t";

        return "\n";
    }

    /**
     * Calculates the parameter passed in bytes, and then rounds it to one decimal place
     * @param int $bytes
     * @return string
     */
    function hr_bytes($bytes)
    {
        if ($bytes >= pow(1024, 3)) {
            return sprintf("%.1f", ($bytes / pow(1024, 3))) . "G";
        } elseif ($bytes >= pow(1024, 2)) {
            return sprintf("%.1f", ($bytes / pow(1024, 2))) . "M";
        } elseif ($bytes >= 1024) {
            return sprintf("%.1f", ($bytes / (1024))) . "K";
        } else {
            return $bytes . "B";
        }
    }

    /**
     * Calculates the parameter passed in bytes, and then rounds it to the nearest integer
     * @param int $bytes
     * @return string
     */
    function hr_bytes_rnd($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'k', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[intval(floor($base))];
    }

    /**
     * Calculates the parameter passed to the nearest power of 1000, then rounds it to the nearest integer
     * @param int $bytes
     * @return string
     */
    function hr_num($bytes)
    {
        if ($bytes >= pow(1000, 3)) {
            return round(($bytes / pow(1000, 3)), 0) . "G";
        } elseif ($bytes >= pow(1000, 2)) {
            return round(($bytes / pow(1000, 2)), 0) . "M";
        } elseif ($bytes >= 1000) {
            return round(($bytes / (1000)), 0) . "K";
        } else {
            return round($bytes, 0) . "B";
        }
    }

    /**
     * Calculates uptime to display in a more attractive form
     * @param int $uptime
     * @return string
     */
    function pretty_uptime($uptime)
    {
        $seconds = $uptime % 60;
        $minutes = (int)(($uptime % 3600) / 60);
        $hours = (int)(($uptime % 86400) / (3600));
        $days = (int)($uptime / (86400));

        if ($days > 0)
            return "${days}d ${hours}h ${minutes}m ${seconds}s";
        elseif ($hours > 0)
            return "${hours}h ${minutes}m ${seconds}s";
        elseif ($minutes > 0)
            return "${minutes}m ${seconds}s";
        else
            return "${seconds}s";
    }

    function is_remote()
    {
        global $config;
        if ($GLOBALS['db']->connectOptions['db_host_name'] != 'localhost' &&
            $GLOBALS['db']->connectOptions['db_host_name'] != '127.0.0.1' &&
            $GLOBALS['db']->connectOptions['db_host_name'] != $_SERVER['HTTP_HOST'] &&
            $GLOBALS['db']->connectOptions['db_host_name'] != $_SERVER['SERVER_NAME']
        ) {
            if (!isset($this->opts['forcemem'])) {
                if ($this->getSys() == 'web') {
                    $this->badPrint("You will need to fill in the MySQL Server Memory Amount field.");
                }
            }
            return true;
        } else {
            return false;
        }
    }

    function os_setup()
    {
        // Retrieves the memory installed on this machine
        // Figure out which OS we are running on, and detect support
        $this->os = $this->myVar['version_compile_os'];

        if ($this->opts['forcemem'] > 0) {
            $this->physical_memory = $this->opts['forcemem'] * 1048576;
            $this->infoPrint("Assuming {$this->opts['forcemem']} MB of physical memory");
            if ($this->opts['forceswap'] > 0) {
                $this->swap_memory = $this->opts['forceswap'] * 1048576;
                $this->infoPrint("Assuming {$this->opts['forceswap']} MB of swap space");
            } else {
                $this->swap_memory = 0;
                $this->badPrint("Assuming 0 MB of swap space (use --forceswap to specify)");
            }
        } else {
            if (stripos($this->os, 'Linux') != false) {
                $this->physical_memory = filter_var(`free -b | grep Mem | awk '{print \$2}'`, FILTER_SANITIZE_NUMBER_INT);
                $this->swap_memory = filter_var(`free -b | grep Swap | awk '{print \$2}'`, FILTER_SANITIZE_NUMBER_INT);
            } elseif (stripos($this->os, 'osx') !== false) {
                $this->physical_memory = filter_var(`sysctl -n hw.memsize`, FILTER_SANITIZE_NUMBER_INT);
                $this->swap_memory = filter_var(`sysctl -n vm.swapusage | colrm 1 8 | colrm 10`, FILTER_SANITIZE_NUMBER_INT) * 1024 * 1024;
            } elseif (stripos($this->os, 'BSD') !== false) {
                $this->physical_memory = filter_var(`sysctl -n hw.physmem`, FILTER_SANITIZE_NUMBER_INT);
                if ($this->physical_memory < 0) {
                    $this->physical_memory = filter_var(`sysctl -n hw.physmem64`, FILTER_SANITIZE_NUMBER_INT);
                }
                $this->swap_memory = filter_var(`swapctl -l | grep '^/' | awk '{ s+= \$2 } END { print s }'`, FILTER_SANITIZE_NUMBER_INT);
            } elseif ($this->os == 'BSD') {
                $this->physical_memory = filter_var(`sysctl -n hw.realmem`, FILTER_SANITIZE_NUMBER_INT);
                $this->swap_memory = filter_var(`swapinfo | grep '^/' | awk '{ s+= \$2 } END { print s }'`, FILTER_SANITIZE_NUMBER_INT);
            } elseif ($this->os == 'SunOS') {
                $this->physical_memory = filter_var(`/usr/sbin/prtconf | grep Memory | cut -f 3 -d ' '`, FILTER_SANITIZE_NUMBER_INT);
                trim($this->physical_memory);
                $this->physical_memory = $this->physical_memory * 1024 * 1024;
            } elseif ($this->os == 'AIX') {
                $this->physical_memory = filter_var(`lsattr -El sys0 | grep realmem | awk '{print \$2}'`, FILTER_SANITIZE_NUMBER_INT);
                trim($this->physical_memory);
                $this->physical_memory = $this->physical_memory * 1024;
                $this->swap_memory = filter_var(`lsps -as | awk -F"(MB| +)" '/MB /{print \$2}'`, FILTER_SANITIZE_NUMBER_INT);
                trim($this->swap_memory);
                $this->swap_memory = $this->swap_memory * 1024 * 1024;
            }
        }

        if (empty($this->swap_memory) && !isset($this->opts['forceswap'])) {
            $this->badPrint("Unable to determine total swap; use '--forceswap' to specify swap memory");
        }
        if (empty($this->physical_memory)) {
            $this->badPrint("Unable to determine total memory; use '--forcemem' and '--forceswap'");
            exit;
        }
        $this->goodPrint("Server Memory: " . $this->hr_bytes_rnd($this->physical_memory));
    }

    public function mysql_setup()
    {
        global $sugar_config;
        # We need to initiate at least one query so that our data is usable
        $rs = $this->db->query("SELECT VERSION() AS VERSION");
        $data = $this->db->fetchByAssoc($rs);
        $this->version = $data['VERSION'];
        list($this->majorVersion, $this->minorVersion, $this->revision) = explode(".", $this->version);
        $rs = $this->db->query("SHOW /*!50000 GLOBAL */ VARIABLES;");
        while ($row = $this->db->fetchByAssoc($rs)) {
            $this->myVar[$row['Variable_name']] = $row['Value'];

        }
        $rs = $this->db->query("SHOW /*!50000 GLOBAL */ STATUS;");
        while ($row = $this->db->fetchByAssoc($rs)) {
            $this->myStat[$row['Variable_name']] = $row['Value'];
        }

        $rs = $this->db->query("SHOW ENGINES;");
        while ($row = $this->db->fetchByAssoc($rs)) {
            $engine = $row['Engine'];
            if (strtolower($engine) == "federated" || strtolower($engine) == "blackhole") {
                $engine .= "_engine";
            } elseif (strtolower($engine) == "berkeleydb") {
                $engine = "bdb";
            }
            $key = 'have_' . $engine;
            if (strtolower($row['Support']) == 'default') {
                $this->myVar[$key] = "YES";
            } else {
                $this->myVar[$key] = $row['Support'];
            }
        }

        $config = $sugar_config['dbconfig'];
        $time_start = microtime(true);
        $conn = new mysqli($config['db_host_name'], $config['db_user_name'], $config['db_password'], $config['db_name']);
        $time_end = microtime(true);
        $time = ($time_end - $time_start);
        if ($time < 0.00025) {
            $this->goodPrint("MySQL/PHP connection time: " . round($time * 1000000, 2) . " μs");
        } else {
            $this->badPrint("MySQL/PHP connection time: " . round($time * 1000000, 2) . " μs");
        }
    }

    // Start up a ton of storage engine counts/statistics
    public function check_storage_engines()
    {
        $engines = "";
        $this->engineStats = array();
        $engineCount = array();
        echo "-------- Storage Engine Statistics -------------------------------------------" . $this->getLineEnding();
        if (isset($this->opts['skipsize']) && !empty($this->opts['skipsize'])) {
            $this->infoPrint("Skipped due to --skipsize option");
            return;
        }

        $rs = $this->db->query("SELECT ENGINE,SUPPORT FROM information_schema.ENGINES WHERE ENGINE NOT IN ('performance_schema','MyISAM','MERGE','MEMORY') ORDER BY ENGINE ASC");
        while ($row = $this->db->fetchByAssoc($rs)) {
            list($engine, $engineEnabed) = array($row['ENGINE'], $row['SUPPORT']);
            if ($engineEnabed == "YES" || $engineEnabed == "DEFAULT") {
                $engines .= $this->greenWrap("+" . $engine . ' ');
            } else {
                $engines .= $this->redWrap("-" . $engine . ' ');
            }
        }

        $rs = $this->db->query("SELECT ENGINE,SUM(DATA_LENGTH) AS DATA_SIZE,COUNT(ENGINE) AS DB_COUNT FROM information_schema.TABLES WHERE TABLE_SCHEMA NOT IN ('information_schema','mysql') AND ENGINE IS NOT NULL GROUP BY ENGINE ORDER BY ENGINE ASC");
        while ($row = $this->db->fetchByAssoc($rs)) {
            list($engine, $dataSize, $dbCount) = array($row['ENGINE'], $row['DATA_SIZE'], $row['DB_COUNT']);
            $this->engineStats[$engine] = $dataSize;
            $engineCount[$engine] = $dbCount;
        }
        echo "$engines" . $this->getLineEnding();

        $rs = $this->db->query("SELECT COUNT(TABLE_NAME) AS FRAGTABLES FROM information_schema.TABLES WHERE TABLE_SCHEMA NOT IN ('information_schema','mysql') AND Data_free > 0 AND NOT ENGINE='MEMORY';");
        $row = $this->db->fetchByAssoc($rs);
        $fragTables = $row['FRAGTABLES'];

        foreach ($this->engineStats as $engineName => $dataSize) {
            if ($dataSize > 0) {
                $this->infoPrint("Data in {$engineName} tables: " . $this->hr_bytes_rnd($dataSize) . " (Tables: " . $engineCount[$engineName] . ")");
            }
        }
        # If the storage engine isn't being used, recommend it to be disabled
        if (!isset($this->engineStats['InnoDB']) && isset($this->myVar['have_innodb']) && $this->myVar['have_innodb'] == "YES") {
            $this->badPrint("InnoDB is enabled but isn't being used");
            $this->generalRecommendation[] = "No InnoDB tables found, but SugarCRM requires InnoDB tables by default, Something is very wrong here.";
            $this->generalRecommendation[] = "Add skip-innodb to MySQL configuration to disable InnoDB";
        }
        if (!isset($this->engineStats['ISAM']) && isset($this->myVar['have_isam']) && $this->myVar['have_isam'] == "YES") {
            $this->badPrint("ISAM is enabled but isn't being used");
            $this->generalRecommendation[] = "Add skip-isam to MySQL configuration to disable ISAM (MySQL > 4.1.0) ";
        }
        if (!isset($this->engineStats['ISAM']) && isset($this->myVar['have_bdb']) && $this->myVar['have_bdb'] == "YES") {
            $this->badPrint("BDB is enabled but isn't being used");
            $this->generalRecommendation[] = "Add skip-bdb to MySQL configuration to disable BDB";
        }

        # Fragmented tables
        if ($fragTables > 0) {
            $this->badPrint("Total fragmented tables: {$fragTables}");
            $this->generalRecommendation[] = "Run OPTIMIZE TABLE to defragment tables for better performance";
        } else {
            $this->goodPrint("Total fragmented tables: {$fragTables}");
        }
    }


    public function security_recommendations()
    {
        echo "-------- Security Recommendations  -------------------------------------------" . $this->getLineEnding();
        $rs = $this->db->query("SELECT CONCAT(user, '\@', host) AS username FROM mysql.user WHERE password = '' OR password IS NULL;");
        $num_users = 0;
        while ($row = $this->db->fetchByAssoc($rs)) {
            $num_users++;
            $this->badPrint("User '" . $row['username'] . "' has no password set.");
        }
        if ($num_users == 0) {
            $this->goodPrint("All database users have passwords assigned");
        }
        if (isset($this->myVar['slow_query_log_file']) && is_readable($this->myVar['slow_query_log_file'])) {
            $this->badPrint("The slow query log may readable and should be checked");
            $this->generalRecommendation[] = "The slow query log ({$this->myVar['slow_query_log_file']}) should be protected because logged statements might contain passwords. See Section 6.1.2.3, 'Passwords and Logging' (https://dev.mysql.com/doc/refman/5.5/en/password-logging.html).";
        }
    }


    // Checks for 32-bit boxes with more than 2GB of RAM
    public function check_architecture()
    {
        $this->arch = 32;
        if (isset($this->myVar['version_compile_machine'])) {
            $arch = $this->myVar['version_compile_machine'];
            if (stripos($arch, '64')) {
                $this->arch = 64;
            }
        }
        if ($this->arch == 32) {
            if ($this->physical_memory > 2147483648) {
                $this->badPrint("Switch to 64-bit OS - MySQL cannot currently use all of your RAM");
            } else {
                $this->goodPrint("Operating on 32-bit architecture with less than 2GB RAM");
            }
        }
        if ($this->arch == 64) {
            $this->goodPrint("Operating on 64-bit architecture");
        }
    }

    /**
     * parseArgs Command Line Interface (CLI) utility function.
     * @usage               $args = parseArgs($_SERVER['argv']);
     * @author              Patrick Fisher <patrick@pwfisher.com>
     * @source              https://github.com/pwfisher/CommandLine.php
     */
    function parseArgs($argv)
    {
        array_shift($argv);
        $o = array();
        foreach ($argv as $a) {
            if (substr($a, 0, 2) == '--') {
                $eq = strpos($a, '=');
                if ($eq !== false) {
                    $o[substr($a, 2, $eq - 2)] = substr($a, $eq + 1);
                } else {
                    $k = substr($a, 2);
                    if (!isset($o[$k])) {
                        $o[$k] = true;
                    }
                }
            } else if (substr($a, 0, 1) == '-') {
                if (substr($a, 2, 1) == '=') {
                    $o[substr($a, 1, 1)] = substr($a, 3);
                } else {
                    foreach (str_split(substr($a, 1)) as $k) {
                        if (!isset($o[$k])) {
                            $o[$k] = true;
                        }
                    }
                }
            } else {
                $o[] = $a;
            }
        }
        return $o;
    }

    /**
     * Loads options into options array, based on command line parms, or get/post params
     */
    function loadOpts()
    {
        global $argv;

        $opts = array(
            'nobad' => false,
            'nogood' => false,
            'noinfo' => false,
            'nocolor' => false,
            'forcemem' => false,
            'forceswap' => false,
            'host' => false,
            'socket' => false,
            'port' => false,
            'user' => false,
            'pass' => false,
            'skipsize' => false,
            'checkversion' => false,
            'help' => false,
        );

        if (isset($argv)) {
            foreach ($this->parseArgs($argv) as $key => $value) {
                if (isset($opts[$key]))
                    $this->opts[$key] = $value;
            }
        }

        if (isset($_REQUEST)) {
            foreach ($_REQUEST as $key => $value) {
                if (isset($opts[$key]))
                    $this->opts[$key] = $value;
            }
        }
    }

    /**
     * Show usage information then exit
     * @return string
     */
    function showUsage()
    {
        global $version;
        return ("   MySQLTuner $version - MySQL High Performance Tuning Script" . $this->getLineEnding() .
            '   Bug reports, feature requests, and downloads at https://github.com/zeryl/MySQLTuner-PHP' . $this->getLineEnding() .
            '   Maintained by Zeryl (lordsaryon@gmail.com) - Licensed under GPL' . $this->getLineEnding() .
            '   Original by Major Hayden (major@mhtx.net)' . $this->getLineEnding() .
            $this->getLineEnding() .
            '   Important Usage Guidelines:' . $this->getLineEnding() .
            '      To run the script with the default options, run the script without arguments' . $this->getLineEnding() .
            '      Allow MySQL server to run for at least 24-48 hours before trusting suggestions' . $this->getLineEnding() .
            '      Some routines may require root level privileges (script will provide warnings)' . $this->getLineEnding() .
            '      You must provide the remote server\'s total memory when connecting to other servers' . $this->getLineEnding() .
            $this->getLineEnding() .
            '   Connection and Authentication' . $this->getLineEnding() .
            '      --host <hostname>    Connect to a remote host to perform tests (default: localhost)' . $this->getLineEnding() .
            '      --socket <socket>    Use a different socket for a local connection' . $this->getLineEnding() .
            '      --port <port>        Port to use for connection (default: 3306)' . $this->getLineEnding() .
            '      --user <username>    Username to use for authentication' . $this->getLineEnding() .
            '      --pass <password>    Password to use for authentication' . $this->getLineEnding() .
            $this->getLineEnding() .
            '   Performance and Reporting Options' . $this->getLineEnding() .
            '      --skipsize           Don\'t enumerate tables and their types/sizes (default: on)' . $this->getLineEnding() .
            '                             (Recommended for servers with many tables)' . $this->getLineEnding() .
//            '      --checkversion       Check for updates to MySQLTuner (default: don\'t check)' . $this->getLineEnding() .
            '      --forcemem <size>    Amount of RAM installed in megabytes' . $this->getLineEnding() .
            '      --forceswap <size>   Amount of swap memory configured in megabytes' . $this->getLineEnding() .
            $this->getLineEnding() .
            '   Output Options:' . $this->getLineEnding() .
            '      --nogood             Remove OK responses' . $this->getLineEnding() .
            '      --nobad              Remove negative/suggestion responses' . $this->getLineEnding() .
            '      --noinfo             Remove informational responses' . $this->getLineEnding() .
            '      --nocolor            Don\'t print output in color' . $this->getLineEnding() .
            $this->getLineEnding());
    }



    ////OUTPUT SECTION

    /**
     * Returns proper color coding based on current system/usage of PHP
     * @param string $color
     */
    public function getColor($color)
    {
        $colors = array(
            'red' => array(
                'web' => '<font color="#FF0000">',
                'nix' => "\033[01;31m",
            ),
            'green' => array(
                'web' => '<font color="#00FF00">',
                'nix' => "\033[01;32m",
            ),
            'yellow' => array(
                'web' => '<font color="#000000">',
                'nix' => "\033[01;33m",
            ),
            'clear' => array(
                'web' => '</font>',
                'nix' => "\033[0m",
            )
        );

        if ($this->opts['nocolor']) {
            return;
        }

        $sys = $this->getSys();

        if ($sys != 'web') {
            $sys = 'nix';
        }

        if (!isset($colors[$color])) {
            return;
        }

        return ($colors[$color][$sys]);
    }

    public function formatString($string, $color)
    {
        return $this->getColor($color) . $string . $this->getColor('clear');
    }

    public function badPrint($string)
    {
        echo $this->formatString("[!!] ", "red");
        echo $string . $this->getLineEnding();
    }

    public function infoPrint($string)
    {
        $string = "[--] " . $string;
        echo $this->formatString($string, "yellow") . $this->getLineEnding();
    }

    public function goodPrint($string)
    {
        echo $this->formatString("[OK] ", "green") . $string . $this->getLineEnding();
    }

    public function greenWrap($string)
    {
        return $this->formatString($string, "green");
    }

    public function redWrap($string)
    {
        return $this->formatString($string, "red");
    }

    //CALCULATIONS

    public function calculations()
    {
        if ($this->myStat['Questions'] < 1) {
            $this->badPrint("Your server has not answered any queries - cannot continue...");
            die();
        }

        # Per-thread memory
        if (isset($this->myVar['read_buffer_size'])) {
            $this->myCalc['per_thread_buffers'] = $this->myVar['read_buffer_size'] +
                $this->myVar['read_rnd_buffer_size'] +
                $this->myVar['sort_buffer_size'] +
                $this->myVar['thread_stack'] +
                $this->myVar['join_buffer_size'];
        } else {
            $this->myCalc['per_thread_buffers'] = $this->myVar['record_buffer'] +
                $this->myVar['record_rnd_buffer'] +
                $this->myVar['sort_buffer'] +
                $this->myVar['thread_stack'] +
                $this->myVar['join_buffer_size'];
        }

        $this->myCalc['total_per_thread_buffers'] = $this->myCalc['per_thread_buffers'] * $this->myVar['max_connections'];
        $this->myCalc['max_total_per_thread_buffers'] = $this->myCalc['per_thread_buffers'] * $this->myStat['Max_used_connections'];

        # Server-wide memory
        $this->myCalc['max_tmp_table_size'] = ($this->myVar['tmp_table_size'] > $this->myVar['max_heap_table_size']) ? $this->myVar['max_heap_table_size'] : $this->myVar['tmp_table_size'];
        $this->myCalc['server_buffers'] = $this->myVar['key_buffer_size'] + $this->myCalc['max_tmp_table_size'];
        $this->myCalc['server_buffers'] += (isset($this->myVar['innodb_buffer_pool_size'])) ? $this->myVar['innodb_buffer_pool_size'] : 0;
        $this->myCalc['server_buffers'] += (isset($this->myVar['innodb_additional_mem_pool_size'])) ? $this->myVar['innodb_additional_mem_pool_size'] : 0;
        $this->myCalc['server_buffers'] += (isset($this->myVar['innodb_log_buffer_size'])) ? $this->myVar['innodb_log_buffer_size'] : 0;
        $this->myCalc['server_buffers'] += (isset($this->myVar['query_cache_size'])) ? $this->myVar['query_cache_size'] : 0;

        # Global memory
        $this->myCalc['max_used_memory'] = $this->myCalc['server_buffers'] + $this->myCalc['max_total_per_thread_buffers'];
        $this->myCalc['total_possible_used_memory'] = $this->myCalc['server_buffers'] + $this->myCalc['total_per_thread_buffers'];
        $this->myCalc['pct_physical_memory'] = intval(($this->myCalc['total_possible_used_memory'] * 100) / $this->physical_memory);

        # Slow queries
        $this->myCalc['pct_slow_queries'] = intval(($this->myStat['Slow_queries'] / $this->myStat['Questions']) * 100);

        # Connections
        $this->myCalc['pct_connections_used'] = intval(($this->myStat['Max_used_connections'] / $this->myVar['max_connections']) * 100);
        $this->myCalc['pct_connections_used'] = ($this->myCalc['pct_connections_used'] > 100) ? 100 : $this->myCalc['pct_connections_used'];

        # Key buffers
        $this->myCalc['pct_key_buffer_used'] = 0;

        if ($this->myStat['Key_read_requests'] > 0) {
            $this->myCalc['pct_keys_from_mem'] = sprintf("%.1f", (100 - (($this->myStat['Key_reads'] / $this->myStat['Key_read_requests']) * 100)));
        } else {
            $this->myCalc['pct_keys_from_mem'] = 0;
        }

        $rs = $this->db->query("SELECT IFNULL(SUM(INDEX_LENGTH),0) INDEXTOTAL FROM information_schema.TABLES WHERE TABLE_SCHEMA NOT IN ('information_schema') AND ENGINE = 'MyISAM';");
        $row = $this->db->fetchByAssoc($rs);
        $this->myCalc['total_myisam_indexes'] = $row['INDEXTOTAL'];

        if (isset($this->myCalc['total_myisam_indexes']) && $this->myCalc['total_myisam_indexes'] == 0) {
            $this->myCalc['total_myisam_indexes'] = "fail";
        }
        //above here check for mysql_version_ge(4)

        // Query cache
        $this->myCalc['query_cache_efficiency'] = sprintf("%.1f", ($this->myStat['Qcache_hits'] / ($this->myStat['Com_select'] + $this->myStat['Qcache_hits'])) * 100);
        if ($this->myVar['query_cache_size']) {
            $this->myCalc['pct_query_cache_used'] = sprintf("%.1f", 100 - ($this->myStat['Qcache_free_memory'] / $this->myVar['query_cache_size']) * 100);
        }
        if ($this->myStat['Qcache_lowmem_prunes'] == 0) {
            $this->myCalc['query_cache_prunes_per_day'] = 0;
        } else {
            $this->myCalc['query_cache_prunes_per_day'] = intval($this->myStat['Qcache_lowmem_prunes'] / ($this->myStat['Uptime'] / 86400));
        }


        # Sorting
        $this->myCalc['total_sorts'] = $this->myStat['Sort_scan'] + $this->myStat['Sort_range'];
        if ($this->myCalc['total_sorts'] > 0) {
            $this->myCalc['pct_temp_sort_table'] = intval(($this->myStat['Sort_merge_passes'] / $this->myCalc['total_sorts']) * 100);
        }

        # Joins
        $this->myCalc['joins_without_indexes'] = $this->myStat['Select_range_check'] + $this->myStat['Select_full_join'];
        $this->myCalc['joins_without_indexes_per_day'] = intval($this->myCalc['joins_without_indexes'] / ($this->myStat['Uptime'] / 86400));

        # Temporary tables
        if ($this->myStat['Created_tmp_tables'] > 0) {
            if ($this->myStat['Created_tmp_disk_tables'] > 0) {
                $this->myCalc['pct_temp_disk'] = intval(($this->myStat['Created_tmp_disk_tables'] / ($this->myStat['Created_tmp_tables'] + $this->myStat['Created_tmp_disk_tables'])) * 100);
            } else {
                $this->myCalc['pct_temp_disk'] = 0;
            }
        }

        # Table cache
        if ($this->myStat['Opened_tables'] > 0) {
            $this->myCalc['table_cache_hit_rate'] = intval($this->myStat['Open_tables'] * 100 / $this->myStat['Opened_tables']);
        } else {
            $this->myCalc['table_cache_hit_rate'] = 100;
        }

        # Open files
        if ($this->myVar['open_files_limit'] > 0) {
            $this->myCalc['pct_files_open'] = intval($this->myStat['Open_files'] * 100 / $this->myVar['open_files_limit']);
        }

        # Table locks
        if ($this->myStat['Table_locks_immediate'] > 0) {
            if ($this->myStat['Table_locks_waited'] == 0) {
                $this->myCalc['pct_table_locks_immediate'] = 100;
            } else {
                $this->myCalc['pct_table_locks_immediate'] = intval($this->myStat['Table_locks_immediate'] * 100 / ($this->myStat['Table_locks_waited'] + $this->myStat['Table_locks_immediate']));
            }
        }

        # Thread cache
        $this->myCalc['thread_cache_hit_rate'] = intval(100 - (($this->myStat['Threads_created'] / $this->myStat['Connections']) * 100));

        # Other
        if ($this->myStat['Connections'] > 0) {
            $this->myCalc['pct_aborted_connections'] = intval(($this->myStat['Aborted_connects'] / $this->myStat['Connections']) * 100);
        }
        if ($this->myStat['Questions'] > 0) {
            $this->myCalc['total_reads'] = $this->myStat['Com_select'];
            $this->myCalc['total_writes'] = $this->myStat['Com_delete'] + $this->myStat['Com_insert'] + $this->myStat['Com_update'] + $this->myStat['Com_replace'];
            if ($this->myCalc['total_reads'] == 0) {
                $this->myCalc['pct_reads'] = 0;
                $this->myCalc['pct_writes'] = 100;
            } else {
                $this->myCalc['pct_reads'] = intval(($this->myCalc['total_reads'] / ($this->myCalc['total_reads'] + $this->myCalc['total_writes'])) * 100);
                $this->myCalc['pct_writes'] = 100 - $this->myCalc['pct_reads'];
            }
        }

        # InnoDB
        if (isset($this->myVar['have_innodb']) && $this->myVar['have_innodb'] == "YES") {
            $this->myCalc['innodb_log_size_pct'] = ($this->myVar['innodb_log_file_size'] * 100 / $this->myVar['innodb_buffer_pool_size']);
        }
    }

    public function mysql_stats()
    {
        print "\n-------- Performance Metrics -------------------------------------------------" . $this->getLineEnding();
        # Show uptime, queries per second, connections, traffic stats
        $qps = 'UPTIME ERROR';
        if ($this->myStat['Uptime'] > 0) {
            $qps = sprintf("%.3f", $this->myStat['Questions'] / $this->myStat['Uptime']);
        }
        if ($this->myStat['Uptime'] < 86400) {
            $this->generalRecommendation[] = "MySQL started within last 24 hours - recommendations may be inaccurate";
        }
        $this->infoPrint("Up for: " . $this->pretty_uptime($this->myStat['Uptime']) . " (" . $this->hr_num($this->myStat['Questions']) .
            " q [" . $this->hr_num($qps) . " qps], " . $this->hr_num($this->myStat['Connections']) . " conn," .
            " TX: " . $this->hr_num($this->myStat['Bytes_sent']) . ", RX: " . $this->hr_num($this->myStat['Bytes_received']) . ")");

        $this->infoPrint("Reads / Writes: " . $this->myCalc['pct_reads'] . "% / " . $this->myCalc['pct_writes'] . "%");

        # Memory usage
        $this->infoPrint("Total buffers: " . $this->hr_bytes($this->myCalc['server_buffers']) .
            " global + " . $this->hr_bytes($this->myCalc['per_thread_buffers']) .
            " per thread ({$this->myVar['max_connections']} max threads)");

        if ($this->opts['buffers'] != 0) {
            $this->infoPrint("Global Buffers");
            $this->infoPrint(" +-- Key Buffer: " . $this->hr_bytes($this->myVar['key_buffer_size']));
            $this->infoPrint(" +-- Max Tmp Table: " . $this->hr_bytes($this->myCalc['max_tmp_table_size']));

            if (isset($this->myVar['innodb_buffer_pool_size'])) {
                $this->infoPrint(" +-- InnoDB Buffer Pool: " . $this->hr_bytes($this->myVar['innodb_buffer_pool_size']));
            }
            if (isset($this->myVar['innodb_additional_mem_pool_size'])) {
                $this->infoPrint(" +-- InnoDB Additional Mem Pool: " . $this->hr_bytes($this->myVar['innodb_additional_mem_pool_size']));
            }
            if (isset($this->myVar['innodb_log_buffer_size'])) {
                $this->infoPrint(" +-- InnoDB Log Buffer: " . $this->hr_bytes($this->myVar['innodb_log_buffer_size']));
            }
            if (isset($this->myVar['query_cache_size'])) {
                $this->infoPrint(" +-- Query Cache: " . $this->hr_bytes($this->myVar['query_cache_size']));
            }

            $this->infoPrint("Per Thread Buffers");
            $this->infoPrint(" +-- Read Buffer: " . $this->hr_bytes($this->myVar['read_buffer_size']));
            $this->infoPrint(" +-- Read RND Buffer: " . $this->hr_bytes($this->myVar['read_rnd_buffer_size']));
            $this->infoPrint(" +-- Sort Buffer: " . $this->hr_bytes($this->myVar['sort_buffer_size']));
            $this->infoPrint(" +-- Thread stack: " . $this->hr_bytes($this->myVar['thread_stack']));
            $this->infoPrint(" +-- Join Buffer: " . $this->hr_bytes($this->myVar['join_buffer_size']));
        }

        if ($this->arch && $this->arch == 32 && $this->myCalc['total_possible_used_memory'] > 2 * 1024 * 1024 * 1024) {
            $this->badPrint("Allocating > 2GB RAM on 32-bit systems can cause system instability");
            $this->badPrint("Maximum possible memory usage: " .
                $this->hr_bytes($this->myCalc['total_possible_used_memory']) .
                " (" . $this->myCalc['pct_physical_memory'] . "% of installed RAM)");
        } elseif ($this->myCalc['pct_physical_memory'] > 85) {
            $this->badPrint("Maximum possible memory usage: " . $this->hr_bytes($this->myCalc['total_possible_used_memory']) . " ($this->myCalc['pct_physical_memory']% of installed RAM)");
            $this->generalRecommendation[] = "Reduce your overall MySQL memory footprint for system stability";
        } else {
            $this->goodPrint("Maximum possible memory usage: " .
                $this->hr_bytes($this->myCalc['total_possible_used_memory']) .
                " ({$this->myCalc['pct_physical_memory']}% of installed RAM)");
        }

        # innodb_file_per_table
        if (isset($this->myVar['innodb_file_per_table'])) {
            if (strtoupper($this->myVar['innodb_file_per_table']) == 'ON') {
                $this->goodPrint("InnoDB File-Per-Table Tablespaces (innodb_file_per_table) set to ON");
            } else {
                $this->badPrint("InnoDB File-Per-Table Tablespaces (innodb_file_per_table) set to OFF");
                $this->generalRecommendation[] = "SugarCRM will run better with innodb_file_per_table set to ON - See https://dev.mysql.com/doc/refman/5.5/en/innodb-multiple-tablespaces.html";
            }
        }

        # Slow queries
        if ($this->myCalc['pct_slow_queries'] > 5) {
            $this->badPrint("Slow queries: {$this->myCalc['pct_slow_queries']}% (" .
                $this->hr_num($this->myStat['Slow_queries']) . "/" .
                $this->hr_num($this->myStat['Questions']) . ")");
        } else {
            $this->goodPrint("Slow queries: {$this->myCalc['pct_slow_queries']}% (" .
                $this->hr_num($this->myStat['Slow_queries']) . "/" .
                $this->hr_num($this->myStat['Questions']) . ")");
        }
        if ($this->myVar['long_query_time'] > 10) {
            $this->adjustVars[] = "long_query_time (<= 10)";
        }
        if (isset($this->myVar['log_slow_queries'])) {
            if ($this->myVar['log_slow_queries'] == "OFF") {
                $this->generalRecommendation[] = "Enable the slow query log to troubleshoot bad queries";
            }
        }
        #innodb_stats_on_metadata
        if (isset($this->myVar['innodb_stats_on_metadata'])) {
            if ($this->myVar['innodb_stats_on_metadata'] != 'OFF') {
                $this->generalRecommendation[] = "Set innodb_stats_on_metadata to OFF (http://dev.mysql.com/doc/refman/5.1/en/innodb-parameters.html#sysvar_innodb_stats_on_metadata)";
            }
        }

        # Connections
        if ($this->myCalc['pct_connections_used'] > 85) {
            $this->badPrint("Highest connection usage: $this->myCalc['pct_connections_used']%  ($this->myStat['Max_used_connections']/$this->myVar['max_connections'])");
            $this->adjustVars[] = "max_connections (> " . $this->myVar['max_connections'] . ")";
            $this->adjustVars[] = "wait_timeout (< " . $this->myVar['wait_timeout'] . ")";
            $this->adjustVars[] = "interactive_timeout (< " . $this->myVar['interactive_timeout'] . ")";
            $this->generalRecommendation[] = "Reduce or eliminate persistent connections to reduce connection usage";
        } else {
            $maxUsedPercent = $this->myStat['Max_used_connections'] / $this->myVar['max_connections'];
            $this->goodPrint("Highest usage of available connections: {$this->myCalc['pct_connections_used']}% ({$maxUsedPercent})");
        }

        # Key buffer
        if (!isset($this->myCalc['total_myisam_indexes'])) {
            $this->generalRecommendation[] = "Unable to calculate MyISAM indexes on remote MySQL server < 5.0.0";
        } elseif ($this->myCalc['total_myisam_indexes'] == 'fail') {
            $this->badPrint("Cannot calculate MyISAM index size - re-run script as root user");
        } elseif ($this->myCalc['total_myisam_indexes'] == "0") {
            $this->badPrint("None of your MyISAM tables are indexed - add indexes immediately");
        } else {
            if ($this->myVar['key_buffer_size'] < $this->myCalc['total_myisam_indexes'] && $this->myCalc['pct_keys_from_mem'] < 95) {
                $this->badPrint("Key buffer size / total MyISAM indexes: " .
                    $this->hr_bytes($this->myVar['key_buffer_size']) . "/" .
                    $this->hr_bytes($this->myCalc['total_myisam_indexes']));
                $this->adjustVars[] = "key_buffer_size (> " . $this->hr_bytes($this->myCalc['total_myisam_indexes']) . ")";
            } else {
                $this->goodPrint("Key buffer size / total MyISAM indexes: " .
                    $this->hr_bytes($this->myVar['key_buffer_size']) . "/" .
                    $this->hr_bytes($this->myCalc['total_myisam_indexes']));
            }
            if ($this->myStat['Key_read_requests'] > 0) {
                if ($this->myCalc['pct_keys_from_mem'] < 95) {
                    $this->badPrint("Key buffer hit rate: {$this->myCalc['pct_keys_from_mem']}% (" .
                        $this->hr_num($this->myStat['Key_read_requests']) . " cached / " .
                        $this->hr_num($this->myStat['Key_reads']) . " reads)");
                } else {
                    $this->goodPrint("Key buffer hit rate: {$this->myCalc['pct_keys_from_mem']}% (" .
                        $this->hr_num($this->myStat['Key_read_requests']) . " cached / " .
                        $this->hr_num($this->myStat['Key_reads']) . " reads");
                }
            } else {
                $this->infoPrint("No queries have run that would use keys");
            }
        }

        # Query cache
        if (class_exists('Memcache')) {
            $memcache = new Memcache;
            $isMemcacheAvailable = @$memcache->connect('localhost');
        }
        if ($this->myVar['query_cache_size'] < 1 && !$isMemcacheAvailable) {
            $this->badPrint("Query cache is disabled");
            $this->adjustVars[] = "query_cache_size (>= 8M but no more than 256M)";
        } elseif ($this->myVar['query_cache_type'] == "OFF" && $isMemcacheAvailable) {
            $this->badPrint("Query cache is disabled!");
            $this->adjustVars[] = "query_cache_type (=1), although having this OFF is not necessarily a bad thing";
        } elseif ($this->myStat['Com_select'] == 0 && $isMemcacheAvailable) {
            $this->badPrint("Query cache cannot be analyzed - no SELECT statements executed");
        } else {
            if ($isMemcacheAvailable) {
                $this->badPrint("Memcache detected, you should not run memcache and query_cache at the same time");
                $this->generalRecommendation[] = "You should disable query_cache. (https://dev.mysql.com/doc/refman/5.6/en/query-cache-configuration.html)";
                $this->adjustVars[] = "query_cache_size (=0)";
                $this->adjustVars[] = "query_cache_type (=0)";
            }
            if ($this->myCalc['query_cache_efficiency'] < 20) {
                $this->badPrint("Query cache efficiency: {$this->myCalc['query_cache_efficiency']}% (" .
                    $this->hr_num($this->myStat['Qcache_hits']) . " cached / " .
                    $this->hr_num($this->myStat['Qcache_hits'] + $this->myStat['Com_select']) . " selects)");
                $this->adjustVars[] = "query_cache_limit (> " . $this->hr_bytes_rnd($this->myVar['query_cache_limit']) . ", or use smaller result sets)";
            } else {
                $this->goodPrint("Query cache efficiency: {$this->myCalc['query_cache_efficiency']}% (" .
                    $this->hr_num($this->myStat['Qcache_hits']) . " cached / " .
                    $this->hr_num($this->myStat['Qcache_hits'] + $this->myStat['Com_select']) . " selects)");
            }
            if ($this->myCalc['query_cache_prunes_per_day'] > 98) {
                $this->badPrint("Query cache prunes per day: {$this->myCalc['query_cache_prunes_per_day']}");
                if ($this->myVar['query_cache_size'] > 256 * 1024 * 1024) {
                    $this->generalRecommendation[] = "Increasing the query_cache size over 256M may reduce performance";
                    $this->adjustVars[] = "query_cache_size (> " . $this->hr_bytes_rnd($this->myVar['query_cache_size']) . ") [see warning above]";
                } else {
                    $this->adjustVars[] = "query_cache_size (> " . $this->hr_bytes_rnd($this->myVar['query_cache_size']) . ")";
                }
            } else {
                $this->goodPrint("Query cache prunes per day: {$this->myCalc['query_cache_prunes_per_day']}");
                $this->goodPrint("Query cache size: " . $this->hr_bytes_rnd($this->myVar['query_cache_size']));
            }

            #query_cache_min_res_unit
            $averageQCache = ($this->myVar['query_cache_size'] - $this->myStat['Qcache_free_memory']) / $this->myStat['Qcache_queries_in_cache'];
            $this->goodPrint("Average size of a cached query: " . $this->hr_bytes_rnd($averageQCache, 0));
            if ($averageQCache < $this->myVar['query_cache_min_res_unit']) {
                $this->adjustVars[] = 'query_cache_min_res_unit (=' . $this->hr_bytes_rnd($averageQCache, 0) . ")";
            } else {
                $this->badPrint("Query_cache minimum resource unit (Avg > Max): ({$averageQCache} > {$this->myVar['query_cache_min_res_unit']})");
                $this->adjustVars[] = 'query_cache_min_res_unit (>' . $this->hr_bytes_rnd($averageQCache, 0) . ")";
            }
        }

        # Sorting
        if ($this->myCalc['total_sorts'] == 0) {
            # For the sake of space, we will be quiet here
            # No sorts have run yet
        } elseif ($this->myCalc['pct_temp_sort_table'] > 10) {
            $this->badPrint("Sorts requiring temporary tables: {$this->myCalc['pct_temp_sort_table']}% (" .
                $this->hr_num($this->myStat['Sort_merge_passes']) . " temp sorts / " .
                $this->hr_num($this->myCalc['total_sorts']) . " sorts)");
            $this->adjustVars[] = "sort_buffer_size (> " . $this->hr_bytes_rnd($this->myVar['sort_buffer_size']) . ")";
            $this->adjustVars[] = "read_rnd_buffer_size (> " . $this->hr_bytes_rnd($this->myVar['read_rnd_buffer_size']) . ")";
        } else {
            $this->goodPrint("Sorts requiring temporary tables: {$this->myCalc['pct_temp_sort_table']}% (" .
                $this->hr_num($this->myStat['Sort_merge_passes']) . " temp sorts / " .
                $this->hr_num($this->myCalc['total_sorts']) . " sorts)");
        }

        # Joins
        if ($this->myCalc['joins_without_indexes_per_day'] > 250) {
            $this->badPrint("Joins performed without indexes: {$this->myCalc['joins_without_indexes']}");
            $this->adjustVars[] = "join_buffer_size (> " . $this->hr_bytes($this->myVar['join_buffer_size']) . ", or always use indexes with joins)";
            $this->generalRecommendation[] = "Adjust your join queries to always utilize indexes";
        } else {
            $this->goodPrint("No joins have run without indexes");
        }

        # Temporary tables
        if ($this->myStat['Created_tmp_tables'] > 0) {
            if ($this->myCalc['pct_temp_disk'] > 25 && $this->myCalc['max_tmp_table_size'] < 256 * 1024 * 1024) {
                $this->badPrint("Temporary tables created on disk: {$this->myCalc['pct_temp_disk']}% (" .
                    $this->hr_num($this->myStat['Created_tmp_disk_tables']) . " on disk / " .
                    $this->hr_num($this->myStat['Created_tmp_disk_tables'] + $this->myStat['Created_tmp_tables']) . " total)");
                $this->adjustVars[] = "tmp_table_size (> " . $this->hr_bytes_rnd($this->myVar['tmp_table_size']) . ")";
                $this->adjustVars[] = "max_heap_table_size (> " . $this->hr_bytes_rnd($this->myVar['max_heap_table_size']) . ")";
                $this->generalRecommendation[] = "When making adjustments, make tmp_table_size/max_heap_table_size equal";
                $this->generalRecommendation[] = "Reduce your SELECT DISTINCT queries without LIMIT clauses";
            } elseif ($this->myCalc['pct_temp_disk'] > 25 && $this->myCalc['max_tmp_table_size'] >= 256) {
                $this->badPrint("Temporary tables created on disk: {$this->myCalc['pct_temp_disk']}% (" .
                    $this->hr_num($this->myStat['Created_tmp_disk_tables']) . " on disk / " .
                    $this->hr_num($this->myStat['Created_tmp_disk_tables'] + $this->myStat['Created_tmp_tables']) . " total)");
                $this->generalRecommendation[] = "Temporary table size is already large - reduce result set size";
                $this->generalRecommendation[] = "Reduce your SELECT DISTINCT queries without LIMIT clauses";
            } else {
                $this->goodPrint("Temporary tables created on disk: {$this->myCalc['pct_temp_disk']}% (" .
                    $this->hr_num($this->myStat['Created_tmp_disk_tables']) . " on disk / " .
                    $this->hr_num($this->myStat['Created_tmp_disk_tables'] + $this->myStat['Created_tmp_tables']) . " total)");
            }
        } else {
            $this->goodPrint("No temporary tables have been created");
        }

        # Thread cache
        if ($this->myVar['thread_cache_size'] == 0) {
            $this->badPrint("Thread cache is disabled");
            $this->generalRecommendation[] = "Set thread_cache_size to 4 as a starting value";
            $this->adjustVars[] = "thread_cache_size (start at 4)";
        } else {
            if ($this->myCalc['thread_cache_hit_rate'] <= 50) {
                $this->badPrint("Thread cache hit rate: {$this->myCalc['thread_cache_hit_rate']}% (" .
                    $this->hr_num($this->myStat['Threads_created']) . " created / " .
                    $this->hr_num($this->myStat['Connections']) . " connections)");
                $this->adjustVars[] = "thread_cache_size (> $this->myVar['thread_cache_size'])";
            } else {
                $this->goodPrint("Thread cache hit rate: {$this->myCalc['thread_cache_hit_rate']}% (" .
                    $this->hr_num($this->myStat['Threads_created']) . " created / " .
                    $this->hr_num($this->myStat['Connections']) . " connections)");
            }
        }

        // Table cache
        $table_cache_var = "";
        if ($this->myStat['Open_tables'] > 0) {
            if ($this->myCalc['table_cache_hit_rate'] < 20) {
                $this->badPrint("Table cache hit rate: {$this->myCalc['table_cache_hit_rate']}% (" .
                    $this->hr_num($this->myStat['Open_tables']) . " open / " .
                    $this->hr_num($this->myStat['Opened_tables']) . " opened)");
                if ($this->mysql_version_ge(5, 1)) {
                    $table_cache_var = "table_open_cache";
                } else {
                    $table_cache_var = "table_cache";
                }
                $this->adjustVars[] = $table_cache_var . " (> " . $this->myVar['table_open_cache'] . ")";
                $this->generalRecommendation[] = "Increase " . $table_cache_var . " gradually to avoid file descriptor limits";
                $this->generalRecommendation[] = "Read this before increasing " . $table_cache_var . " over 64: http://bit.ly/1mi7c4C";
            } else {
                $this->goodPrint("Table cache hit rate: {$this->myCalc['table_cache_hit_rate']}% (" .
                    $this->hr_num($this->myStat['Open_tables']) . " open / " .
                    $this->hr_num($this->myStat['Opened_tables']) . " opened)");
            }
        }

        # Open files
        if (isset($this->myCalc['pct_files_open'])) {
            if ($this->myCalc['pct_files_open'] > 85) {
                $this->badPrint("Open file limit used: {$this->myCalc['pct_files_open']}% (" .
                    $this->hr_num($this->myStat['Open_files']) . "/" .
                    $this->hr_num($this->myVar['open_files_limit']) . ")");
                $this->adjustVars[] = "open_files_limit (> " . $this->myVar['open_files_limit'] . ")";
            } else {
                $this->goodPrint("Open file limit used: {$this->myCalc['pct_files_open']}% (" .
                    $this->hr_num($this->myStat['Open_files']) . "/" .
                    $this->hr_num($this->myVar['open_files_limit']) . ")");
            }
        }

        # Table locks
        if (isset($this->myCalc['pct_table_locks_immediate'])) {
            if ($this->myCalc['pct_table_locks_immediate'] < 95) {
                $this->badPrint("Table locks acquired immediately: {$this->myCalc['pct_table_locks_immediate']}%");
                $this->generalRecommendation[] = "Optimize queries and/or use InnoDB to reduce lock wait";
            } else {
                $this->goodPrint("Table locks acquired immediately: {$this->myCalc['pct_table_locks_immediate']}% (" .
                    $this->hr_num($this->myStat['Table_locks_immediate']) . " immediate / " .
                    $this->hr_num($this->myStat['Table_locks_waited'] + $this->myStat['Table_locks_immediate']) . " locks)");
            }
        }

        # Performance options
        if (!$this->mysql_version_ge(4, 1)) {
            $this->generalRecommendation[] = "Upgrade to MySQL 4.1+ to use concurrent MyISAM inserts";
        } elseif ($this->myVar['concurrent_insert'] == "OFF") {
            $this->generalRecommendation[] = "Enable concurrent_insert by setting it to 'ON'";
        } elseif ($this->myVar['concurrent_insert'] == 0) {
            $this->generalRecommendation[] = "Enable concurrent_insert by setting it to 1";
        }
        if ($this->myCalc['pct_aborted_connections'] > 5) {
            $this->badPrint("Connections aborted: " . $this->myCalc['pct_aborted_connections'] . "%");
            $this->generalRecommendation[] = "Your applications are not closing MySQL connections properly";
        }

        # InnoDB
        if (isset($this->myVar['have_innodb']) && $this->myVar['have_innodb'] == "YES" && isset($this->engineStats['InnoDB'])) {
            if ($this->myVar['innodb_buffer_pool_size'] > $this->engineStats['InnoDB']) {
                $this->goodPrint("InnoDB buffer pool / data size: " . $this->hr_bytes($this->myVar['innodb_buffer_pool_size']) . "/" . $this->hr_bytes($this->engineStats['InnoDB']));
            } else {
                $this->badPrint("InnoDB  buffer pool / data size: " . $this->hr_bytes($this->myVar['innodb_buffer_pool_size']) . "/" . $this->hr_bytes($this->engineStats['InnoDB']));
                $this->adjustVars[] = "innodb_buffer_pool_size (>= " . $this->hr_bytes_rnd($this->engineStats['InnoDB']) . ")";
            }
            if (isset($this->myStat['Innodb_log_waits']) && $this->myStat['Innodb_log_waits'] > 0) {
                $this->badPrint("InnoDB log waits: " . $this->myStat['Innodb_log_waits']);
                $this->adjustVars[] = "innodb_log_buffer_size (>= " . $this->hr_bytes_rnd($this->myVar['innodb_log_buffer_size']) . ")";
            } else {
                $this->goodPrint("InnoDB log waits: " . $this->myStat['Innodb_log_waits']);
            }
        }
    }

    # Take the two recommendation arrays and display them at the end of the output
    public function make_recommendations()
    {
        print "\n-------- Recommendations -----------------------------------------------------" . $this->getLineEnding();
        if (count($this->generalRecommendation) > 0) {
            print "General recommendations:" . $this->getLineEnding();
            foreach ($this->generalRecommendation as $line) {
                echo wordwrap($line, 120, $this->getLineEnding() . $this->getTab());
                echo $this->getLineEnding();
            }
        }
        if (count($this->adjustVars) > 0) {
            echo $this->getLineEnding() . "Variables to adjust:" . $this->getLineEnding();
            if ($this->myCalc['pct_physical_memory'] > 80) {
                echo "*** MySQL's maximum memory usage is very high ***" . $this->getLineEnding() .
                    "*** Add RAM before increasing MySQL buffer variables ***" . $this->getLineEnding();
            }
            foreach ($this->adjustVars as $line) {
                echo $line . $this->getLineEnding();
            }
        }
        if (count($this->generalRecommendation) == 0 && count($this->adjustVars) == 0) {
            print "No additional performance recommendations are available." . $this->getLineEnding();
        }
    }

    # Checks for supported or EOL'ed MySQL versions
    public function validate_mysql_version()
    {
        if (!$this->mysql_version_ge(5)) {
            $this->badPrint("Your MySQL version " . $this->myVar['version'] . " is EOL software!  Upgrade soon!");
        } elseif ($this->mysql_version_ge(6)) {
            $this->badPrint("Currently running unsupported MySQL version " . $this->myVar['version']);
        } else {
            $this->goodPrint("Currently running supported MySQL version " . $this->myVar['version']);
        }
    }

    private function mysql_version_ge($major, $minor = 0, $revision = 0)
    {
        if (empty($major)) {
            die("ERROR: No mySQL version given to function mysql_version_ge()");
        }
        if ($major <= $this->majorVersion || ($major == $this->majorVersion && $minor == 0)) {
            return true;
        }
        if ($major == $this->majorVersion && $minor <= $this->minorVersion) {
            return true;
        }
        if ($major == $this->majorVersion && $minor == $this->minorVersion && $revision <= $this->revision) {
            return true;
        }
        return false;
    }
}