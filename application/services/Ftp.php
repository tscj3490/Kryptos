<?php

class Application_Service_Ftp
{
    /** @var self[] */
    protected static $_instances = [];

    private function __clone() {}
    public static function getInstance($host, $port, $user, $pass, $timeout = 10) {
        $mask = sprintf('%s:%s|%s@%s|%s', $host, $port, $user, $pass, $timeout);

        if (!isset(self::$_instances[$mask])) {
            self::$_instances[$mask] = new self($host, $port, $user, $pass, $timeout);
        }

        return self::$_instances[$mask];
    }


    protected $resource;
    protected $cache = [];

    private function __construct($host, $port = 21, $user, $pass, $timeout = 10)
    {
        $this->resource = ftp_connect($host, $port, $timeout);
        $login_result = ftp_login($this->resource, $user, $pass);

        if ((!$this->resource) || (!$login_result)) {
            Throw new Exception('FTP login error on '. $host, 500);
        }
        ftp_pasv($this->resource, true);
    }

    function upload($destination, $file, $replace = false)
    {
        $destinationArray = explode('/', $destination);
        $destinationFile = array_pop($destinationArray);
        $destinationFileArray = explode('.', $destinationFile);
        $destinationFileName = implode('. ', array_slice($destinationFileArray, 0, -1));
        $destinationFileExt = array_pop($destinationFileArray);
        $destinationFileUriFinal = implode('/', $destinationArray) . '/' . $destinationFileName . '.' . $destinationFileExt;

        foreach (array_keys($destinationArray) as $currentDirIndex) {
            $this->ftp_create_dir(implode('/', array_slice($destinationArray, 0, $currentDirIndex + 1)));
        }

        if (!$replace) {
            $counter = 1;
            $loopGuard = 0;
            while ($this->exists($destinationFileUriFinal)) {
                if (++$loopGuard > 10) {
                    Throw new Exception('Upload rename loopGuard error', 500);
                }

                $destinationFileUriFinal = sprintf('%s/%s_(%d).%s', implode('/', $destinationArray), $destinationFileName, $counter, $destinationFileExt);
                $counter++;
            }
        }

        if (is_resource($file)) {
            $status = ftp_fput($this->resource, $destinationFileUriFinal, $file, FTP_BINARY);
        } else {
            $status = ftp_put($this->resource, $destinationFileUriFinal, $file, FTP_BINARY);
        }

        if (false === $status) {
            Throw new Exception('Ftp error: cant send file '. $destinationFileUriFinal);
        }

        return $destinationFileUriFinal;
    }

    public function download($file)
    {
        $tempHandle = fopen('php://temp', 'r+');

        ftp_fget($this->resource, $tempHandle, $file, FTP_BINARY, 0);

        $fstats = fstat($tempHandle);
        fseek($tempHandle, 0);
        $result = fread($tempHandle, $fstats['size']);

        fclose($tempHandle);

        return $result;
    }

    function ftp_create_dir($dir)
    {
        if (!$this->exists($dir)) {
            $status = ftp_mkdir($this->resource, $dir);
            if (false === $status) {
                Throw new Exception('Ftp error: cant create directory '. $dir);
            }

            $this->cache['exists'][$dir] = true;
        }
    }

    function exists($fileOrDir)
    {
        $fileOrDir = $this->clear_uri($fileOrDir);
        if (isset($this->cache['exists'][$fileOrDir])) {
            return $this->cache['exists'][$fileOrDir];
        }

        $path = $this->getElementParentsPathFromPath($fileOrDir);
        $element = $this->getElementFromPath($fileOrDir);
        $status = false;

        $contents = ftp_nlist($this->resource, $path);
        foreach ($contents as $file) {
            $targetElement = $this->getElementFromPath($file);

            if ($targetElement === $element) {
                $status = true;
            }
        }

        $this->cache['exists'][$fileOrDir] = $status;
        return $status;
    }

    function getElementFromPath($fileOrDir)
    {
        $dirArray = explode('/', $fileOrDir);

        try {
            do {
                $element = array_pop($dirArray);
            } while (empty($element) && !empty($dirArray));

            if (empty($element)) {
                Throw new Exception('Empty path', 500);
            }
        } catch (Exception $e) {
            vdie($e);
            Throw $e;
        }

        return $this->clear_uri($element);
    }

    function getElementParentsPathFromPath($fileOrDir)
    {
        $dirArray = explode('/', $fileOrDir);

        try {
            do {
                $element = array_pop($dirArray);
            } while (empty($element) && !empty($dirArray));

            if (empty($element)) {
                Throw new Exception('Empty path', 500);
            }
        } catch (Exception $e) {
            vdie($e);
            Throw $e;
        }

        return $this->clear_uri(implode('/', $dirArray));
    }

    function clear_uri($uri)
    {
        if ('./' === substr($uri, 0, 2)) {
            $uri = substr($uri, 2);
        }
        if ('/' === $uri[0]) {
            $uri = substr($uri, 1);
        }
        $uri = str_replace('//', '/', $uri);
        $uri = str_replace('\\\\', '\\', $uri);
        return $uri;
    }

    /**
     * @inheritDoc
     */
    function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
    }
}