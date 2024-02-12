<?php
    $password = "PleaseChangeMe"; // Change this to your desired password
    $image = "image.png";
    $mainDirectory = realpath(dirname(__FILE__));

    if (isset($_GET['password']) && $_GET['password'] === $password && !($_SERVER["REQUEST_METHOD"] == "POST")) {
        // Password is correct and it's not a POST request
        // Viewport
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $currentDirectory = $mainDirectory;
        if (isset($_GET['directory'])) {
            $directory = $_GET['directory'];
            $currentDirectory .= "/$directory";
        } else {
            $directory = "";
        }

        if (strpos($currentDirectory, $mainDirectory) !== 0) {
            echo "Access Denied!";
            exit;
        } else if(isset($_GET["directory"]) && strstr($_GET["directory"], "../")){
            die("Unauthorized access. Terminating listing.");
        }

        if(isset($_GET["file"]) && !isset($_GET["delete"])) {
            if(isset($_GET["directory"]) && $_GET["directory"] === "" || !isset($_GET["directory"])){
                if($_GET["file"] === "index.php"){
                    die("Blocking access to main script. <a href='?password=".$_GET["password"]."'>Go back</a>");
                }
            }
            $filename = $_GET["file"];
            $filePath = $mainDirectory . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $filename;

            if(file_exists($filePath) && strpos($filePath, $mainDirectory) === 0) {
                header("Content-Type: application/octet-stream");
                header("Content-Disposition: attachment; filename=\"$filename\"");
                echo file_get_contents($filePath);
                exit;
            } else {
                die("File does not exist or unauthorized access.");
            }
        } elseif (isset($_GET["file"]) && isset($_GET["delete"]) && isset($_GET["directory"]) && !isset($_GET["confirm"])){
            $filename = $_GET["file"];
            $filePath = $mainDirectory . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $filename;
            if(file_exists($filePath)){
                echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
                echo "<p>Do you really want to delete '$filename'?</p>";
                echo "<p><a href='?password={$_GET["password"]}&directory={$_GET["directory"]}'>No</a> <a href='?delete=true&password={$_GET["password"]}&directory={$_GET["directory"]}&file={$_GET["file"]}&confirm=true'>Yes</a></p>";
            } else {
                echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
                echo "<h1>404</h1>";
                echo "<p>Could not delete that file because it doesn't exist!</p>";
            }
        } elseif (isset($_GET["file"]) && isset($_GET["delete"]) && isset($_GET["directory"]) && isset($_GET["confirm"])) {
            $filename = $_GET["file"];
            $filePath = $mainDirectory . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $filename;
            
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
            if(unlink($filePath)){
                echo "<p>Successfully deleted file '$filename'. <a href='?password={$_GET["password"]}&directory={$_GET["directory"]}'>Go back</a></p>";
            } else {
                echo "<p>Failed to delete file '$filename'. <a href='?password={$_GET["password"]}&directory={$_GET["directory"]}'>Go back</a></p>";
            }
            
        } else {
            $files = scandir($currentDirectory);
            if(isset($_GET["directory"]) && ($_GET["directory"] !== "")){
                $upDirectory = dirname($_GET["directory"]);
                if ($upDirectory !== '.') {
                    if($upDirectory === "\\"){
                        $upDirectory = "";
                    }
                    echo "<p><a href='?password=$password&directory=$upDirectory'>../</a></p>";
                }
            }
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $path = $currentDirectory . "/" . $file;
                    if (is_dir($path)) {
                        echo "<a href='?password=$password&directory=$directory/$file'>$file/ (folder)</a><br>";
                    }
                }
            }
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $path = $currentDirectory . "/" . $file;
                    if (is_dir($path)) {
                        // It's a directory
                    } else {
                        $size = filesize($path);
                        $hash = md5_file($path);
                        echo "<a href='?file=$file&password=$password&directory=$directory' target='_blank'>$file (Size: $size bytes, Hash: $hash)</a> [<a href='?delete=true&file=$file&password=$password&directory=$directory'>Delete</a>]<br>";
                    }
                }
            }
            echo "<p><form action='".htmlentities($_SERVER["PHP_SELF"])."' method='POST' enctype='multipart/form-data'><input type='file' name='upload'><input type='hidden' name='directory' value='$directory'><input type='hidden' name='password' value='$password'><input type='submit' value='Upload file' name='submit'></form></p>";
            echo "<p><form action='".htmlentities($_SERVER["PHP_SELF"])."' method='POST' enctype='multipart/form-data'><input type='hidden' name='directory' value='$directory'><input type='hidden' name='password' value='$password'><label for='mkd'>Create directory: </label><input type='text' name='mkd' maxlength='64'> <input type='submit' value='Create folder' name='createfolder'></form></p>";
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["directory"]) && isset($_POST["password"]) && isset($_FILES["upload"]) && $_FILES["upload"]["error"] == 0 && $_POST["password"] === $password) {
        $baseDirectory = realpath(dirname(__FILE__)); // Get the absolute path of the base directory
        $directory = $_POST["directory"]; // Get the directory provided via POST
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';

        // Construct the full path of the upload directory
        $uploadDir = realpath($baseDirectory . DIRECTORY_SEPARATOR . $directory);

        $uploadedFile = $uploadDir . DIRECTORY_SEPARATOR . basename($_FILES["upload"]["name"]);
        $fileType = pathinfo($uploadedFile, PATHINFO_EXTENSION);
        if (strtolower($fileType) !== "php" && strtolower($fileType) !== "html") {
            if (move_uploaded_file($_FILES["upload"]["tmp_name"], $uploadedFile)) {
                echo "<p>File has been successfully uploaded <a href='?password=".$_POST["password"]."&directory=".$_POST["directory"]."'>Go back to directory</a></p>";
            } else {
                echo "Error uploading file.";
            }
        } else {
            echo "Action not allowed. <a href='?password=".$_POST["password"]."&directory=".$_POST["directory"]."'>Go back to directory</a>";
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["directory"]) && isset($_POST["password"]) && $_POST["password"] === $password && isset($_POST["mkd"])) {
        // Make directory
        $baseDirectory = realpath(dirname(__FILE__)); // Get the absolute path of the base directory
        $directory = $_POST["directory"]; // Get the directory provided via POST
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';

        // Construct the full path of the upload directory
        $uploadDir = realpath($baseDirectory . DIRECTORY_SEPARATOR . $directory);
        if(mkdir($uploadDir . DIRECTORY_SEPARATOR . $_POST["mkd"] . DIRECTORY_SEPARATOR, 0777)){
            echo "<p>Directory created. Click <a href='?directory=$directory&password=".$_POST["password"]."'>here</a> to go back to parent directory</p>";
        }

    } else {
        // Serve default image if no file or password provided
        header("Content-Type: text/plain");
        echo file_get_contents($image);
    }
    ?>
