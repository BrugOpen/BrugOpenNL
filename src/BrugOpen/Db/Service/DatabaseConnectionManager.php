<?php
namespace Grip\Vwm\Db\Service;

use BrugOpen\Core\Context;

class DatabaseConnectionManager
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @var \PDO
     */
    private $connection;

    /**
     *
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
    }

    /**
     *
     * @return \PDO
     */
    public function getConnection()
    {
        $connection = null;

        if ($this->connection == null) {

            if ($createdConnection = $this->createConnection()) {

                $connection = $createdConnection;
                $this->connection = $createdConnection;
            } else {

                $this->connection = false;
            }
        } else {

            if ($this->connection !== false) {

                $connection = $this->connection;
            }
        }

        return $connection;
    }

    /**
     *
     * @return \PDO
     */
    public function createConnection()
    {
        $connection = null;

        $config = $this->getDatabaseConnectionConfig();

        if ($config) {

            if ($config['driver'] == 'pdo_mysql') {

                $username = $config['user'];
                $password = $config['password'];
                $driverOptions = null;

                $dsn = 'mysql:';
                if (isset($config['host']) && $config['host'] != '') {
                    $dsn .= 'host=' . $config['host'] . ';';
                }
                if (isset($config['port'])) {
                    $dsn .= 'port=' . $config['port'] . ';';
                }
                if (isset($config['dbname'])) {
                    $dsn .= 'dbname=' . $config['dbname'] . ';';
                }
                if (isset($config['unix_socket'])) {
                    $dsn .= 'unix_socket=' . $config['unix_socket'] . ';';
                }
                if (isset($config['charset'])) {
                    $dsn .= 'charset=' . $config['charset'] . ';';
                }

                try {
                    $connection = new \PDO($dsn, $username, $password, $driverOptions);
                } catch (\PDOException $e) {

                    trigger_error('Could not create PDO connection: ' . $e->getMessage());
                }

                if ($connection) {

                    if (version_compare(PHP_VERSION, '5.3.6') < 0) {

                        // charset is ignored before 5.3.6
                        $connection->exec("set names utf8");
                    }
                }
            }
        }

        return $connection;
    }

    /**
     *
     * @return string[]
     */
    public function getDatabaseConnectionConfig()
    {
        $conn = array();

        if ($context = $this->context) {

            if ($contextConfig = $context->getConfig()) {

                if ($databaseParam = $contextConfig->getParam('database')) {

                    if ($parsedUrl = $this->parseDSN($databaseParam)) {

                        $type = 'mysql';

                        $conn = array(
                            'driver' => 'pdo_' . $type,
                            'user' => $parsedUrl['username'],
                            'password' => $parsedUrl['password'],
                            'host' => $parsedUrl['hostspec'],
                            'dbname' => $parsedUrl['database'],
                            'charset' => 'utf8'
                        );
                    }
                } else {

                    $type = 'mysql';

                    $configParams = $contextConfig->getParams();

                    $conn = array(
                        'driver' => 'pdo_' . $type,
                        'user' => $configParams['DB_USER'],
                        'password' => $configParams['DB_PASS'],
                        'host' => $configParams['DB_HOST'],
                        'dbname' => $configParams['DB_NAME'],
                        'charset' => 'utf8'
                    );
                }
            }
        }

        return $conn;
    }

    /**
     * Parse a data source name
     *
     * Additional keys can be added by appending a URI query string to the
     * end of the DSN.
     *
     * The format of the supplied DSN is in its fullest form:
     * <code>
     * phptype(dbsyntax)://username:password@protocol+hostspec/database?option=8&another=true
     * </code>
     *
     * Most variations are allowed:
     * <code>
     * phptype://username:password@protocol+hostspec:110//usr/db_file.db?mode=0644
     * phptype://username:password@hostspec/database_name
     * phptype://username:password@hostspec
     * phptype://username@hostspec
     * phptype://hostspec/database
     * phptype://hostspec
     * phptype(dbsyntax)
     * phptype
     * </code>
     *
     * @param string $dsn
     *            Data Source Name to be parsed
     *
     * @return array an associative array with the following keys:
     *         + phptype: Database backend used in PHP (mysql, odbc etc.)
     *         + dbsyntax: Database used with regards to SQL syntax etc.
     *         + protocol: Communication protocol to use (tcp, unix etc.)
     *         + hostspec: Host specification (hostname[:port])
     *         + database: Database to use on the DBMS server
     *         + username: User name for login
     *         + password: Password for login
     */
    public function parseDSN($dsn)
    {
        $parsed = array(
            'phptype' => false,
            'dbsyntax' => false,
            'username' => false,
            'password' => false,
            'protocol' => false,
            'hostspec' => false,
            'port' => false,
            'socket' => false,
            'database' => false
        );

        if (is_array($dsn)) {
            $dsn = array_merge($parsed, $dsn);
            if (! $dsn['dbsyntax']) {
                $dsn['dbsyntax'] = $dsn['phptype'];
            }
            return $dsn;
        }

        // Find phptype and dbsyntax
        if (($pos = strpos($dsn, '://')) !== false) {
            $str = substr($dsn, 0, $pos);
            $dsn = substr($dsn, $pos + 3);
        } else {
            $str = $dsn;
            $dsn = null;
        }

        $arr = array();
        // Get phptype and dbsyntax
        // $str => phptype(dbsyntax)
        if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
            $parsed['phptype'] = $arr[1];
            $parsed['dbsyntax'] = ! $arr[2] ? $arr[1] : $arr[2];
        } else {
            $parsed['phptype'] = $str;
            $parsed['dbsyntax'] = $str;
        }

        if (strlen($dsn) == 0) {
            return $parsed;
        }

        // Get (if found): username and password
        // $dsn => username:password@protocol+hostspec/database
        if (($at = strrpos($dsn, '@')) !== false) {
            $str = substr($dsn, 0, $at);
            $dsn = substr($dsn, $at + 1);
            if (($pos = strpos($str, ':')) !== false) {
                $parsed['username'] = rawurldecode(substr($str, 0, $pos));
                $parsed['password'] = rawurldecode(substr($str, $pos + 1));
            } else {
                $parsed['username'] = rawurldecode($str);
            }
        }

        // Find protocol and hostspec
        $match = array();
        if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
            // $dsn => proto(proto_opts)/database
            $proto = $match[1];
            $proto_opts = $match[2] ? $match[2] : false;
            $dsn = $match[3];
        } else {
            // $dsn => protocol+hostspec/database (old format)
            if (strpos($dsn, '+') !== false) {
                list ($proto, $dsn) = explode('+', $dsn, 2);
            }
            if (strpos($dsn, '/') !== false) {
                list ($proto_opts, $dsn) = explode('/', $dsn, 2);
            } else {
                $proto_opts = $dsn;
                $dsn = null;
            }
        }

        // process the different protocol options
        $parsed['protocol'] = (! empty($proto)) ? $proto : 'tcp';
        $proto_opts = rawurldecode($proto_opts);
        if (strpos($proto_opts, ':') !== false) {
            list ($proto_opts, $parsed['port']) = explode(':', $proto_opts);
        }
        if ($parsed['protocol'] == 'tcp') {
            $parsed['hostspec'] = $proto_opts;
        } elseif ($parsed['protocol'] == 'unix') {
            $parsed['socket'] = $proto_opts;
        }

        // Get dabase if any
        // $dsn => database
        if ($dsn) {
            if (($pos = strpos($dsn, '?')) === false) {
                // /database
                $parsed['database'] = rawurldecode($dsn);
            } else {
                // /database?param1=value1&param2=value2
                $parsed['database'] = rawurldecode(substr($dsn, 0, $pos));
                $dsn = substr($dsn, $pos + 1);
                if (strpos($dsn, '&') !== false) {
                    $opts = explode('&', $dsn);
                } else { // database?param1=value1
                    $opts = array(
                        $dsn
                    );
                }
                foreach ($opts as $opt) {
                    list ($key, $value) = explode('=', $opt);
                    if (! isset($parsed[$key])) {
                        // don't allow params overwrite
                        $parsed[$key] = rawurldecode($value);
                    }
                }
            }
        }

        return $parsed;
    }
}
