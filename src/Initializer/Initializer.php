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
     * Constructor to initialize the Initializer class.
     *
     * @param array $ignoreList List of files and folders to ignore.
     * @param string $root The custom root directory for the project.
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
     * Gathers PHP file paths in the project.
     * Excludes items in the ignoreList array.
     *
     * @param string $dir The directory to scan.
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
     * Defines the root directory. If no custom root is specified, the folder with the composer.json file will be used.
     *
     * @param string $root The custom root directory.
     * @return string The determined root directory.
     */
    private function defineRoot(string $root): string
    {
        $currentDir = realpath(__DIR__);
        $outermostDir = null;

        // Traverse up the directory tree to find the outermost directory containing composer.json
        while ($currentDir && $currentDir !== dirname($currentDir)) {
            if (file_exists($currentDir . '/composer.json') && strpos($currentDir, 'vendor') === false) {
                $outermostDir = $currentDir;
            }
            $currentDir = dirname($currentDir);
        }

        // Return the custom root if specified, otherwise the outermost directory or the current directory
        return $root ?: ($outermostDir ?: realpath(__DIR__));
    }

    /**
     * Converts relative paths in the ignore list to absolute paths.
     *
     * @return void
     */
    private function adjustIgnoreList()
    {
        $ignoreFolderPath = [];
        $ignoreFilePath = [];

        // Convert folder paths to absolute paths
        foreach ($this->ignoreList['folder'] as $folder) {
            if ($folder != '') $ignoreFolderPath[] = $this->root . '/' . $folder;
        }

        // Convert file paths to absolute paths
        foreach ($this->ignoreList['files'] as $file) {
            if ($file != '') $ignoreFilePath[] = $this->root . '/' . $file;
        }

        $this->ignoreList['folder'] = $ignoreFolderPath;
        $this->ignoreList['files'] = $ignoreFilePath;
    }


    /**
     * Adds require_once statement in every PHP file with a POST form.
     *
     * @return void
     */
    private function modifyFiles()
    {
        $locationOfInitFile = __DIR__ . '\\bootstrap.php';
        $fileChangeCount = 0;

        foreach ($this->filePathList as $filePath) {
            $fileContent = file_get_contents($filePath);

            // Check if the file contains a POST form
            $hasForm = preg_match('/<form\b[^>]*\bmethod=["\']?post["\']?[^>]*>/i', $fileContent) === 1;

            // Check if the file already has a require_once statement for CSRF protection
            $hasCsrfProtection = strpos($fileContent, "<?php require_once '") !== false;

            // Check if the file contains a class definition
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
                        // Prepare the new content to be added
                        $newContent = "<?php require_once '" . $relativePath . "';?> \n";
                        $oldContent = fread($file, filesize($filePath));
                        rewind($file); // Move the file pointer to the beginning

                        // Write the new content followed by the old content
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
                    // Log any exceptions and exit
                    $this->log('An error occurred while executing init_script.php: ' . $exception, true);
                    exit();
                }
            }
        }
        $this->renameInitScript();
        print('Script executed successfully. ' . $fileChangeCount . ' files changed. Open log/initializer_script for further Information.');
    }


    /**
     * Calculates the relative path for require_once statement.
     *
     * @param string $from The source directory.
     * @param string $to The target file.
     * @return string The relative path from the source directory to the target file.
     */
    private function getRelativePath($from, $to): string
    {
        // Convert paths to absolute paths and replace forward slashes with backslashes
        $from = rtrim(str_replace('/', '\\', realpath($from)), '\\');
        $to = rtrim(str_replace('/', '\\', realpath($to)), '\\');

        // Split the paths into arrays of their components
        $fromParts = explode('\\', $from);
        $toParts = explode('\\', $to);

        // Find the common base path length
        $commonLength = 0;
        $maxCommonLength = min(count($fromParts), count($toParts));
        while ($commonLength < $maxCommonLength && $fromParts[$commonLength] === $toParts[$commonLength]) {
            $commonLength++;
        }

        // Calculate the number of directories to go up from the source to the common base path
        $numUp = count($fromParts) - $commonLength;

        // Get the remaining path components to reach the target from the common base path
        $remainingToParts = array_slice($toParts, $commonLength);

        // Combine '..' for each directory to go up with the remaining path components to the target
        $relativeParts = array_merge(array_fill(0, $numUp, '..'), $remainingToParts);

        // Join the parts into a single path string
        $relativePath = implode('\\', $relativeParts);

        return $relativePath;
    }


    /**
     * Logs status data to log/initializer_script.
     *
     * @param string $message The message to log.
     * @param bool $error Whether the message is an error message.
     * @return void
     */
    private function log(string $message, bool $error)
    {
        $logPath = './log/initializer_script';
        $logName = 'log_' . date('dmY') . '.log';

        // Format the log message based on whether it is an error
        if ($error) {
            print('ERROR occurred. Open log/initializer_script for further Information.');
            $logMessage = date('d-m-Y H:i:s') . ' ERROR: ' . $message;
        } else {
            $logMessage = date('d-m-Y H:i:s') . ' INFO: Name-Protection added to: ' . $message;
        }

        // Check if the log file exists, create it if it doesn't, then write the log message
        if (!file_exists($logPath . '/' . $logName)) {
            touch($logPath . '/' . $logName);
            file_put_contents($logPath . '/' . $logName, $logMessage . "\n");
        } else {
            file_put_contents($logPath . '/' . $logName, $logMessage . "\n", FILE_APPEND);
        }
    }


    /**
     * Renames init_script.php after execution for security reasons.
     *
     * @return void
     */
    private function renameInitScript()
    {
        $initScriptPath = './init_script.php';
        $initScriptPathNew = './init_script.php.disabled';

        // Rename the initial script to disable it
        rename($initScriptPath, $initScriptPathNew);
    }


    /**
     * Validates the structure and types of input parameters.
     *
     * @param array $ignoreList Input list of files and folders to ignore.
     * @param string $root The root custom directory input for the project.
     * @return void
     */
    private function validateInputs(array $ignoreList, string $root)
    {
        $this->validateRoot($root);
        $this->validateIgnoreList($ignoreList);
    }


    /**
     * Validates the root directory.
     *
     * @param string $root The custom root directory specified in the configuration.
     * @return void
     */
    private function validateRoot(string $root)
    {
        if (!empty($root)) {
            // Validate that the root is a directory and does not contain HTML tags for security reasons
            if (!is_dir($root) || $root !== strip_tags($root)) {
                $this->log('Root from config.php must be a valid directory path without HTML tags if specified.', true);
                exit();
            }
        }
    }


    /**
     * Validates the structure and content of the ignore list.
     *
     * @param array $ignoreList The ignore list containing folders and files to be excluded.
     * @return void
     */
    private function validateIgnoreList(array $ignoreList)
    {
        $requiredKeys = ['folder', 'files']; // Required keys in the ignore list

        foreach ($requiredKeys as $key) {
            if (!isset($ignoreList[$key]) || !is_array($ignoreList[$key])) {
                $this->log("ignoreList from config.php must contain a \"$key\" key with an array value.", true);
                exit();
            }

            foreach ($ignoreList[$key] as $item) {
                // Ensure each item is a string and does not contain HTML tags
                if (!is_string($item) || $item !== strip_tags($item)) {
                    $this->log("Each entry in ignoreList[\"$key\"] from config.php must be a string without HTML tags.", true);
                    exit();
                }
            }
        }
    }
}