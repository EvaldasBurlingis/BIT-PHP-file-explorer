<?php declare(strict_types = 1);

    $fake_db = [
        'username' => 'user',
        'password' => password_hash('secret', PASSWORD_BCRYPT),
        'access_token' => '$2y$10$HP1RbNBxdXDoSDIxiJSzheQ1oJk5pNVpiwBfu.bJzzYeupsACpGXm'
    ];

    $fm = new FileManager();
    $cwd = getcwd();

    $path = isset($_POST['path']) ? $_POST['path'] : '';

    /**
     * Login
     */
    if (isset($_POST) && $_POST['action'] === 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $response;

        if ($username === $fake_db['username'] && password_verify($password, $fake_db['password'])) {

            $response = [
                'success' => true,
                'data' => [
                    'authToken' => $fake_db['access_token']
                ]
            ];

        } else {
            $response = ['success' => false];
            $_SESSION['user'] = 0;
        }

        echo json_encode($response);
    } 

    /**
     * Logout
     */
    if (isset($_POST) && $_POST['action'] === 'logout') {
    } 

    // on each request validate auth token
    if(isset($_POST['authToken']) && $_POST['authToken'] === $fake_db['access_token']) {

        $path === '/' ? $fm->setPath($cwd) : $fm->setPath(($cwd . $path));
        $fm->setAction($_POST['action']);

        if (isset($_POST) && $_POST['action'] === 'mkdir') $fm->createNewDir($_POST['new_folder_name']);
        if (isset($_POST) && $_POST['action'] === 'unlink') $fm->deleteFile($_POST['filename']);
        if (isset($_POST) && $_POST['action'] === 'download') $fm->downloadFile($_POST['filename']);
        if (isset($_POST) && $_POST['action'] === 'upload') {            
            if (isset($_FILES['file'])) $fm->uploadFile();
            
        }

        echo $fm->getJsonResponse();
    }


    /**
     * Catch all error
    */

    if (
        isset($_POST) && 
        $_POST['action'] !== 'mkdir' && 
        $_POST['action'] !== 'cd' &&
        $_POST['action'] !== 'unlink' &&
        $_POST['action'] !== 'download' &&
        $_POST['action'] !== 'upload' &&
        $_POST['action'] !== 'login' &&
        $_POST['action'] !== 'logout'
    ) {
        $data = [ 
            'success' => false,
            'error' => 'Bad Request'
        ];
    
        echo json_encode($data);
    }

    class FileManager
    {
        
        private string $path;
        private string $action;

        /**
         * Return current path
         * 
         * @return string
         */
        private function getPath() : string
        {
            return $this->path;
        }

        /**
         * Set current path
         * 
         * @return self
         */
        public function setPath(string $path) : self
        {
            $this->path = $path;

            return $this;
        }


        /**
         * Get active action
         * 
         * @return string
         */
        private function getAction() : string
        {
            return $this->action;
        }

        /**
         * Set active action
         * 
         * @return self
         */
        public function setAction(string $action) : self
        {
            $this->action = $action;

            return $this;
        }


        /**
         * Get all files and directories that are
         * stored inside given path on webserver
         * 
         * @return array
         */
        private function getFilesAndFolders(string $path) : array
        {

            return array_values(array_diff(scandir($path), array('..', '.')));    
        }


        /**
         * Get folders that are inside given path
         * 
         * @return array
         */
        private function getFolders() : array
        {   

            $files_and_folders = $this->getFilesAndFolders($this->path);  
            $folders = [];

            if($this->path === getcwd()) {

                foreach ($files_and_folders as $fnd) {
                    if (is_dir($fnd)) array_push($folders, $fnd);
                }

            } else {

                foreach ($files_and_folders as $fnd) {
                    if (is_dir(($this->path . $fnd))) array_push($folders, $fnd);
                }
            }

            return $folders;
        }


        /**
         * Get files that are inside given path
         * 
         * @return array
         */
        private function getFiles() : array
        {   

            $files_and_folders = $this->getFilesAndFolders($this->path);
            $files = [];

            if($this->path === getcwd()) {

                foreach ($files_and_folders as $fnd) {
                    if (is_file($fnd)) array_push($files, $fnd);
                }

            } else {
                
                foreach ($files_and_folders as $fnd) {
                    if (is_file(($this->path . $fnd))) array_push($files, $fnd);
                }
            }

            return $files;
        }

        /**
         * Create new directory
         *
         * @return void
        */
        public function createNewDir(string $new_folder_name) : void
        {
            $new_dir_path = $this->path . '/' . $new_folder_name;

            mkdir($new_dir_path);
        }

        /**
         * Create new directory
         *
         * @return void
        */
        public function deleteFile(string $filename) : void
        {
            $file_path = $this->path . '/' . $filename;

            unlink($file_path);
        }

        /**
         * Download file
         *
         * @return void
        */
        public function downloadFile(string $filename) : void
        {
            $file_path = $this->path . '/' . $filename;

            if (file_exists($file_path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path));
                readfile($file_path);
                exit;
            }
        }

        /**
         * Upload file
         *
         * @return void
        */
        public function uploadFile() : void
        {
            $filepath = $this->getPath() . '/' . $_FILES['file']['name'];


            if (!file_exists($filepath)) {
                move_uploaded_file($_FILES['file']['tmp_name'], $filepath);
            }
        }

        /**
         * JSON response to api call
         * 
         * @return string
         */
        public function getJsonResponse() : string
        {
            // Response to cd operation
            $response = [
                'success' => true,
                'data' => [
                    'path' => $this->getPath(),
                    'folders' => $this->getFolders(),
                    'files' => $this->getFiles()
                ]
            ];

            return json_encode($response);
        }
    }
