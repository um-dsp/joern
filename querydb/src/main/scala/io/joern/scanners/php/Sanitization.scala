val san_functions_sql = List("addslashes", "dbx_escape_string", "db2_escape_string", "ingres_escape_string",  
   "maxdb_escape_string", "maxdb_real_escape_string", "mysql_escape_string", "mysql_real_escape_string",  
   "mysqli_escape_string", "mysqli_real_escape_string", "pg_escape_string", "pg_escape_bytea",  
   "sqlite_escape_string", "sqlite_udf_encode_binary", "cubrid_real_escape_string")
val san_functions_all = List("intval", "floatval", "doubleval", "filter_input", "urlencode", "rawurlencode", "round", "floor", "strlen", "strrpos", "strpos", "strftime", "strtotime", "md5", "md5_file", "sha1", "sha1_file", "crypt", "crc32", "hash", "mhash", "hash_hmac", "password_hash", "mcrypt_encrypt", "mcrypt_generic", "base64_encode", "ord", "sizeof", "count", "bin2hex", "levenshtein", "abs", "bindec", "decbin", "dechex", "decoct", "hexdec", "rand", "max", "min", "metaphone", "tempnam", "soundex", "money_format", "number_format", "date_format", "filetype", "nl_langinfo", "bzcompress", "convert_uuencode", "gzdeflate", "gzencode", "gzcompress", "http_build_query", "lzf_compress", "zlib_encode", "imap_binary", "iconv_mime_encode", "bson_encode", "sqlite_udf_encode_binary", "session_name", "readlink", "getservbyport", "getprotobynumber", "gethostname", "gethostbynamel", "gethostbyname", "date", "ctype_digit",
   "int", "float", "double", "char")
val san_functions = san_functions_sql ::: san_functions_all

// Check whether given CPG Node is sanitized, filter accordingly
val isSanitized: AnyRef => Boolean = (arg: AnyRef) => arg match {
   case traversal: Traversal[Expression] => isSanitized(traversal.l)
   case List() => true
   case listExpr: List[Expression] => isSanitized(listExpr.head) && isSanitized(listExpr.tail)
   case listParam: List[MethodParameterIn] => isSanitized(listParam.head) && isSanitized(listParam.tail)
   case literal: Literal => true
   case func: Call => san_functions.contains(func.name) || isSanitized(func.argument) || isSanitized(func.callee.ast.isReturn.astChildren)
   case identifier: Identifier => {
      val definingNode = {
         // identifier coming from method parameter is unsanitized
         if (identifier.method.parameter.name.l.contains(identifier.name) && identifier.astParent.assignment.argument(1).isIdentifier.name(identifier.name).isEmpty) 
            identifier.method.parameter.name(identifier.name)
         // CfgNode assigning a variable will have ddgIn pointing to the value of the assignment
         else if (!identifier.ddgIn.astParent.assignment.argument(1).isIdentifier.name(identifier.name).isEmpty) {
            identifier.ddgIn.astParent.assignment.argument(2)
         }
         // assigned but never used variables don't have ddgIn edge
         else if (identifier.ddgIn.isEmpty) identifier.astParent.assignment.argument(2)
         // used variable will have ddgIn periodically pointing to its last usage
         else {
            identifier.repeat(_.ddgIn.isIdentifier.name(identifier.name))(_.until(_.astParent.assignment.argument(1).isIdentifier.name(identifier.name)))
                     .astParent.assignment.argument(2)
         }
      }
      !definingNode.isEmpty && isSanitized(definingNode)
   }
   case metadata: MetaData => true
   case namespace: Namespace => true
   case namespaceblock: NamespaceBlock => true
   case typedec: TypeDecl => true
   case block: Block => true
   case file: File => true
   case local: Local => false
   // case method: Method => 
   case methodParam: MethodParameterIn => false
   case declaredtype: Type => true
   case _ => {
      println(arg)
      false
   }
}