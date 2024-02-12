<?php
// TNImage v1.0
$password = "PleaseChangeMe"; // Change this to your desired password
$image = "image.png";

// Replace image.png with whatever you want.

$mainDirectory = realpath(dirname(__FILE__));
$scriptDirectory = realpath($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME']);

// Check if the password is correct
if (isset($_GET['password']) && $_GET['password'] === $password) {
    // Password is correct, show directory listing
    $currentDirectory = $mainDirectory;
    if (isset($_GET['directory'])) {
        $directory = $_GET['directory'];
        $currentDirectory .= "/$directory";
    } else {
        $directory = "";
    }

    // Check if the requested directory is within the main operating directory
    if (strpos($currentDirectory, $mainDirectory) !== 0 ) {
        echo "Access Denied!";
        exit;
    } else if(isset($_GET["directory"]) && strstr($_GET["directory"], "../")){
        die("Unauthorized access. Terminating listing.");
    }

    if(isset($_GET["file"])) {
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
    }

    $files = scandir($currentDirectory);
    if(isset($_GET["directory"]) && ($_GET["directory"] !== "")){
        $upDirectory = dirname($_GET["directory"]);
        // Ensure the root directory is not exceeded
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
                // Directories go first
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
                echo "<a href='?file=$file&password=$password&directory=$directory' target='_blank'>$file (Size: $size bytes, Hash: $hash)</a><br>";
            }
        }
    }
} else {
    // Serve default image if no file or password provided
    header("Content-Type: ".mime_content_type($image));
    echo file_get_contents($image);
}
?>
