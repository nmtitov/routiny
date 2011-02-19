#!/usr/bin/php
<?php

/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nikita@zencode.ru>
 */
/**
 * This script packs Routiny code from /lib directory into one file
 * should be used as pre-commit hook
 * 
 * mercurial example:
 * 
 * .hgrc
 * [hooks]
 * precommit.RunClang = php compress.php
 * 
 */

/**
 * Provides access only to development rt*.php files in /lib directory
 */
class RtDirectoryIterator extends FilterIterator {
    const WILDCARD = "/^rt(.*)\.php/i";

    public function __construct($path) {
        parent::__construct($path);
    }

    public function accept() { //FilterIterator method
        if (!$this->getInnerIterator()->isDot()
                && !$this->getInnerIterator()->isDir()) {
            return preg_match(self::WILDCARD, $this->getInnerIterator()->getFilename());
        }
    }

}

//check if directory with development files exists
$chunks_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib';
if (!is_dir($chunks_path)) {
    die("path $chunks_path doesn't exist\n");
}
//fetch code to the buffer
$dir_iter = new RtDirectoryIterator(new DirectoryIterator($chunks_path));
$php_tags = "/^(<\?php|<\?|<%)/i";
$buffer = "";
foreach ($dir_iter as $file_info) {
    $code = file_get_contents($file_info->getPath()
            . DIRECTORY_SEPARATOR . $file_info->getFilename());
    if (!empty($buffer)) {
        //remove php starting tags from files except first
        $code = preg_replace($php_tags, "", $code);
    }
    $buffer.=$code;
}
if (empty($buffer)) {
    die("Buffer is empty");
}
//add newline to the end of new file
$new_line = "\n";
if (!preg_match("/$new_line/", $buffer)) {
    $buffer.=$new_line;
}
//create /compressed path
$compressed_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'compressed';
if (!is_dir($compressed_path)) {
    if (!mkdir($compressed_path)) {
        die("Can't create /compressed directory\n");
    }
}
chmod($chunks_path, "0755");
//write buffer to new file
$compressed_filename = "rt.php";
$file = @fopen($compressed_path
                . DIRECTORY_SEPARATOR . $compressed_filename, "w");
if ($file) {
    if (fwrite($file, $buffer)) {
        echo "File $compressed_filename saved\nDone!!";
    } else {
        echo "Error while saving file $compressed_filename\nFiles were not commited!";
        die(1);
    }
} else {
    die("Can't open file $compressed_filename\nFiles were not commited!");
}

