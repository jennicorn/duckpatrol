<?php

namespace Jennchen\Duckpatrol\Initializer;

use DirectoryIterator;
use ErrorException;

class Initializer
{
    private array $ignoreList;
    private array $filePathList = [];
    private string $root;

    /**
     * @param array $ignoreList
     * @param string $root
     */
    public function __construct(array $ignoreList, string $root)
    {
        $ignoreList['folder'][] = 'duckpatrol';
        $this->validateInputs($ignoreList, $root);
        $this->ignoreList = $ignoreList;
        $this->root = $this->defineRoot($root);
        $this->adjustIgnoreList();
        $this->dirRecursive($this->root);
        $this->modifyFiles();
    }

    /**
     * gathers php-file paths in project
     * excludes everything from ignoreList-array
     * @param string $dir
     * @return void
     */
    private function dirRecursive(string $dir)
    {
        try {
            $iter = new DirectoryIterator($dir);
            foreach ($iter as $item) {
                $absFilePath = $item->getPath() . '/' . $item->getFilename();
                if ($item != '.' && $item != '..' && $item != '.idea' && !is_int(array_search($absFilePath, $this->ignoreList['folder']))) {
                    if ($item->isDir()) {
                        $this->dirRecursive($absFilePath);
                    } else {
                        if (!is_int(array_search($absFilePath, $this->ignoreList['files'])) &&
                            is_int(strpos($item->getFilename(), ".php")))
                            $this->filePathList[] = $dir . "/" . $item->getFilename();
                    }
                }
            }
        } catch (\RuntimeException $exception) {
            $this->log('Could not find root directory. Please make sure, that either a root directory is specified or 
                a composer.json exists in the root directory. ' . $exception, true);
            exit();
        }
    }

    /**
     * when no custom root is specified, the folder with the composer.json-file will be
     * defined as root
     * @param string $root
     * @return string
     */
    private function defineRoot(string $root): string
    {
        $currentDir = realpath(__DIR__);
        $outermostDir = null;

        while ($currentDir && $currentDir !== dirname($currentDir)) {
            if (file_exists($currentDir . '/composer.json') && strpos($currentDir, 'duckpatrol') === false) {
                $outermostDir = $currentDir;
            }
            $currentDir = dirname($currentDir);
        }

        return $root ?: ($outermostDir ?: realpath(__DIR__));
    }


    /**
     * gets absolute paths for files in ignoreList
     * @return void
     */
    private function adjustIgnoreList()
    {
        $ignoreFolderPath = [];
        $ignoreFilePath = [];

        foreach ($this->ignoreList['folder'] as $folder) {
            if ($folder != '') $ignoreFolderPath[] = $this->root . '/' . $folder;
        }

        foreach ($this->ignoreList['files'] as $file) {
            if ($file != '') $ignoreFilePath[] = $this->root . '/' . $file;
        }

        $this->ignoreList['folder'] = $ignoreFolderPath;
        $this->ignoreList['files'] = $ignoreFilePath;
    }

    /**
     * adds required_once-statement in every php-file with a post-form
     * @return void
     */
    private function modifyFiles()
    {
        $locationOfInitFile = __DIR__ . '\\bootstrap.php';
        $fileChangeCount = 0;

        foreach ($this->filePathList as $filePath) {
            $fileContent = file_get_contents($filePath);
            $hasForm = preg_match('/<form\b[^>]*\bmethod=["\']?post["\']?[^>]*>/i', $fileContent) === 1;
            $hasCsrfProtection = strpos($fileContent, "<?php require_once '") !== false;
            $isClass = preg_match('/\bclass\s+\w+\b/', $fileContent);
            $needsCsrfProtection = $hasForm && !$hasCsrfProtection && !$isClass;

            if ($needsCsrfProtection) {
                try {
                    if (!file_exists($filePath)) {
                        throw new ErrorException($filePath . ': File not found.');
                    }

                    $relativePath = $this->getRelativePath(dirname($filePath), $locationOfInitFile);

                    $file = fopen($filePath, 'r+');
                    if ($file) {
                        $newContent = "<?php require_once '" . $relativePath . "';?> \n";
                        $oldContent = fread($file, filesize($filePath));
                        rewind($file);
                        if (fwrite($file, $newContent . $oldContent)) {
                            $this->log($filePath, false);
                            fclose($file);
                            $fileChangeCount++;
                        } else {
                            throw new ErrorException($filePath . ': File modifying failed.');
                        }
                    } else {
                        throw new ErrorException($filePath . ': File open failed.');
                    }
                } catch (\Exception $exception) {
                    $this->log('An error occurred while executing init_script.php: ' . $exception, true);
                    exit();
                }
            }
        }
//        $this->renameInitScript();
        print('Script executed successfully. ' . $fileChangeCount . ' files changed. Open log/initializer_script for further Information.');
    }

    /**
     * calculates relative Path for require_once path
     * @param $from
     * @param $to
     * @return string
     */
    private function getRelativePath($from, $to): string
    {
        $from = rtrim(str_replace('/', '\\', realpath($from)), '\\');
        $to = rtrim(str_replace('/', '\\', realpath($to)), '\\');

        $fromParts = explode('\\', $from);
        $toParts = explode('\\', $to);

        $commonLength = 0;
        $maxCommonLength = min(count($fromParts), count($toParts));
        while ($commonLength < $maxCommonLength && $fromParts[$commonLength] === $toParts[$commonLength]) {
            $commonLength++;
        }

        $numUp = count($fromParts) - $commonLength;
        $remainingToParts = array_slice($toParts, $commonLength);

        $relativeParts = array_merge(array_fill(0, $numUp, '..'), $remainingToParts);
        $relativePath = implode('\\', $relativeParts);

        return $relativePath;
    }


    /**
     * logges status-data to log/initializer_script
     * @param string $message
     * @param bool $error
     * @return void
     */
    private function log(string $message, bool $error)
    {
        $logPath = './log/initializer_script';
        $logName = 'log_' . date('dmY') . '.log';

        if ($error) {
            print('ERROR occurred. Open log/initializer_script for further Information.');
            $logMessage = date('d-m-Y H:i:s') . ' ERROR: ' . $message;
        } else {
            $logMessage = date('d-m-Y H:i:s') . ' INFO: Name-Protection added to: ' . $message;
        }

        if (!file_exists($logPath . '/' . $logName)) {
            touch($logPath . '/' . $logName);
            file_put_contents($logPath . '/' . $logName, $logMessage . "\n");
        } else {
            file_put_contents($logPath . '/' . $logName, $logMessage . "\n", FILE_APPEND);
        }
    }

    /**
     * renames init_script.php after execution for security reasons
     * @return void
     */
    private function renameInitScript()
    {
        $initScriptPath = './init_script.php';
        $initScriptPathNew = './init_script.php.disabled';
        rename($initScriptPath, $initScriptPathNew);
    }

    /**
     * validates structure and types of input
     * @param array $ignoreList
     * @param string $root
     * @return void
     */
    private function validateInputs(array $ignoreList, string $root)
    {
        $this->validateRoot($root);
        $this->validateIgnoreList($ignoreList);
    }

    private function validateRoot(string $root)
    {
        if (!empty($root)) {
            if (!is_dir($root) || $root !== strip_tags($root)) {
                $this->log('Root from config.php must be a valid directory path without HTML tags if specified.', true);
                exit();
            }
        }
    }

    private function validateIgnoreList(array $ignoreList)
    {
        $requiredKeys = ['folder', 'files'];
        foreach ($requiredKeys as $key) {
            if (!isset($ignoreList[$key]) || !is_array($ignoreList[$key])) {
                $this->log("ignoreList from config.php must contain a \"$key\" key with an array value.", true);
                exit();
            }
            foreach ($ignoreList[$key] as $item) {
                if (!is_string($item) || $item !== strip_tags($item)) {
                    $this->log("Each entry in ignoreList[\"$key\"] from config.php must be a string without HTML tags.", true);
                    exit();
                }
            }
        }
    }

}