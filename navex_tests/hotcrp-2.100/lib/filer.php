<?php
// filer.php -- generic document helper class
// HotCRP is Copyright (c) 2006-2016 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

class ZipDocument_File {
    public $filename;
    public $filestore;
    public $sha1;
    function __construct($filename, $filestore, $sha1) {
        $this->filename = $filename;
        $this->filestore = $filestore;
        $this->sha1 = $sha1;
    }
}

class ZipDocument {
    private $tmpdir_ = null;
    private $files;
    public $warnings;
    private $recurse;
    private $downloadname;
    private $mimetype;
    private $headers;
    private $start_time;
    private $filestore;

    function __construct($downloadname, $mimetype = "application/zip") {
        $this->downloadname = $downloadname;
        $this->mimetype = $mimetype;
        $this->clean();
    }

    function clean() {
        $this->tmpdir_ = null;
        $this->files = array();
        $this->warnings = array();
        $this->recurse = false;
        $this->headers = false;
        $this->start_time = time();
        $this->filestore = array();
    }

    private function tmpdir() {
        if ($this->tmpdir_ === null
            && ($this->tmpdir_ = tempdir()) === false)
            $this->warnings[] = "Could not create temporary directory.";
        return $this->tmpdir_;
    }

    private function _add($doc, $filename, $check_filename) {
        // maybe this is a warning container
        if (is_object($doc) && isset($doc->error) && $doc->error) {
            $this->warnings[] = (isset($doc->filename) ? $doc->filename . ": " : "")
                . (isset($doc->error_html) ? htmlspecialchars_decode($doc->error_html) : "Unknown error.");
            return;
        }

        // check filename
        if (!$filename && is_object($doc) && isset($doc->filename))
            $filename = $doc->filename;
        if (!$filename
            || ($check_filename
                && !preg_match(',\A[^.*/\s\000-\017\\\\\'"][^*/\000-\017\\\\\'"]*\z,', $filename))) {
            $this->warnings[] = "$filename: Bad filename.";
            return false;
        }

        // load document
        if (is_string($doc))
            $doc = (object) array("content" => $doc);
        if (!isset($doc->filestore) && !isset($doc->content)) {
            if ($doc->docclass && $doc->docclass->load($doc))
                $doc->_content_reset = true;
            else {
                if (!isset($doc->error_text))
                    $this->warnings[] = "$filename: Couldn’t load document.";
                else if ($doc->error_text)
                    $this->warnings[] = $doc->error_text;
                return false;
            }
        }

        // add document to filestore list
        if (is_array($this->filestore) && isset($doc->filestore)
            && ($sha1 = Filer::binary_sha1($doc)) !== null) {
            $this->filestore[] = new ZipDocument_File($filename, $doc->filestore, $sha1);
            return self::_add_done($doc, true);
        }

        // At this point, we will definitely create a new zipfile.

        // construct temporary directory
        if (!($tmpdir = $this->tmpdir()))
            return self::_add_done($doc, false);
        $zip_filename = "$tmpdir/";

        // populate with contents of filestore list, if any
        if (!$this->_add_filestore())
            return self::_add_done($doc, false);

        // construct subdirectories
        $fn = $filename;
        while (($p = strpos($fn, "/")) !== false) {
            $zip_filename .= substr($fn, 0, $p);
            if (!is_dir($zip_filename)
                && (file_exists($zip_filename) || !@mkdir($zip_filename, 0777))) {
                $this->warnings[] = "$filename: Couldn’t save document to this name.";
                return self::_add_done($doc, false);
            }
            $zip_filename .= "/";
            $fn = substr($fn, $p + 1);
        }
        if ($fn === "") {
            $this->warnings[] = "$filename: Bad filename.";
            return self::_add_done($doc, false);
        }
        $zip_filename .= $fn;

        // store file in temporary directory
        if (isset($doc->filestore)
            && @symlink($doc->filestore, $zip_filename))
            /* OK */;
        else if (isset($doc->content)) {
            $trylen = file_put_contents($zip_filename, $doc->content);
            if ($trylen != strlen($doc->content)) {
                clean_tempdirs();
                $trylen = file_put_contents($zip_filename, $doc->content);
            }
            if ($trylen != strlen($doc->content)) {
                $this->warnings[] = "$filename: Could not save.";
                return self::_add_done($doc, false);
            }
        }

        // track files to pass to `zip`
        $zip_filename = substr($zip_filename, strlen($tmpdir) + 1);
        if (($p = strpos($zip_filename, "/")) !== false) {
            $zip_filename = substr($zip_filename, 0, $p);
            $this->recurse = true;
        }
        $this->files[$zip_filename] = true;

        // complete
        if (time() - $this->start_time >= 0)
            set_time_limit(30);
        return self::_add_done($doc, true);
    }

    static private function _add_done($doc, $result) {
        if (isset($doc->_content_reset) && $doc->_content_reset)
            unset($doc->content, $doc->_content_reset);
        return $result;
    }

    private function _add_filestore() {
        if (($filestore = $this->filestore) !== null) {
            $this->filestore = null;
            foreach ($filestore as $f)
                if (!$this->_add($f, $f->filename, false))
                    return false;
        }
        return true;
    }

    public function add($doc, $filename = null) {
        return $this->_add($doc, $filename, true);
    }

    public function add_as($doc, $filename) {
        return $this->_add($doc, $filename, false);
    }

    public function download_headers() {
        if (!$this->headers) {
            header("Content-Disposition: attachment; filename=" . mime_quote_string($this->downloadname));
            header("Content-Type: " . $this->mimetype);
            $this->headers = true;
        }
    }

    private function create() {
        global $Now, $Opt;
        if (!($tmpdir = $this->tmpdir()))
            return set_error_html("Could not create temporary directory.");

        // maybe cache zipfile in docstore
        $zip_filename = "$tmpdir/_hotcrp.zip";
        if (count($this->filestore) > 0 && get($Opt, "docstore")
            && get($Opt, "docstoreAccelRedirect")) {
            // calculate sha1 for zipfile contents
            $sorted_filestore = $this->filestore;
            usort($sorted_filestore, function ($a, $b) {
                return strcmp($a->filename, $b->filename);
            });
            $sha1_input = count($sorted_filestore) . "\n";
            foreach ($sorted_filestore as $f)
                $sha1_input .= $f->filename . "\n" . $f->sha1 . "\n";
            if (count($this->warnings))
                $sha1_input .= "README-warnings.txt\n" . join("\n", $this->warnings) . "\n";
            $zipfile_sha1 = sha1($sha1_input, false);
            // look for zipfile
            $zfn = $Opt["docstore"] . "/tmp/" . $zipfile_sha1 . ".zip";
            if (Filer::prepare_filestore($Opt["docstore"], $zfn)) {
                if (file_exists($zfn)) {
                    if (($mtime = @filemtime($zfn)) < $Now - 21600)
                        @touch($zfn);
                    return $zfn;
                }
                $zip_filename = $zfn;
            }
        }

        // actually run zip
        if (!($zipcmd = get($Opt, "zipCommand", "zip")))
            return set_error_html("<code>zip</code> is not supported on this installation.");
        $this->_add_filestore();
        if (count($this->warnings))
            $this->add(join("\n", $this->warnings) . "\n", "README-warnings.txt");
        $opts = ($this->recurse ? "-rq" : "-q");
        set_time_limit(60);
        $out = system("cd $tmpdir; $zipcmd $opts '$zip_filename' '" . join("' '", array_keys($this->files)) . "' 2>&1", $status);
        if ($status != 0)
            return set_error_html("<code>zip</code> returned an error.  Its output: <pre>" . htmlspecialchars($out) . "</pre>");
        if (!file_exists($zip_filename))
            return set_error_html("<code>zip</code> output unreadable or empty.  Its output: <pre>" . htmlspecialchars($out) . "</pre>");
        return $zip_filename;
    }

    public function download() {
        $result = $this->create();
        if (is_string($result)) {
            set_time_limit(180); // large zip files might download slowly
            $this->download_headers();
            Filer::download_file($result);
            $result = (object) array("error" => false);
        }
        $this->clean();
        return $result;
    }
}

class Filer_UploadJson implements JsonSerializable {
    public $docid;
    public $content;
    public $filename;
    public $mimetype;
    public $timestamp;
    public function __construct($upload) {
        $this->content = file_get_contents($upload["tmp_name"]);
        if (isset($upload["name"])
            && strlen($upload["name"]) <= 255
            && is_valid_utf8($upload["name"]))
            $this->filename = $upload["name"];
        $this->mimetype = Mimetype::type(get($upload, "type", "application/octet-stream"));
        $this->timestamp = time();
    }
    public function jsonSerialize() {
        $x = array();
        foreach (get_object_vars($this) as $k => $v)
            if ($k === "content" && $v !== null) {
                $v = strlen($v) < 50 ? $v : substr($v, 0, 50) . "...";
                $x[$k] = convert_to_utf8($v);
            } else if ($v !== null)
                $x[$k] = $v;
        return $x;
    }
}

class Filer {
    static public $tempdir;

    // override these to tell Filer how to behave
    function mimetypes($doc = null, $docinfo = null) {
        // Return the acceptable mimetypes for $doc.
        return [];
    }
    function validate_content($doc) {
        // load() callback. Return `true` if content of $doc is up to date and
        // need not be checked by load_content.
        return true;
    }
    function load_content($doc) {
        // load() callback. Return `true` if content was successfully loaded.
        // On return true, at least one of `$doc->content` and
        // `$doc->filestore` must be set.
        return false;
    }
    function filestore_pattern($doc) {
        // load()/store() callback. Return the filestore pattern suitable for
        // `$doc`.
        return null;
    }
    function dbstore($doc, $docinfo) {
        // store() callback. Return a `Filer_Dbstore` object to tell how to
        // store the document in the database.
        return null;
    }
    function store_other($doc, $docinfo) {
        // store() callback. Store `$doc` elsewhere (e.g. S3) if appropriate.
    }

    // main accessors
    static function has_content($doc) {
        return is_string(get($doc, "content"))
            || is_string(get($doc, "content_base64"))
            || self::content_filename($doc);
    }
    static function content_filename($doc) {
        if (is_string(get($doc, "content_file")) && is_readable($doc->content_file))
            return $doc->content_file;
        if (is_string(get($doc, "file")) && is_readable($doc->file))
            return $doc->file;
        if (is_string(get($doc, "filestore")) && is_readable($doc->filestore))
            return $doc->filestore;
        return false;
    }
    static function content($doc) {
        if (is_string(get($doc, "content")))
            return $doc->content;
        if (is_string(get($doc, "content_base64")))
            return $doc->content = base64_decode($doc->content_base64);
        if (($filename = self::content_filename($doc)))
            return $doc->content = @file_get_contents($filename);
        return false;
    }
    public function load($doc) {
        // Return true iff `$doc` can be loaded.
        if (!($has_content = self::has_content($doc))
            && ($fsinfo = $this->_filestore($doc))
            && is_readable($fsinfo[1])) {
            $doc->filestore = $fsinfo[1];
            $has_content = true;
        }
        return ($has_content && $this->validate_content($doc))
            || $this->load_content($doc);
    }
    public function load_to_filestore($doc) {
        if (!$this->load($doc))
            return false;
        if (!isset($doc->filestore)) {
            if (!self::$tempdir && (self::$tempdir = tempdir()) == false) {
                set_error_html($doc, "Cannot create temporary directory.");
                return false;
            }
            $sha1 = self::text_sha1($doc);
            if ($sha1 === false)
                $sha1 = $doc->sha1 = sha1($doc->content);
            $path = self::$tempdir . "/" . $sha1 . Mimetype::extension(self::_mimetype($doc));
            if (file_put_contents($path, $doc->content) != strlen($doc->content)) {
                set_error_html($doc, "Failed to save document to temporary file.");
                return false;
            }
            $doc->filestore = $path;
        }
        return true;
    }
    public function store($doc, $docinfo) {
        // load content (if unloaded)
        // XXX loading enormous documents into memory...?
        if (!$this->load($doc, $docinfo)
            || ($content = self::content($doc)) === null
            || $content === false
            || get($doc, "error"))
            return false;
        // calculate SHA-1, complain on mismatch
        $sha1 = sha1($content, true);
        if (isset($doc->sha1) && $doc->sha1 !== false && $doc->sha1 !== ""
            && self::binary_sha1($doc) !== $sha1) {
            set_error_html($doc, "Document claims checksum " . self::text_sha1($doc) . ", but has checksum " . bin2hex($sha1) . ".");
            return false;
        }
        $doc->sha1 = $sha1;
        if (isset($doc->size) && $doc->size && $doc->size != strlen($content))
            set_error_html($doc, "Document claims length " . $doc->size . ", but has length " . strlen($content) . ".");
        $doc->size = strlen($content);
        $content = null;
        // actually store
        if (($dbinfo = $this->dbstore($doc, $docinfo)))
            $this->store_database($dbinfo, $doc);
        $this->store_filestore($doc);
        $this->store_other($doc, $docinfo);
        return !get($doc, "error");
    }

    // dbstore functions
    function store_database($dbinfo, $doc) {
        global $Conf, $OK;
        $N = 400000;
        $idcol = $dbinfo->id_column;
        $while = "while storing document in database";

        $a = $ks = $vs = array();
        foreach ($dbinfo->columns as $k => $v)
            if ($k !== $idcol) {
                $ks[] = "`$k`=?";
                $vs[] = substr($v, 0, $N);
            }

        if (isset($dbinfo->columns[$idcol])) {
            $q = "update $dbinfo->table set " . join(",", $ks) . " where $idcol=?";
            $vs[] = $dbinfo->columns[$idcol];
        } else
            $q = "insert into $dbinfo->table set " . join(",", $ks);
        if (!($result = Dbl::query_apply($q, $vs))) {
            set_error_html($doc, $Conf->db_error_html(true, $while));
            return;
        }

        if (isset($dbinfo->columns[$idcol]))
            $doc->$idcol = $dbinfo->columns[$idcol];
        else {
            $doc->$idcol = $result->insert_id;
            if (!$doc->$idcol) {
                set_error_html($doc, $Conf->db_error_html(true, $while));
                $OK = false;
                return;
            }
        }

        for ($pos = $N; true; $pos += $N) {
            $a = array();
            foreach ($dbinfo->columns as $k => $v)
                if (strlen($v) > $pos)
                    $a[] = "`" . $k . "`=concat(`" . $k . "`,'" . sqlq(substr($v, $pos, $N)) . "')";
            if (!count($a))
                break;
            if (!$Conf->q("update $dbinfo->table set " . join(",", $a) . " where $idcol=" . $doc->$idcol)) {
                set_error_html($doc, $Conf->db_error_html(true, $while));
                return;
            }
        }

        // check that paper storage succeeded
        if ($dbinfo->check_contents
            && (!($result = $Conf->qe("select length($dbinfo->check_contents) from $dbinfo->table where $idcol=" . $doc->$idcol))
                || !($row = edb_row($result))
                || $row[0] != strlen(self::content($doc)))) {
            set_error_html($doc, "Failed to store your document. Usually this is because the file you tried to upload was too big for our system. Please try again.");
            return;
        }
    }

    // filestore functions
    function filestore_check($doc) {
        $fsinfo = $this->_filestore($doc);
        return $fsinfo && is_readable($fsinfo[1]);
    }
    static function prepare_filestore($parent, $path) {
        if (!self::_make_fpath_parents($parent, $path))
            return false;
        // Ensure an .htaccess file exists, even if someone else made the
        // filestore directory
        $htaccess = "$parent/.htaccess";
        if (!is_file($htaccess)
            && @file_put_contents($htaccess, "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n") === false) {
            @unlink($htaccess);
            return false;
        }
        return true;
    }
    function store_filestore($doc, $no_error = false) {
        if (!($fsinfo = $this->_filestore($doc)))
            return false;
        list($fsdir, $fspath) = $fsinfo;
        if (!self::prepare_filestore($fsdir, $fspath)) {
            $no_error || set_error_html($doc, "Internal error: docstore cannot be initialized.");
            return false;
        }
        $content = self::content($doc);
        if (!is_readable($fspath) || file_get_contents($fspath) !== $content) {
            if (file_put_contents($fspath, $content) != strlen($content)) {
                @unlink($fspath);
                $no_error || set_error_html($doc, "Internal error: docstore failure.");
                return false;
            }
            @chmod($fspath, 0660 & ~umask());
        }
        $doc->filestore = $fspath;
        return true;
    }

    // download
    static function download_file($filename, $no_accel = false) {
        global $Opt, $zlib_output_compression;
        // if docstoreAccelRedirect, output X-Accel-Redirect header
        if (($dar = get($Opt, "docstoreAccelRedirect"))
            && ($ds = get($Opt, "docstore"))
            && !$no_accel) {
            if (!str_ends_with($ds, "/"))
                $ds .= "/";
            if (str_starts_with($filename, $ds)
                && ($tail = substr($filename, strlen($ds)))
                && preg_match(',\A[^/]+,', $tail)) {
                if (!str_ends_with($dar, "/"))
                    $dar .= "/";
                header("X-Accel-Redirect: $dar$tail");
                return;
            }
        }
        // write length header, flush output buffers
        if (!$zlib_output_compression)
            header("Content-Length: " . filesize($filename));
        flush();
        while (@ob_end_flush())
            /* do nothing */;
        // read file directly to output
        readfile($filename);
    }
    function download($doc, $downloadname = null, $attachment = null) {
        if (is_object($doc) && !isset($doc->docclass))
            $doc->docclass = $this;
        return self::multidownload($doc, $downloadname, $attachment);
    }
    static function multidownload($doc, $downloadname = null, $attachment = null) {
        global $zlib_output_compression;
        if (is_array($doc) && count($doc) == 1) {
            $doc = $doc[0];
            $downloadname = null;
        }
        if (!$doc || (is_object($doc) && isset($doc->size) && $doc->size == 0))
            return set_error_html("Empty file.");
        if (is_array($doc)) {
            $z = new ZipDocument($downloadname);
            foreach ($doc as $d)
                $z->add($d);
            return $z->download();
        }
        if (!self::has_content($doc)
            && (!get($doc, "docclass") || !$doc->docclass->load($doc))) {
            $error_html = "Don’t know how to download.";
            if (get($doc, "error") && isset($doc->error_html))
                $error_html = $doc->error_html;
            else if (get($doc, "error") && isset($doc->error_text))
                $error_html = htmlspecialchars($doc->error_text);
            return set_error_html($error_html);
        }

        // Print paper
        $doc_mimetype = self::_mimetype($doc);
        header("Content-Type: " . Mimetype::type($doc_mimetype));
        if ($attachment === null)
            $attachment = !Mimetype::disposition_inline($doc_mimetype);
        if (!$downloadname) {
            $downloadname = $doc->filename;
            if (($slash = strrpos($downloadname, "/")) !== false)
                $downloadname = substr($downloadname, $slash + 1);
        }
        header("Content-Disposition: " . ($attachment ? "attachment" : "inline") . "; filename=" . mime_quote_string($downloadname));
        // reduce likelihood of XSS attacks in IE
        header("X-Content-Type-Options: nosniff");
        if (($filename = self::content_filename($doc)))
            self::download_file($filename, get($doc, "no_cache") || get($doc, "no_accel"));
        else {
            $content = self::content($doc);
            if (!$zlib_output_compression)
                header("Content-Length: " . strlen($content));
            echo $content;
        }
        return (object) array("error" => false);
    }

    // upload
    static function file_upload_json($upload) {
        if (is_string($upload) && $upload)
            $upload = $_FILES[$upload];
        if (!$upload || !is_array($upload) || !fileUploaded($upload)
            || !isset($upload["tmp_name"]))
            return set_error_html($doc, "Upload error. Please try again.");
        $doc = new Filer_UploadJson($upload);
        if ($doc->content === false || strlen($doc->content) == 0)
            return set_error_html($doc, "The uploaded file was empty. Please try again.");
        return $doc;
    }
    function upload($doc, $docinfo) {
        global $Conf;
        if (!is_object($doc)) {
            error_log(caller_landmark() . ": Filer::upload called with non-object");
            return false;
        }
        if (!$this->load($doc) && !get($doc, "error_html"))
            set_error_html($doc, "Empty document.");
        if (get($doc, "error"))
            return false;

        // Check if paper one of the allowed mimetypes.
        if (!isset($doc->mimetype) && isset($doc->type) && is_string($doc->type))
            $doc->mimetype = $doc->type;
        if (!get($doc, "mimetype"))
            $doc->mimetype = "application/octet-stream";
        // Sniff content since MacOS browsers supply bad mimetypes.
        if (($m = Mimetype::sniff(self::content($doc))))
            $doc->mimetype = $m->mimetype;
        if (($m = Mimetype::lookup($doc->mimetype)))
            $doc->mimetypeid = $m->mimetypeid;

        $mimetypes = $this->mimetypes($doc, $docinfo);
        for ($i = 0; $i < count($mimetypes); ++$i)
            if ($mimetypes[$i]->mimetype === $doc->mimetype)
                break;
        if ($i >= count($mimetypes) && count($mimetypes) && !$doc->filterType) {
            $e = "I only accept " . htmlspecialchars(Mimetype::description($mimetypes)) . " files.";
            $e .= " (Your file has MIME type “" . htmlspecialchars($doc->mimetype) . "” and starts with “" . htmlspecialchars(substr($doc->content, 0, 5)) . "”.)<br />Please convert your file to a supported type and try again.";
            set_error_html($doc, $e);
            return false;
        }

        if (!get($doc, "timestamp"))
            $doc->timestamp = time();
        return $this->store($doc, $docinfo);
    }

    // SHA-1 helpers
    static function text_sha1($doc) {
        $sha1 = is_object($doc) ? get($doc, "sha1") : $doc;
        if (is_string($sha1) && strlen($sha1) > 40)
            $sha1 = trim($sha1);
        if (is_string($sha1) && strlen($sha1) === 20)
            return bin2hex($sha1);
        else if (is_string($sha1) && strlen($sha1) === 40 && ctype_xdigit($sha1))
            return strtolower($sha1);
        else
            return false;
    }
    static function binary_sha1($doc) {
        $sha1 = is_object($doc) ? get($doc, "sha1") : $doc;
        if (is_string($sha1) && strlen($sha1) > 40)
            $sha1 = trim($sha1);
        if (is_string($sha1) && strlen($sha1) === 20)
            return $sha1;
        else if (is_string($sha1) && strlen($sha1) === 40 && ctype_xdigit($sha1))
            return hex2bin($sha1);
        else
            return false;
    }

    // private functions
    private static function _mimetype($doc) {
        return (isset($doc->mimetype) ? $doc->mimetype : $doc->mimetypeid);
    }
    private function _filestore($doc) {
        if (!($fsinfo = $this->filestore_pattern($doc)))
            return $fsinfo;
        if (get($doc, "error"))
            return null;

        list($fdir, $fpath) = $fsinfo;
        $sha1 = false;

        $xfpath = $fdir;
        $fpath = substr($fpath, strlen($fdir));
        while (preg_match("/\\A(.*?)%(\d*)([%hx])(.*)\\z/", $fpath, $m)) {
            $fpath = $m[4];

            $xfpath .= $m[1];
            if ($m[3] === "%")
                $xfpath .= "%";
            else if ($m[3] === "x")
                $xfpath .= Mimetype::extension(self::_mimetype($doc));
            else {
                if ($sha1 === false)
                    $sha1 = self::text_sha1($doc);
                if ($sha1 === false
                    && ($content = self::content($doc)) !== false)
                    $sha1 = $doc->sha1 = sha1($content);
                if ($sha1 === false)
                    return array(null, null);
                if ($m[2] !== "")
                    $xfpath .= substr($sha1, 0, +$m[2]);
                else
                    $xfpath .= $sha1;
            }
        }

        if ($fdir && $fdir[strlen($fdir) - 1] === "/")
            $fdir = substr($fdir, 0, strlen($fdir) - 1);
        return array($fdir, $xfpath . $fpath);
    }
    private static function _make_fpath_parents($fdir, $fpath) {
        $lastslash = strrpos($fpath, "/");
        $container = substr($fpath, 0, $lastslash);
        while (str_ends_with($container, "/"))
            $container = substr($container, 0, strlen($container) - 1);
        if (!is_dir($container)) {
            if (strlen($container) < strlen($fdir)
                || !($parent = self::_make_fpath_parents($fdir, $container))
                || !@mkdir($container, 0770))
                return false;
            if (!@chmod($container, 02770 & fileperms($parent))) {
                @rmdir($container);
                return false;
            }
        }
        return $container;
    }


    public function is_archive($doc) {
        return $doc->filename
            && preg_match('/\.(?:zip|tar|tgz|tar\.[gx]?z|tar\.bz2)\z/i', $doc->filename);
    }

    public function archive_listing($doc) {
        if (!$this->load_to_filestore($doc))
            return false;
        $type = null;
        if (preg_match('/\.zip\z/i', $doc->filename))
            $type = "zip";
        else if (preg_match('/\.(?:tar|tgz|tar\.[gx]?z|tar\.bz2)\z/i', $doc->filename))
            $type = "tar";
        else if (!$doc->filename) {
            $contents = file_get_contents($doc->filestore, false, null, 0, 1000);
            if (str_starts_with($contents, "\x1F\x9D")
                || str_starts_with($contents, "\x1F\xA0")
                || str_starts_with($contents, "BZh")
                || str_starts_with($contents, "\x1F\x8B")
                || str_starts_with($contents, "\xFD7zXZ\x00"))
                $type = "tar";
            else if (str_starts_with($contents, "ustar\x0000")
                     || str_starts_with($contents, "ustar  \x00"))
                $type = "tar";
            else if (str_starts_with($contents, "PK\x03\x04")
                     || str_starts_with($contents, "PK\x05\x06")
                     || str_starts_with($contents, "PK\x07\x08"))
                $type = "zip";
        }
        if (!$type)
            return false;
        if ($type === "zip")
            $cmd = "zipinfo -1 ";
        else
            $cmd = "tar tf ";
        $cmd .= escapeshellarg($doc->filestore);
        $pipes = null;
        $proc = proc_open($cmd, [1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($proc);
        if ($status != 0 || $err != "")
            error_log("failed $cmd: status $status, stderr $err");
        return explode("\n", rtrim($out));
    }
}

class Filer_Dbstore {
    public $table;
    public $id_column;
    public $columns;
    public $check_contents;

    public function __construct($table, $id_column, $columns, $check_contents = null) {
        $this->table = $table;
        $this->id_column = $id_column;
        $this->columns = $columns;
        $this->check_contents = $check_contents;
    }
}
