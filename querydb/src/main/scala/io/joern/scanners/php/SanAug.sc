@main def exec(cpgFile: String) = { //, outFile: String) = {
   importCpg(cpgFile)
   val san_functions_sql = List("addslashes", "dbx_escape_string", "db2_escape_string", "ingres_escape_string",  
      "maxdb_escape_string", "maxdb_real_escape_string", "mysql_escape_string", "mysql_real_escape_string",  
      "mysqli_escape_string", "mysqli_real_escape_string", "pg_escape_string", "pg_escape_bytea",  
      "sqlite_escape_string", "sqlite_udf_encode_binary", "cubrid_real_escape_string")
   val san_functions_all = List("intval", "floatval", "doubleval", "filter_input", "urlencode", "rawurlencode", "round", "floor", "strlen", "strrpos", "strpos", "strftime", "strtotime", "md5", "md5_file", "sha1", "sha1_file", "crypt", "crc32", "hash", "mhash", "hash_hmac", "password_hash", "mcrypt_encrypt", "mcrypt_generic", "base64_encode", "ord", "sizeof", "count", "bin2hex", "levenshtein", "abs", "bindec", "decbin", "dechex", "decoct", "hexdec", "rand", "max", "min", "metaphone", "tempnam", "soundex", "money_format", "number_format", "date_format", "filetype", "nl_langinfo", "bzcompress", "convert_uuencode", "gzdeflate", "gzencode", "gzcompress", "http_build_query", "lzf_compress", "zlib_encode", "imap_binary", "iconv_mime_encode", "bson_encode", "sqlite_udf_encode_binary", "session_name", "readlink", "getservbyport", "getprotobynumber", "gethostname", "gethostbynamel", "gethostbyname", "date", "ctype_digit")

   // Check whether identifier assigned to a call of a sanitization function
   // Modify SAN_SQL tag of the identifier accordingly
   // To-Do: check that identifier is 1st argument of assignment; add assignment and function call propagation
   cpg.identifier.where(node => node.inAstMinusLeaf.isCall.name(".*assignment.*").argument(2)
      .isCall.filter(node => san_functions_sql.contains(node.name) || san_functions_all.contains(node.name)))
      .newTagNodePair("SAN_SQL", "TRUE").store 

   cpg.identifier.whereNot(_.inAstMinusLeaf.isCall.name(".*assignment.*").argument(2)
      .isCall.filter(node => san_functions_sql.contains(node.name) || san_functions_all.contains(node.name)))
      .newTagNodePair("SAN_SQL", "FALSE").store 

   run.commit
   
   println("SQL-Sanitized Identifiers: " + cpg.tag.name("SAN_SQL").value("TRUE").identifier.dedup.name.l)
   println("SQL-Unsanitized Identifiers: " + cpg.tag.name("SAN_SQL").value("FALSE").identifier.dedup.name.l)

   //Check if sink reachable by source via function calls
   sink.repeat(_.method.callIn.argument)(_.until(_.reachableBy(source))).l 

   //Problem with above sanitization tagging: 
   //x = sanitize(y), y will be wrongly tagged as sanitized
   //code should only add sanitized tag to identifiers that are 1st argument (x) of assignment functions not 2nd argument (y)
   //below is an attempt to check the above condition (but node.name is returning empty traversal)
   cpg.identifier.where(node => node.inAstMinusLeaf.isCall.name(".*assignment.*").where(_.argument(1).isIdentifier.name(node.name)).argument(2)
      .isCall.filter(node => san_functions_sql.contains(node.name) || san_functions_all.contains(node.name)))

   cpg.identifier.repeat(_.astParent)(_.until(_.isCall.name(".*assignment.*"))).isCall.argument(1).isIdentifier.name.sideEffect(x=>println(x))

   def customFun(nodes: List[Identifier]): List[Identifier] = {
      for (var node: Identifier <- nodes) {
         node.repeat(_.astParent)(_.until(_.isCall.name(".*assignment.*"))).isCall.argument(1)
         .isIdentifier.name.filter(_==node.name)
      }
   }
}