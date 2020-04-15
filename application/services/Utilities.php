<?php

class Application_Service_Utilities
{
    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }

    const STATUS_DISPLAY_DUAL = [
        [
            'id' => 0,
            'label' => 'Nieaktywny',
            'color' => '#cc0000',
        ],
        [
            'id' => 1,
            'label' => 'Aktywny',
            'color' => '#00cc00',
        ]
    ];

    const STATUS_DISPLAY_YESNO = [
        [
            'id' => 0,
            'label' => 'Nie',
            'color' => '#cc0000',
        ],
        [
            'id' => 1,
            'label' => 'Aktywny',
            'color' => '#00cc00',
        ]
    ];

    const STATUS_DISPLAY_YESNO_NEUTRAL = [
        [
            'id' => 0,
            'label' => 'Nie',
            'color' => '#d0d9e2',
        ],
        [
            'id' => 1,
            'label' => 'Aktywny',
            'color' => '#364757',
        ]
    ];

    const BASE_TYPE_GLOBAL = 1;
    const BASE_TYPE_LOCAL = 2;

    private $modelCache;

    private function __construct()
    {
        $this->modelCache = array();
    }

    public static function requestDirectory($dir, $create = true)
    {
        $result = false;

        try {
            if (!is_dir($dir)) {
                if ($create && mkdir($dir, 755, true)) {
                    $result = $dir;
                }
            } else {
                $result = $dir;
            }

        } catch (Exception $e) {

        }

        return $result;
    }

    public static function sortArray($array, $key, $order = 'asc', $caseSensitive = false)
    {
        $clone = $array;

        uasort($clone, function($a, $b) use ($key, $order, $caseSensitive) {
            if ($caseSensitive) {
                return $a[$key] - $b[$key];
            } elseif(!is_array($a[$key]) AND !is_array($b[$key])) {
                return strnatcmp(mb_strtolower($a[$key]), mb_strtolower($b[$key]));
            }
        });

        return $clone;
    }

    /**
     * @param array $array
     * @param array|string $keys
     * @param bool|true $assoc
     * @return array
     */
    public static function pullData($array, $keys, $assoc = true, $dimensions = 2)
    {
        $result = array();

        if ($dimensions === 1) {
            $array = array($array);
        }

        foreach ($array as $ak => $row) {
            if (is_string($keys)) {
                if (isset($row[$keys])) {
                    $result[$ak] = $row[$keys];
                }
            } else {
                foreach ($keys as $key) {
                    $result[$ak][$key] = $row[$key];
                }

                if (!$assoc) {
                    $result[$ak] = array_values($result[$ak]);
                }
            }
        }

        return $dimensions === 1 ? current($result) : $result;
    }

    /**
     * @param array $array
     * @param $key
     */
    public static function indexBy(&$array, $key, $multiple = false)
    {
        $result = array();
        if (!is_array($key)) {
            $key = [$key];
        }

        foreach ($array as $arrayValue) {
            $tempKey = $key;
            $rowResult = &$result;
            do {
                $currentKey = array_shift($tempKey);
                $currentKeyValue = self::getValue($arrayValue, $currentKey);

                $rowResult = &$rowResult[$currentKeyValue];

                if (empty($tempKey)) {
                    if ($multiple) {
                        $rowResult[] = $arrayValue;
                    } else {
                        $rowResult = $arrayValue;
                    }
                }
            } while (!empty($tempKey));
        }

        $array = $result;
    }

    /**
     * @param array $array
     * @param $key
     */
    public static function getIndexedBy(&$array, $valueKey, $indexKey, $multiple = false)
    {
        $results = array();
        if(!is_array($array)){
            return $results;
        }

        foreach ($array as $arrayValue) {
            $indexValue = self::getValue($arrayValue, $indexKey);
            $valueValue = self::getValue($arrayValue, $valueKey);

            if ($multiple) {
                if (!isset($results[$indexValue])) {
                    $results[$indexValue] = [];
                }
                $results[$indexValue][] = $valueValue;
            } else {
                $results[$indexValue] = $valueValue;
            }
        }

        return $results;
    }

    /**
     * @param array $array
     * @return array
     */
    public static function ungroup($array)
    {
        return call_user_func_array('array_merge', $array);
    }

    /**
     * @param mixed $data
     * @return array
     */
    public static function forceArray($data)
    {
        return is_array($data)
            ? $data
            : [$data];
    }

    /**
     * @param string $name
     * @return Muzyka_DataModel
     */
    public static function getModel($name)
    {
        $instance = self::getInstance();

        if (!isset($instance->modelCache[$name])) {
            $modelClass = 'Application_Model_' . ucfirst($name);
            if (!class_exists($modelClass)) {
                Throw new Exception(sprintf('Nie znaleziono klasy: %s', $modelClass), 500);
            }
            $instance->modelCache[$name] = new $modelClass;
        }

        return $instance->modelCache[$name];
    }

    /**
     * @param $template
     * @param array $data
     * @return string
     */
    public static function renderView($template, $data = [])
    {
        $view = clone Zend_Layout::getMvcInstance()->getView();
        $view->assign($data);
        return $view->render($template);
    }

    public static function isPlainArray($subject)
    {
        if (is_array($subject)) {
            foreach ($subject as $v) {
                if (!is_scalar($v)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public static function isIntKeyedArray($subject)
    {
        if (is_array($subject)) {
            foreach (array_keys($subject) as $k) {
                if ((string) $k !== (string) ((int) $k) || $k === '') {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public static function getValues($data, $key, &$results = array())
    {
        $keyArray = explode('.', $key);
        $currentKey = array_shift($keyArray);
        $nextKey = implode('.', $keyArray);

        if (self::isIntKeyedArray($data)) {
            foreach ($data as $row) {
                $search = self::getValue($row, $currentKey);

                if ($nextKey === '') {
                    if (is_array($search)) {
                        $results = array_merge($results, $search);
                    } elseif (!is_null($search)) {
                        $results[] = $search;
                    }
                    continue;
                }

                if (is_object($search) || is_array($search)) {
                    self::getValues($search, $nextKey, $results);
                }
            }
        } else {
            $search = self::getValue($data, $currentKey);

            if ($nextKey === '') {
                if (is_array($search)) {
                    $results = array_merge($results, $search);
                } elseif (!is_null($search)) {
                    $results[] = $search;
                }
            }

            if (is_object($search) || is_array($search)) {
                self::getValues($search, $nextKey, $results);
            }

        }

        return $results;
    }

    public static function getUniqueValues($data, $key)
    {
        return array_unique(self::getValues($data, $key));
    }

    public static function combineTable($data, $injections = [], $dataKey = false)
    {
        $result = [];

        if ($dataKey) {
            foreach ($data as $index => $value) {
                $row = $injections;

                if ($dataKey) {
                    $row[$dataKey] = $value;
                } else {
                    $row = array_merge($row, $value);
                }

                $result[] = $row;
            }
        }

        return $result;
    }

    public static function setValues($data, $key, $targetKey, $results, &$currentContainer = null)
    {
        $keyArray = explode('.', $key);
        $currentKey = array_shift($keyArray);
        $nextKey = implode('.', $keyArray);

        foreach ($data as &$row) {
            $search = self::getValue($row, $currentKey);

            if ($nextKey === '') {
                $result = null;

                if (is_array($search)) {
                    $result = array();
                    foreach ($search as $identifier) {
                        $result[$identifier];
                    }
                    $results = array_merge($results, $search);
                } elseif (!is_null($search)) {
                    $results[] = $search;
                }

                self::setValue($row, 'asdsadsa');
                continue;
            }

            if (is_object($search) || is_array($search)) {
                self::setValues($search, $nextKey, $targetKey, $results, $row);
            }
        }

        return $results;
    }

    public static function getValue($subject, $key)
    {
        $foundValue = null;
        $keyArray = explode('.', $key);
        $currentKey = array_shift($keyArray);

        if (is_object($subject)) {
            if (count($keyArray) > 0) {
                if (isset($subject->{$currentKey})) {
                    $foundValue = self::getValue($subject->{$currentKey}, implode('.', $keyArray));
                }
            } else {
                if (isset($subject->$currentKey)) {
                    $foundValue = $subject->$currentKey;
                }
            }
        } elseif (is_array($subject)) {
            if (count($keyArray) > 0) {
                if (isset($subject[$currentKey])) {
                    $foundValue = self::getValue($subject[$currentKey], implode('.', $keyArray));
                }
            } else {
                if (isset($subject[$currentKey])) {
                    $foundValue = $subject[$currentKey];
                }
            }
        } else {
            vd($subject);
            Throw new Exception('Unsupported subject', 500);
        }

        return $foundValue;
    }

    public static function setValue(&$subject, $key, $value)
    {
        $keyArray = explode('.', $key);
        $currentKey = array_shift($keyArray);

        if (is_object($subject)) {
            if (!isset($subject->{$currentKey})) {
                $subject->{$currentKey} = null;
            }

            if (count($keyArray) > 1) {
                self::setValue($subject->{$currentKey}, implode('.', $keyArray), $value);
            } else {
                $subject->{$currentKey} = $value;
            }
        } elseif (is_array($subject)) {
            if (!isset($subject[$currentKey])) {
                $subject[$currentKey] = null;
            }

            if (count($keyArray) > 1) {
                self::setValue($subject[$currentKey], implode('.', $keyArray), $value);
            } else {
                $subject[$currentKey] = $value;
            }
        } else {
            Throw new Exception('Unsupported subject', 500);
        }
    }

    public static function addValue($subject, $key, $value)
    {
        $keyArray = explode('.', $key);
        $currentKey = array_shift($keyArray);

        if (is_object($subject)) {
            if (!isset($subject->{$currentKey})) {
                $subject->{$currentKey} = [];
            }

            if (count($keyArray) > 1) {
                self::setValue($subject->{$currentKey}, implode('.', $keyArray), $value);
            } else {
                $subject->{$currentKey}[] = $value;
            }
        } elseif (is_array($subject)) {
            if (!isset($subject[$currentKey])) {
                $subject[$currentKey][] = [];
            }

            if (count($keyArray) > 1) {
                self::setValue($subject[$currentKey], implode('.', $keyArray), $value);
            } else {
                $subject[$currentKey][] = $value;
            }
        } else {
            Throw new Exception('Unsupported subject', 500);
        }
    }

    public static function arrayFindOne(&$array, $searchKey, $searchValue = null, $comparison = '=')
    {
        $results = self::arrayFind($array, $searchKey, $searchValue, $comparison);

        return !empty($results)
            ? $results[0]
            : null;
    }

    public static function arrayFind(&$array, $searchKey, $searchValue = null, $comparison = '=')
    {
        $results = [];
        $searchConfig = [];

        if (!is_array($searchKey)) {
            $searchConfig[] = array($searchKey, $searchValue);
        } else {
            $searchConfig = $searchKey;
        }

        foreach ($array as $item) {
            $doSearch = $searchConfig;
            $found = true;

            while ($currentSearch = array_shift($doSearch)) {
                list ($searchKey, $searchValue) = $currentSearch;

                switch ($comparison) {
                    case "=":
                        if (!self::equals(self::getValue($item, $searchKey), $searchValue)) {
                            $found = false;
                            break 2;
                        }
                        break;
                    case "!=":
                    case "<>":
                        if (self::equals(self::getValue($item, $searchKey), $searchValue)) {
                            $found = false;
                            break 2;
                        }
                        break;
                    case "inArray":
                        if (in_array(self::getValue($item, $searchKey), $searchValue)) {
                            $found = false;
                            break 2;
                        }
                        break;
                    default:
                        Throw new Exception('Unhandled comparison type', 500);
                }
            }

            if ($found) {
                $results[] = $item;
            }
        }

        return $results;
    }

    public static function keyExists(&$array, $searchKey)
    {
        if (!self::isIntKeyedArray($array)) {
            return self::arrayKeyExists($array, $searchKey);
        } else {
            foreach ($array as $item) {
                if (self::arrayKeyExists($item, $searchKey)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $array
     * @param $searchKey
     *
     * only for array or object, not for rowset
     */
    public static function arrayKeyExists(&$array, $searchKey)
    {
        if (is_array($array)) {
            return array_key_exists($searchKey, $array);
        }

        if ($array instanceof Application_Service_EntityRow) {
            return isset($array[$searchKey]);
        }
vdie($array, $searchKey);
        Throw new Exception('Error');
    }

    public static function getAppType()
    {
        return Zend_Registry::getInstance()->get('config')->production->app->type;
    }

    /**
     * @param string $compare
     * @return array|bool
     * @throws Zend_Exception
     */
    public static function getAppClass($compare = null)
    {
        $class = Zend_Registry::getInstance()->get('config')->production->app->class;

        if (!empty($class)) {
            $class = $class->toArray();
        } else {
            $class = [];
        }

        return null !== $compare
            ? in_array($compare, $class)
            : $class;
    }

    public static function getAppId()
    {
        return Zend_Registry::getInstance()->get('config')->production->app->id;
    }

    public static function apiCall($app, $method, $data = null, $type = 'json')
    {
        $apiBaseUrl = self::getAppUrl($app);

        return self::apiUrlCall($apiBaseUrl, $method, $data, $type);
    }

    public static function apiGlobalCall($appId, $method, $data = null, $type = 'json')
    {
        return self::apiUrlCall($appId . '.' . Zend_Registry::getInstance()->get('config')->production->global->apps_url_suffix, $method, $data, $type);
    }

    public static function apiUrlCall($host, $method, $data = null, $type = 'json')
    {
        $requestUrl = 'http://' . $host . '/' . $method;

        $context = ['http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
        ]];

        if ($data) {
            $context['http']['content'] = http_build_query($data);
        }

        vd('API call', $requestUrl, $context);

        $result = file_get_contents($requestUrl, false, stream_context_create($context));

        //vd('API result', $result);

        if ($type === 'json') {
            return json_decode($result, true);
        } else {
            return $result;
        }
    }

    public static function getAppUrl($app)
    {
        $baseUrls = [
            'hq_data' => '102.kryptos24.pl',
            'hq_notifications' => '102.kryptos24.pl',
        ];

        $spoof = Zend_Registry::getInstance()->get('config')->production->dev->spoof->app->{$app};

        if (empty($spoof) && !isset($baseUrls[$app])) {
            Throw new Exception('Unrecognized app', 500);
        } 

        return !empty($spoof) ? $spoof : $baseUrls[$app];
    }

    /**
     * used for hidden checkboxes filtering
     *
     * @param $data
     * @return array
     */
    public static function removeEmptyValues($data)
    {
        $result = [];

        foreach ($data as $k => $v) {
            if (!empty($v)) {
                $result[$k] = $v;
            }
        }

        return $result;
    }

    public static function equals($x, $y)
    {
        if (is_null($x) || is_null($y)
            || is_bool($x) || is_bool($y)
            || $x === '' || $y === ''
            || $x === 0 || $y === 0
            || $x === '0' || $y === '0'
            || is_array($x) || is_array($y)) {
            return $x === $y;
        }
        if (is_string($x) || is_string($y)) {
            return $x === $y || ($x == $y && strlen($x) === strlen($y));
        }

        return $x == $y;
    }

    public static function isNotEmpty($x)
    {
        if (is_null($x)
            || '' === $x
            || [] === $x
        ) {
            return false;
        }

        return true;
    }

    public static function requireKeys($array, $keys, $notNull = true, $throwException = false)
    {
        foreach ($keys as $key) {
            if (($notNull && !isset($array[$key])) || (!$notNull && !array_key_exists($key, $array))) {
                if ($throwException) {
                    Throw new Exception('No parameter: ' . $key, 500);
                }
                return false;
            }
        }

        return true;
    }

    public static function getDomElement($string)
    {
        $string = str_ireplace('< ', '&lt; ', $string);
        $string = preg_replace("/>\s+</", "><", $string);

        $docMetadata = new DOMDocument('1.0','UTF-8');
        $docMetadata->preserveWhiteSpace = false;
        $docMetadata->strictErrorChecking = false;
        $docMetadata->substituteEntities = false;
        $docMetadata->formatOutput = false;
        $docMetadata->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpathMetadata = new DOMXPath($docMetadata);

        $elementMetadata = $xpathMetadata->query('//div')->item(0);

        return $elementMetadata;
    }

    public static function hasEqualDefinition($base, $result)
    {
        $baseKeys = array_keys($base);
        $resultKeys = array_keys($result);
        $intersectKeys = array_intersect($baseKeys, $resultKeys);

        return $baseKeys === $intersectKeys;
    }

    public static function  prepareEntitiesForJson($data)
    {
        $results = [];
        if (is_array($data) || is_object($data))
        {
            foreach ($data as $k => $item) {
                if ($item instanceof Zend_Db_Table_Row_Abstract) {
                    $results[$k] = self::prepareEntitiesForJson($item->toArray());
                } elseif (is_array($item)) {
                    $results[$k] = self::prepareEntitiesForJson($item);
                } else {
                    $results[$k] = $item;
                }
            }
        }
        return $results;
    }

    public static function standarizeName($n, $separator = '-')
    {
        $a__ = array("Ę","Ó","Ą","Ś","Ł","Ż","Ź","Ć","Ń","ę","ó","ą","ś","ł","ż","ź","ć","ń","-","_"," ");
        $b__ = array("e","o","a","s","l","z","z","c","n","e","o","a","s","l","z","z","c","n",$separator,$separator,$separator);

        $n = mb_strtolower(str_replace($a__,$b__,$n));
        $n = preg_replace("/([^a-z0-9]){1}/",$separator.$separator.$separator,$n);
        $n = preg_replace("/$separator{2,}/",$separator,$n);
        $n = preg_replace("/^$separator/","",$n);
        $n = preg_replace("/$separator$/","",$n);
        return $n;
    }

    public static function getFileRealPath($uri)
    {
        return sprintf('%s/%s', ROOT_PATH, $uri);
    }

    public static function getFlashMessage($type, $text, $title = 'Wiadomość systemowa', $disappear = 10, $position = 'top right')
    {
        return sprintf('<div data-type="%s" data-disappear="%s" data-title="%s" data-position="%s">%s</div>', $type, $disappear, $title, $position, $text);
    }

    public static function fillParams($array, $data)
    {
        $result = [];

        foreach ($array as $k => $v) {
            if ($v[0] === ':') {
                $result[$k] = $data[substr($v, 1)];
            }
        }

        return $result;
    }

    public static function sprinta($pattern, $array, $separator = '')
    {
        $result = [];

        foreach ($array as $data) {
            if (!is_array($data)) {
                $data = [$data];
            }
            array_unshift($data, $pattern);

            $result[] = call_user_func_array('sprintf', $data);
        }

        return implode($separator, $result);
    }

    public static function stempl($pattern, $data)
    {
        preg_match_all('/\{([a-z0-9.-_]+)\}/i', $pattern, $matches);

        foreach ($matches[1] as $match) {
            if (array_key_exists($match, $data)) {
                $pattern = str_replace('{'.$match.'}', $data[$match], $pattern);
            }
        }

        return $pattern;
    }

    public static function getDocumentNumber($pattern, $dateString, $number = null)
    {
        $datestamp = strtotime($dateString);

        if ($number !== null) {
            $pattern = str_ireplace('[nr]', $number, $pattern);
        }
        $pattern = str_ireplace('[yyyy]', date('Y', $datestamp), $pattern);
        $pattern = str_ireplace('[mm]', date('m', $datestamp), $pattern);
        $pattern = str_ireplace('[dd]', date('d', $datestamp), $pattern);

        return $pattern;
    }
}