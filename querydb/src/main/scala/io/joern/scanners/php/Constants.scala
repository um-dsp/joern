package io.joern.scanners.php

object Constants {

  val attacker_input: String = ".*(_GET|_POST|_COOKIE|_REQUEST|_ENV|HTTP_ENV_VARS|HTTP_POST_VARS|HTTP_GET_VARS).*"

  val san_functions_sql = List( 
    "addslashes",
    "dbx_escape_string",
    "db2_escape_string",
    "ingres_escape_string",
    "maxdb_escape_string",
    "maxdb_real_escape_string",
    "mysql_escape_string",
    "mysql_real_escape_string",
    "mysqli_escape_string",
    "mysqli_real_escape_string",
    "pg_escape_string", 
    "pg_escape_bytea",
    "sqlite_escape_string",
    "sqlite_udf_encode_binary",
    "cubrid_real_escape_string")

  val san_functions_xss = List("htmlentities",
    "htmlspecialchars",
    "highlight_string")

  val san_functions_all = List("intval",
    "floatval",
    "doubleval",
    "filter_input",
    "urlencode",
    "rawurlencode",
    "round",
    "floor",
    "strlen",
    "strrpos",
    "strpos",
    "strftime",
    "strtotime",
    "md5",
    "md5_file",
    "sha1",
    "sha1_file",
    "crypt",
    "crc32",
    "hash",
    "mhash",
    "hash_hmac",
    "password_hash",
    "mcrypt_encrypt",
    "mcrypt_generic",
    "base64_encode",
    "ord",
    "sizeof",
    "count",
    "bin2hex",
    "levenshtein",
    "abs",
    "bindec",
    "decbin",
    "dechex",
    "decoct",
    "hexdec",
    "rand",
    "max",
    "min",
    "metaphone",
    "tempnam",
    "soundex",
    "money_format",
    "number_format",
    "date_format",
    "filetype",
    "nl_langinfo",
    "bzcompress",
    "convert_uuencode",
    "gzdeflate",
    "gzencode",
    "gzcompress",
    "http_build_query",
    "lzf_compress",
    "zlib_encode",
    "imap_binary",
    "iconv_mime_encode",
    "bson_encode",
    "sqlite_udf_encode_binary",
    "session_name",
    "readlink",
    "getservbyport",
    "getprotobynumber",
    "gethostname",
    "gethostbynamel",
    "gethostbyname",
    "date", "ctype_digit")
  
  val san_functions_code = List()

  val san_functions_os_command  = List("escapeshellarg",
    "escapeshellcmd")
  
  val san_functions_file= List("basename",
    "dirname",
    "pathinfo")
}