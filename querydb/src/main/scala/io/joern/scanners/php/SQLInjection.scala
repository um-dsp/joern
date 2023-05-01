package io.joern.scanners.php

import io.joern.console._
import io.joern.dataflowengineoss.language._
import io.joern.dataflowengineoss.queryengine.EngineContext
import io.joern.macros.QueryMacros._
import io.joern.scanners._
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.semanticcpg.language._

object SQLInjection extends QueryBundle {

  implicit val resolver: ICallResolver = NoResolve

  @q
  def SQLInjection()(implicit context: EngineContext): Query =
    Query.make(
      name = "php-sql-injection",
      author = Crew.niko,
      title = "SQL injection: A parameter is used in an insecure database API call.",
      description = """
          |An attacker controlled parameter is used in an insecure database API call.
          |
          |If the parameter is not validated and sanitized, this is a SQL injection.
          |""".stripMargin,
      score = 11,
      withStrRep({ cpg =>
        // $_REQUEST["foo"], $_GET["foo"], $_POST["foo"]
        // are identifier (at the moment)
   def source = 
          cpg.call.name(Operators.assignment).argument.code(".*_(REQUEST|GET|POST|ENV|COOKIE|SERVER).*") 

        def sink = cpg.call.code(".*(mysql_query|mysqli_query|pg_query|sqlite_query|query).*").argument.l
	
	def path = sink.reachableByFlows(source)

  var sanitized = false
  val sanitization = Array("intval","floatval",
  "intval", "floatval", "doubleval", "urlencode", "rawurlencode", "round", "floor", "strlen", "strrpos", "strpos", "strftime", "strtotime", "md5", "md5_file", "sha1", "sha1_file", "crypt", "crc32", "hash", "mhash", "hash_hmac", "mcrypt_encrypt", "mcrypt_generic", "base64_encode", "ord", "sizeof", "count", "bin2hex", "levenshtein", "abs", "bindec", "decbin", "dechex", "decoct", "hexdec", "rand", "max", "min", "metaphone", "tempnam", "soundex","money_format", "number_format", "filetype", "nl_langinfo", "bzcompress", "convert_uuencode", "gzdeflate", "gzencode", "gzcompress", "http_build_query", "lzf_compress", "zlib_encode", "imap_binary", "iconv_mime_encode", "bson_encode", "sqlite_udf_encode_binary", "session_name", "readlink", "getservbyport", "getprotobynumber", 
  "gethostname", "gethostbynamel", "gethostbyname","filter_var","pg_escape_identifier",
  "pg_escape_literal","mysqli_real_escape_string","dbx_escape_string", "db2_escape_string", "ingres_escape_string", "maxdb_escape_string", "maxdb_real_escape_string", "mysql_escape_string", "mysql_real_escape_string", "mysqli_escape_string", "mysqli_real_escape_string", "sqlite_escape_string","cubrid_real_escape_string")

  for (c <- path.head.elements.isCall.name) {if (sanitization contains c)  {sanitized = true}}

  if(sanitized) {cpg.call("veryrandomname").l} else {sink.reachableBy(source)}
	
      }),
      tags = List(QueryTags.remoteCodeExecution, QueryTags.default)
    )
}
