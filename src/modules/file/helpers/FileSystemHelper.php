<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/19
 * Time: 11:18 AM
 */

namespace orangins\modules\file\helpers;

use FileList;
use PhutilNumber;
use orangins\modules\file\FilesystemException;
use Yii;
use Exception;

class FileSystemHelper extends \orangins\lib\OranginsObject
{

    /**
     * Read a file in a manner similar to file_get_contents(), but throw detailed
     * exceptions on failure.
     *
     * @param  string  File path to read. This file must exist and be readable,
     *                 or an exception will be thrown.
     * @return string  Contents of the specified file.
     *
     * @task   file
     * @throws FilesystemException
     */
    public static function readFile($path)
    {
        $path = self::resolvePath($path);

        self::assertExists($path);
        self::assertIsFile($path);
        self::assertReadable($path);

        $data = @file_get_contents($path);
        if ($data === false) {
            throw new FilesystemException(
                $path,
                \Yii::t("app", "Failed to read file '{0}'.", $path));
        }

        return $data;
    }


    /**
     * Write a file in a manner similar to file_put_contents(), but throw
     * detailed exceptions on failure. If the file already exists, it will be
     * overwritten.
     *
     * @param  string  File path to write. This file must be writable and its
     *                 parent directory must exist.
     * @param  string  Data to write.
     *
     * @task   file
     * @throws FilesystemException
     */
    public static function writeFile($path, $data)
    {
        self::assertWritableFile($path);

        if (@file_put_contents($path, $data) === false) {
            throw new FilesystemException(
                $path,
                \Yii::t("app", "Failed to write file '{0}' . ", [
                    $path
                ]));
        }
    }

    /**
     * Make assertions about the state of path in preparation for
     * writeFile() and writeFileIfChanged().
     * @param $path
     * @throws FilesystemException
     */
    private static function assertWritableFile($path)
    {
        $path = self::resolvePath($path);
        $dir = dirname($path);

        self::assertExists($dir);
        self::assertIsDirectory($dir);

        // File either needs to not exist and have a writable parent, or be
        // writable itself.
        $exists = true;
        try {
            self::assertNotExists($path);
            $exists = false;
        } catch (Exception $ex) {
            self::assertWritable($path);
        }

        if (!$exists) {
            self::assertWritable($dir);
        }
    }

    /**
     * Determine whether or not a path exists in the filesystem. This differs from
     * file_exists() in that it returns true for symlinks. This method does not
     * attempt to resolve paths before testing them.
     *
     * @param   string  Test for the existence of this path.
     * @return  bool    True if the path exists in the filesystem.
     * @task    path
     */
    public static function pathExists($path)
    {
        return file_exists($path) || is_link($path);
    }


    /**
     * Assert that something (e.g., a file, directory, or symlink) exists at a
     * specified location.
     *
     * @param  string    Assert that this path exists.
     * @return void
     *
     * @task   assert
     * @throws FilesystemException
     */
    public static function assertExists($path)
    {
        if (!self::pathExists($path)) {
            throw new FilesystemException(
                $path,
                \Yii::t("app", "File system entity '{0}' does not exist . ", [
                    $path
                ]));
        }
    }


    /**
     * Assert that nothing exists at a specified location.
     *
     * @param  string    Assert that this path does not exist.
     * @return void
     *
     * @task   assert
     * @throws FilesystemException
     */
    public static function assertNotExists($path)
    {
        if (file_exists($path) || is_link($path)) {
            throw new FilesystemException(
                $path,
                \Yii::t("app", "Path '{0}' already exists!", [
                    $path
                ]));
        }
    }


    /**
     * Assert that a path represents a file, strictly (i.e., not a directory).
     *
     * @param  string    Assert that this path is a file.
     * @return void
     *
     * @task   assert
     * @throws FilesystemException
     */
    public static function assertIsFile($path)
    {
        if (!is_file($path)) {
            throw new FilesystemException(
                $path,
                \Yii::t("app", "Requested path '{0}' is not a file . ", [
                    $path
                ]));
        }
    }


    /**
     * Assert that a path represents a directory, strictly (i.e., not a file).
     *
     * @param  string    Assert that this path is a directory.
     * @return void
     *
     * @task   assert
     * @throws FilesystemException
     */
    public static function assertIsDirectory($path)
    {
        if (!is_dir($path)) {
            throw new FilesystemException(
                $path,
                \Yii::t("app", "Requested path '{0}' is not a directory . ", [
                    $path
                ]));
        }
    }


    /**
     * Assert that a file or directory exists and is writable.
     *
     * @param  string    Assert that this path is writable.
     * @return void
     *
     * @task   assert
     * @throws FilesystemException
     */
    public static function assertWritable($path)
    {
        if (!is_writable($path)) {
            throw new FilesystemException(
                $path,
                \Yii::t("app", "Requested path '{0}' is not writable . ", [
                    $path
                ]));
        }
    }


    /**
     * Assert that a file or directory exists and is readable.
     *
     * @param  string    Assert that this path is readable.
     * @return void
     *
     * @task   assert
     * @throws FilesystemException
     */
    public static function assertReadable($path)
    {
        if (!is_readable($path)) {
            throw new FilesystemException(
                $path,
                \Yii::t("app", "Path '{0}' is not readable . ", [
                    $path
                ]));
        }
    }

    /**
     * Canonicalize a path by resolving it relative to some directory (by
     * default PWD), following parent symlinks and removing artifacts. If the
     * path is itself a symlink it is left unresolved.
     *
     * @param $path
     * @param null $relative_to
     * @return string    Canonical, absolute path.
     *
     * @task   path
     */
    public static function resolvePath($path, $relative_to = null)
    {
        $is_absolute = self::isAbsolutePath($path);

        if (!$is_absolute) {
            if (!$relative_to) {
                $relative_to = getcwd();
            }
            $path = $relative_to . DIRECTORY_SEPARATOR . $path;
        }

        if (is_link($path)) {
            $parent_realpath = realpath(dirname($path));
            if ($parent_realpath !== false) {
                return $parent_realpath . DIRECTORY_SEPARATOR . basename($path);
            }
        }

        $realpath = realpath($path);
        if ($realpath !== false) {
            return $realpath;
        }


        // This won't work if the file doesn't exist or is on an unreadable mount
        // or something crazy like that. Try to resolve a parent so we at least
        // cover the nonexistent file case.
        $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
        while (end($parts) !== false) {
            array_pop($parts);
            if (phutil_is_windows()) {
                $attempt = implode(DIRECTORY_SEPARATOR, $parts);
            } else {
                $attempt = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
            }
            $realpath = realpath($attempt);
            if ($realpath !== false) {
                $path = $realpath . substr($path, strlen($attempt));
                break;
            }
        }

        return $path;
    }

    /**
     * Checks if a path is specified as an absolute path.
     *
     * @param  string
     * @return bool
     */
    public static function isAbsolutePath($path)
    {
        if (phutil_is_windows()) {
            return (bool)preg_match('/^[A-Za-z]+:/', $path);
        } else {
            return !strncmp($path, DIRECTORY_SEPARATOR, 1);
        }
    }

    /**
     * List files in a directory.
     *
     * @param  string    Path, absolute or relative to PWD.
     * @param  bool      If false, exclude files beginning with a ".".
     *
     * @return array     List of files and directories in the specified
     *                   directory, excluding `.' and `..'.
     *
     * @task   directory
     * @throws FilesystemException
     */
    public static function listDirectory($path, $include_hidden = true)
    {
        $path = self::resolvePath($path);

        self::assertExists($path);
        self::assertIsDirectory($path);
        self::assertReadable($path);

        $list = @scandir($path);
        if ($list === false) {
            throw new FilesystemException(
                $path,
                \Yii::t("app", "Unable to list contents of directory '{0}'.", [
                    $path
                ]));
        }

        foreach ($list as $k => $v) {
            if ($v == '.' || $v == '..' || (!$include_hidden && $v[0] == '.')) {
                unset($list[$k]);
            }
        }

        return array_values($list);
    }

    /**
     * Create a temporary directory and return the path to it. You are
     * responsible for removing it (e.g., with Filesystem::remove())
     * when you are done with it.
     *
     * @param  string    Optional directory prefix.
     * @param  int       Permissions to create the directory with. By default,
     *                   these permissions are very restrictive (0700).
     * @param  string    Optional root directory. If not provided, the system
     *                   temporary directory (often "/tmp") will be used.
     * @return string    Path to newly created temporary directory.
     *
     * @task   directory
     * @throws FilesystemException
     */
    public static function createTemporaryDirectory(
        $prefix = '',
        $umask = 0700,
        $root_directory = null)
    {
        $prefix = preg_replace('/[^A-Z0-9._-]+/i', '', $prefix);

        if ($root_directory !== null) {
            $tmp = $root_directory;
            self::assertExists($tmp);
            self::assertIsDirectory($tmp);
            self::assertWritable($tmp);
        } else {
            $tmp = sys_get_temp_dir();
            if (!$tmp) {
                throw new FilesystemException(
                    $tmp,
                    Yii::t("app", 'Unable to determine system temporary directory.'));
            }
        }

        $base = $tmp . DIRECTORY_SEPARATOR . $prefix;

        $tries = 3;
        do {
            $dir = $base . substr(base_convert(md5(mt_rand()), 16, 36), 0, 16);
            try {
                self::createDirectory($dir, $umask);
                break;
            } catch (FilesystemException $ex) {
                // Ignore.
            }
        } while (--$tries);

        if (!$tries) {
            $df = disk_free_space($tmp);
            if ($df !== false && $df < 1024 * 1024) {
                throw new FilesystemException(
                    $dir,
                    Yii::t("app", 'Failed to create a temporary directory: the disk is full.'));
            }

            throw new FilesystemException(
                $dir,
                Yii::t("app", "Failed to create a temporary directory in '{0}'.", [
                    $tmp
                ]));
        }

        return $dir;
    }

    /**
     * Create a directory in a manner similar to mkdir(), but throw detailed
     * exceptions on failure.
     *
     * @param  string    Path to directory. The parent directory must exist and
     *                   be writable.
     * @param  int       Permission umask. Note that umask is in octal, so you
     *                   should specify it as, e.g., `0777', not `777'.
     * @param  boolean   Recursively create directories. Default to false.
     * @return string    Path to the created directory.
     *
     * @task   directory
     * @throws FilesystemException
     */
    public static function createDirectory(
        $path,
        $umask = 0755,
        $recursive = false)
    {

        $path = self::resolvePath($path);

        if (is_dir($path)) {
            if ($umask) {
                self::changePermissions($path, $umask);
            }
            return $path;
        }

        $dir = dirname($path);
        if ($recursive && !file_exists($dir)) {
            // Note: We could do this with the recursive third parameter of mkdir(),
            // but then we loose the helpful FilesystemExceptions we normally get.
            self::createDirectory($dir, $umask, true);
        }

        self::assertIsDirectory($dir);
        self::assertExists($dir);
        self::assertWritable($dir);
        self::assertNotExists($path);

        if (!mkdir($path, $umask)) {
            throw new FilesystemException(
                $path,
                Yii::t("app", "Failed to create directory '{0}'.", [
                    $path
                ]));
        }

        // Need to change permissions explicitly because mkdir does something
        // slightly different. mkdir(2) man page:
        // 'The parameter mode specifies the permissions to use. It is modified by
        // the process's umask in the usual way: the permissions of the created
        // directory are (mode & ~umask & 0777)."'
        if ($umask) {
            self::changePermissions($path, $umask);
        }

        return $path;
    }


    /**
     * Change the permissions of a file or directory.
     *
     * @param  string    Path to the file or directory.
     * @param  int       Permission umask. Note that umask is in octal, so you
     *                   should specify it as, e.g., `0777', not `777'.
     * @return void
     *
     * @task   file
     * @throws FilesystemException
     */
    public static function changePermissions($path, $umask)
    {
        $path = self::resolvePath($path);

        self::assertExists($path);

        if (!@chmod($path, $umask)) {
            $readable_umask = sprintf('%04o', $umask);
            throw new FilesystemException(
                $path,
                Yii::t("app", "Failed to chmod '{0}' to '{1}'.", [
                    $path, $readable_umask
                ]));
        }
    }


    /**
     * Remove a file or directory.
     *
     * @param  string    File to a path or directory to remove.
     * @return void
     *
     * @task   file
     * @throws Exception
     */
    public static function remove($path)
    {
        if (!strlen($path)) {
            // Avoid removing PWD.
            throw new Exception(
                Yii::t("app",
                    'No path provided to {0}.',
                    [
                        __FUNCTION__ . '()'
                    ]));
        }

        $path = self::resolvePath($path);

        if (!file_exists($path)) {
            return;
        }

        self::executeRemovePath($path);
    }


    /**
     * Internal. Recursively remove a file or an entire directory. Implements
     * the core function of @{method:remove} in a way that works on Windows.
     *
     * @param  string    File to a path or directory to remove.
     * @return void
     *
     * @task file
     * @throws FilesystemException
     */
    private static function executeRemovePath($path)
    {
        if (is_dir($path) && !is_link($path)) {
            foreach (self::listDirectory($path, true) as $child) {
                self::executeRemovePath($path . DIRECTORY_SEPARATOR . $child);
            }
            $ok = rmdir($path);
            if (!$ok) {
                throw new FilesystemException(
                    $path,
                    Yii::t("app", "Failed to remove directory '{0}'!", [
                        $path
                    ]));
            }
        } else {
            $ok = unlink($path);
            if (!$ok) {
                throw new FilesystemException(
                    $path,
                    Yii::t("app", "Failed to remove file '{0}'!", [
                        $path
                    ]));
            }
        }
    }

    /**
     * Locates the full path that an executable binary (like `git` or `svn`) is at
     * the configured `$PATH`.
     *
     * @param   string  Binary name, like `'git'` or `'svn'`.
     * @return  string  The full binary path if it is present, or null.
     * @task    exec
     * @throws \ReflectionException
     */
    public static function resolveBinary($binary)
    {
        if (phutil_is_windows()) {
            list($err, $stdout) = exec_manual('where {0}', $binary);
            $stdout = phutil_split_lines($stdout);

            // If `where {0}` could not find anything, check for relative binary
            if ($err) {
                $path = self::resolvePath($binary);
                if (self::pathExists($path)) {
                    return $path;
                }
                return null;
            }
            $stdout = head($stdout);
        } else {
            list($err, $stdout) = exec_manual('which %s', $binary);
        }

        return $err === 0 ? trim($stdout) : null;
    }

    /**
     * Read random alphanumeric characters from /dev/urandom or equivalent. This
     * method operates like @{method:readRandomBytes} but produces alphanumeric
     * output (a-z, 0-9) so it's appropriate for use in URIs and other contexts
     * where it needs to be human readable.
     *
     * @param   int     Number of characters to read.
     * @return  string  Random character string of the provided length.
     *
     * @task file
     * @throws Exception
     */
    public static function readRandomCharacters($number_of_characters)
    {

        // NOTE: To produce the character string, we generate a random byte string
        // of the same length, select the high 5 bits from each byte, and
        // map that to 32 alphanumeric characters. This could be improved (we
        // could improve entropy per character with base-62, and some entropy
        // sources might be less entropic if we discard the low bits) but for
        // reasonable cases where we have a good entropy source and are just
        // generating some kind of human-readable secret this should be more than
        // sufficient and is vastly simpler than trying to do bit fiddling.

        $map = array_merge(range('a', 'z'), range('2', '7'));

        $result = '';
        $bytes = self::readRandomBytes($number_of_characters);
        for ($ii = 0; $ii < $number_of_characters; $ii++) {
            $result .= $map[ord($bytes[$ii]) >> 3];
        }

        return $result;
    }


    /**
     * Read random bytes from /dev/urandom or equivalent. See also
     * @{method:readRandomCharacters}.
     *
     * @param   int     Number of bytes to read.
     * @return  string  Random bytestring of the provided length.
     *
     * @task file
     * @throws Exception
     */
    public static function readRandomBytes($number_of_bytes)
    {
        $number_of_bytes = (int)$number_of_bytes;
        if ($number_of_bytes < 1) {
            throw new Exception(\Yii::t("app", 'You must generate at least 1 byte of entropy.'));
        }

        // Try to use `openssl_random_pseudo_bytes()` if it's available. This source
        // is the most widely available source, and works on Windows/Linux/OSX/etc.

        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = true;
            $data = openssl_random_pseudo_bytes($number_of_bytes, $strong);

            if (!$strong) {
                // NOTE: This indicates we're using a weak random source. This is
                // probably OK, but maybe we should be more strict here.
            }

            if ($data === false) {
                throw new Exception(
                    \Yii::t("app",
                        '{0} failed to generate entropy!',
                        [
                            'openssl_random_pseudo_bytes()'
                        ]));
            }

            if (strlen($data) != $number_of_bytes) {
                throw new Exception(
                    \Yii::t("app",
                        '{0} returned an unexpected number of bytes (got {1}, expected {2})!',
                        'openssl_random_pseudo_bytes()',
                        [
                            new PhutilNumber(strlen($data)),
                            new PhutilNumber($number_of_bytes)
                        ]));
            }

            return $data;
        }


        // Try to use `/dev/urandom` if it's available. This is usually available
        // on non-Windows systems, but some PHP config (open_basedir) and chrooting
        // may limit our access to it.

        $urandom = @fopen('/dev/urandom', 'rb');
        if ($urandom) {
            $data = @fread($urandom, $number_of_bytes);
            @fclose($urandom);
            if (strlen($data) != $number_of_bytes) {
                throw new FilesystemException(
                    '/dev/urandom',
                    \Yii::t("app", 'Failed to read random bytes!'));
            }
            return $data;
        }

        // (We might be able to try to generate entropy here from a weaker source
        // if neither of the above sources panned out, see some discussion in
        // T4153.)

        // We've failed to find any valid entropy source. Try to fail in the most
        // useful way we can, based on the platform.

        if (phutil_is_windows()) {
            throw new Exception(
                \Yii::t("app",
                    '{0} requires the PHP OpenSSL extension to be installed and enabled ' .
                    'to access an entropy source. On Windows, this extension is usually ' .
                    'installed but not enabled by default. Enable it in your "s".',
                    [
                        __METHOD__ . '()',
                        'php.ini'
                    ]));
        }

        throw new Exception(
            \Yii::t("app",
                '{0} requires the PHP OpenSSL extension or access to "{1}". Install or ' .
                'enable the OpenSSL extension, or make sure "{2}" is accessible.',
                [
                    __METHOD__ . '()',
                    '/dev/urandom',
                    '/dev/urandom'
                ]));
    }

    /**
     * Determine if an executable binary (like `git` or `svn`) exists within
     * the configured `$PATH`.
     *
     * @param   string  Binary name, like `'git'` or `'svn'`.
     * @return  bool    True if the binary exists and is executable.
     * @task    exec
     * @throws \ReflectionException
     */
    public static function binaryExists($binary)
    {
        return self::resolveBinary($binary) !== null;
    }

    /**
     * Identify the MIME type of a file. This returns only the MIME type (like
     * text/plain), not the encoding (like charset=utf-8).
     *
     * @param string Path to the file to examine.
     * @param string Optional default mime type to return if the file's mime
     *               type can not be identified.
     * @return string File mime type.
     *
     * @task file
     *
     * @phutil-external-symbol function mime_content_type
     * @phutil-external-symbol function finfo_open
     * @phutil-external-symbol function finfo_file
     * @throws FilesystemException
     * @throws \ReflectionException
     */
    public static function getMimeType(
        $path,
        $default = 'application/octet-stream')
    {

        $path = self::resolvePath($path);

        self::assertExists($path);
        self::assertIsFile($path);
        self::assertReadable($path);

        $mime_type = null;

        // Fileinfo is the best approach since it doesn't rely on `file`, but
        // it isn't builtin for older versions of PHP.

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            if ($finfo) {
                $result = finfo_file($finfo, $path);
                if ($result !== false) {
                    $mime_type = $result;
                }
            }
        }

        // If we failed Fileinfo, try `file`. This works well but not all systems
        // have the binary.

        if ($mime_type === null) {
            list($err, $stdout) = exec_manual('file --brief --mime %s', $path);
            if (!$err) {
                $mime_type = trim($stdout);
            }
        }

        // If we didn't get anywhere, try the deprecated mime_content_type()
        // function.

        if ($mime_type === null) {
            if (function_exists('mime_content_type')) {
                $result = mime_content_type($path);
                if ($result !== false) {
                    $mime_type = $result;
                }
            }
        }

        // If we come back with an encoding, strip it off.
        if (strpos($mime_type, ';') !== false) {
            list($type, $encoding) = explode(';', $mime_type, 2);
            $mime_type = $type;
        }

        if ($mime_type === null) {
            $mime_type = $default;
        }

        return $mime_type;
    }

    /**
     * Return all directories between a path and the specified root directory
     * (defaulting to "/"). Iterating over them walks from the path to the root.
     *
     * @param  string        Path, absolute or relative to PWD.
     * @param  string        The root directory.
     * @return array<string>  List of parent paths, including the provided path.
     * @task   directory
     */
    public static function walkToRoot($path, $root = null)
    {
        $path = self::resolvePath($path);

        if (is_link($path)) {
            $path = realpath($path);
        }

        // NOTE: On Windows, paths start like "C:\", so "/" does not contain
        // every other path. We could possibly special case "/" to have the same
        // meaning on Windows that it does on Linux, but just special case the
        // common case for now. See PHI817.
        if ($root !== null) {
            $root = self::resolvePath($root);

            if (is_link($root)) {
                $root = realpath($root);
            }

            // NOTE: We don't use `isDescendant()` here because we don't want to
            // reject paths which don't exist on disk.
            $root_list = new FileList(array($root));
            if (!$root_list->contains($path)) {
                return array();
            }
        } else {
            if (phutil_is_windows()) {
                $root = null;
            } else {
                $root = '/';
            }
        }

        $walk = array();
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        foreach ($parts as $k => $part) {
            if (!strlen($part)) {
                unset($parts[$k]);
            }
        }

        while (true) {
            if (phutil_is_windows()) {
                $next = implode(DIRECTORY_SEPARATOR, $parts);
            } else {
                $next = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
            }

            $walk[] = $next;
            if ($next == $root) {
                break;
            }

            if (!$parts) {
                break;
            }

            array_pop($parts);
        }

        return $walk;
    }


    /**
     * Convert a canonical path to its most human-readable format. It is
     * guaranteed that you can use resolvePath() to restore a path to its
     * canonical format.
     *
     * @param  string    Path, absolute or relative to PWD.
     * @param  string    Optionally, working directory to make files readable
     *                   relative to.
     * @return string    Human-readable path.
     *
     * @task   path
     */
    public static function readablePath($path, $pwd = null) {
        if ($pwd === null) {
            $pwd = getcwd();
        }

        foreach (array($pwd, self::resolvePath($pwd)) as $parent) {
            $parent = rtrim($parent, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            $len = strlen($parent);
            if (!strncmp($parent, $path, $len)) {
                $path = substr($path, $len);
                return $path;
            }
        }

        return $path;
    }
}