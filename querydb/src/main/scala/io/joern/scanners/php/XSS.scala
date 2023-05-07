package io.joern.scanners.php

import io.joern.console._
import io.joern.dataflowengineoss.language._
import io.joern.dataflowengineoss.queryengine.EngineContext
import io.joern.macros.QueryMacros._
import io.joern.scanners._
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.semanticcpg.language._

object XSS extends QueryBundle {

  implicit val resolver: ICallResolver = NoResolve

  @q
  def XSS()(implicit context: EngineContext): Query =
    Query.make(
      name = "php-xss",
      author = Crew.niko,
      title = "Cross site scripting vulnerability.",
      description = """
          |An attacker controlled parameter is used in an insecure echo | print  call.
          |
          |If the parameter is not validated and sanitized, this is a remote code execution.
          |""".stripMargin,
      score = 10,
      withStrRep({ cpg =>
        // $_REQUEST["foo"], $_GET["foo"], $_POST["foo"]
        // are identifier (at the moment)

      def source = 
          cpg.call.name(Operators.assignment).argument.code(".*_(REQUEST|GET|POST|ENV|COOKIE|SERVER).*") 


        def sink = cpg.call.name("print|echo|printf")


  var sanitized = false

    def isSanitized(path: Traversal[Path]) : Boolean = {
    if(path.isEmpty) return false
    var sanitized = false
    val sanitization = Array("intval","floatval",
  "intval", "floatval", "doubleval", "urlencode", "rawurlencode", "round", "floor", "strlen", "strrpos", "strpos", "strftime", "strtotime", "md5", "md5_file", "sha1", "sha1_file", "crypt", "crc32", "hash", "mhash", "hash_hmac", "mcrypt_encrypt", "mcrypt_generic", "base64_encode", "ord", "sizeof", "count", "bin2hex", "levenshtein", "abs", "bindec", "decbin", "dechex", "decoct", "hexdec", "rand", "max", "min", "metaphone", "tempnam", "soundex","money_format", "number_format", "filetype", "nl_langinfo", "bzcompress", "convert_uuencode", "gzdeflate", "gzencode", "gzcompress", "http_build_query", "lzf_compress", "zlib_encode", "imap_binary", "iconv_mime_encode", "bson_encode", "sqlite_udf_encode_binary", "session_name", "readlink", "getservbyport", "getprotobynumber", 
  "gethostname", "gethostbynamel", "gethostbyname","filter_var","pg_escape_identifier",
  "pg_escape_literal","mysqli_real_escape_string","dbx_escape_string", "db2_escape_string", "ingres_escape_string", "maxdb_escape_string", "maxdb_real_escape_string", "mysql_escape_string", "mysql_real_escape_string", "mysqli_escape_string", "mysqli_real_escape_string", "sqlite_escape_string","cubrid_real_escape_string")
     
     for (c <- path.head.elements.isCall.name) {if (sanitization contains c)  {sanitized = true}}
    return sanitized
   }

// if the sink (print:echo ...) is in a function
//this function will reset sink to statement where the function was called 
// it will do this recursively as long as the sink is within a function
// at every step we check the path between sink and source for sanitization functions 
// if  we found  a sanitization function we set sanitized boolean to true
 def result = sink.repeat(sink => sink.method.callIn)(_.until(_.argument.reachableByFlows(source))).sideEffect(node => if(isSanitized(node.argument.reachableByFlows(source))){sanitized = true}).l 

//at this point result reflects if the sink and source are reachableBy
// if its an empty traversal the source/sink are not reachable
// if it has a path it is reachable and tha path is the path between source and 
// the first function call to sink or to sink if no calls are maxdb_escape_string/
// if path is empty we do no falg as vulnerable 
// if the path is full we check if sanitized boolean and return empty traversal if it is sanitized 
// or return a full traversal(path) if sanitized bolean is false (we flag as vulnerable)
if(result.isEmpty){result}else {if(sanitized){cpg.call("veryrandomname").l} else {result}} 
  
  }),
      tags = List(QueryTags.remoteCodeExecution, QueryTags.default)
    
)}