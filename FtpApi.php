<?php
/**
* FtpApi is a sample class for local machine / Server File Transfer
*
* Example usage:
* try{
*     $remoteFtpHost = 'FTP-Server';
*     $remoteFtpUsername = 'FTP-Username';
*     $remoteFtpPassword = 'FTP-Password';
* 
*     $ftp = new FtpApi($remoteFtpHost);
*     $ftp->login($remoteFtpUsername, $remoteFtpPassword);
*     $ftp->localUploadPath = '/path/';
*     $ftp->remoteUploadPath = '/path/';
*     // $ftp->localDownloadPath = '/path/';
*     // $ftp->remoteDownloadPath = '/path/';
*
*     $ftp->fileType = 'csv';
*
*     if($transferSuccess = $ftp->sendLocalCsv()) $log->lwrite('File transfer successful');
* }
*     catch (Exception $e){
*     // $log->lwrite($e->getMessage());
* }
*
* @package  Example
* @author   Sascha Bröning <sascha.broening@gmail.com>
* @version  1.0
* @access   public
*/

class FtpApi {

    /**
     * stores the current connection
     * @var object
     */
    private $connection;
    /**
     * stores values given during initialization (e.g. filetype)
     * @var array
     */
    private $data;

    /**
     * Constructor
     * 
     * @param server  $server  server address
     * @param integer $port    port to connect
     * @param integer $timeout timeout
     * @throws Exception if connection to server failed
     */
    public function __construct($server, $port = 21, $timeout = 90)
    {
        $this->connection = ftp_connect($server, $port, $timeout);

        if (!$this->connection)
            throw new Exception('Cant establish connection to ' . $server . ' on Port ' . $port . ')');
    }

    /**
     * Get value from local value-array
     * 
     * @param  string $varName variable name to search for
     * @throws Exception if variable doesnt exist
     * @return string variable value
     */
    public function __get($varName)
    {
        if (!array_key_exists($varName, $this->data)){
            throw new Exception('Variable doesnt exist: ' . $varName);
        }
        else return $this->data[$varName];
    }
    
    /**
     * Store given value in local value-array
     * 
     * @param string $varName given variable name
     * @param string $value   given variable value
     */
    public function __set($varName, $value)
    {
        $this->data[$varName] = $value;
    }

    /**
     * Remote FTP Login Method
     * 
     * @param string $username for login purpose
     * @param string $password for login purpose
     * @throws Exception if login failed
     * @todo check server off-/online or credentials invalid
     */
    public function login($username, $password)
    {
        if (!@ftp_login($this->connection, $username, $password))
            throw new Exception('Login failed. Given username/password are inavlid.');
    }

    /**
     * List all files within given local upload path
     * 
     * @return array list of local files
     */
    public function scanLocalUploadDirectory() 
    { 
        return @scandir($_SERVER['DOCUMENT_ROOT'] . $this->localUploadPath); 
    }

    /**
     * List all files within remote download path
     * 
     * @return array list of remote files
     */
    public function scanRemoteDownloadDirectory() 
    { 
        return ftp_nlist($this->connection, $this->remoteDownloadPath); 
    }

    /**
     * Transfer file to remote server and delete local files
     *
     * @todo if local directory empty throw another exception message
     * @todo specify exception message if transfer failed
     * @todo implement switch for rename/delete/ignore transfered files
     * @return bool true|false
     */
    public function sendLocalCsv()
    {
        if (!$localFilesInFolder = $this->scanLocalUploadDirectory())
            throw new Exception('Cant access local files in given directory path');

        foreach ($localFilesInFolder as $file){
            $path_parts = pathinfo($file);
            if (isset($path_parts['extension']) && $path_parts['extension'] == $this->fileType){ 
                if (@ftp_put($this->connection, $this->remoteUploadPath . $file, $_SERVER['DOCUMENT_ROOT'] . $this->localUploadPath . $file, FTP_BINARY)){
                    unlink($_SERVER['DOCUMENT_ROOT'] . $this->localUploadPath . $file);
                    return true;
                } else { throw new Exception('Filetransfer to remote path failed'); }
            }
        }

        return false;
    }

    /**
     * Transfer remote file(s) to local directory and rename remote file to .bak (backup)
     *
     * @todo implement switch for rename/delete/ignore transfered files
     * @return bool true|false
     */
    public function getRemoteCsv()
    {
        if (!$remoteFilesInFolder = $this->scanRemoteDownloadDirectory())
            throw new Exception('Cant access directory content on remote server');

        foreach ($remoteFilesInFolder as $file) { 
            $path_parts = pathinfo($file);
            if (isset($path_parts['extension']) && $path_parts['extension'] == $this->fileType){
                if (@ftp_get($this->connection, $_SERVER['DOCUMENT_ROOT'] . $this->localDownloadPath . $file, $this->remoteDownloadPath . $file, FTP_BINARY)){
                    ftp_rename($this->connection, $this->remoteDownloadPath . $file, $this->remoteDownloadPath . $file . '.bak');
                    return true;
                } else { throw new Exception('Filetransfer to local path failed'); }
            }
        }

        return false;
    }

}