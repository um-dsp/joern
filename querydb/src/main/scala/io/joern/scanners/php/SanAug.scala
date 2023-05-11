@main def exec(cpgFile: String) = { //, outFile: String) = {
   importCpg(cpgFile)
   val san_functions_sql = List("addslashes", "dbx_escape_string", "db2_escape_string", "ingres_escape_string",  
      "maxdb_escape_string", "maxdb_real_escape_string", "mysql_escape_string", "mysql_real_escape_string",  
      "mysqli_escape_string", "mysqli_real_escape_string", "pg_escape_string", "pg_escape_bytea",  
      "sqlite_escape_string", "sqlite_udf_encode_binary", "cubrid_real_escape_string")
   val san_functions_all = List("intval", "floatval", "doubleval", "filter_input", "urlencode", "rawurlencode", "round", "floor", "strlen", "strrpos", "strpos", "strftime", "strtotime", "md5", "md5_file", "sha1", "sha1_file", "crypt", "crc32", "hash", "mhash", "hash_hmac", "password_hash", "mcrypt_encrypt", "mcrypt_generic", "base64_encode", "ord", "sizeof", "count", "bin2hex", "levenshtein", "abs", "bindec", "decbin", "dechex", "decoct", "hexdec", "rand", "max", "min", "metaphone", "tempnam", "soundex", "money_format", "number_format", "date_format", "filetype", "nl_langinfo", "bzcompress", "convert_uuencode", "gzdeflate", "gzencode", "gzcompress", "http_build_query", "lzf_compress", "zlib_encode", "imap_binary", "iconv_mime_encode", "bson_encode", "sqlite_udf_encode_binary", "session_name", "readlink", "getservbyport", "getprotobynumber", "gethostname", "gethostbynamel", "gethostbyname", "date", "ctype_digit",
      "int", "float", "double", "char")
   val san_functions = san_functions_sql ::: san_functions_all

   // Check whether given CPG Node is sanitized, filter accordingly
   // To-Do: function call propagation and Method nodes
   val isSanitized: AnyRef => Boolean = (arg: AnyRef) => arg match {
      case traversal: Traversal[Expression] => isSanitized(traversal.l)
      case List() => true
      case list: List[Expression] => isSanitized(list.head) && isSanitized(list.tail)
      case literal: Literal => true
      case func: Call => san_functions.contains(func.name) || isSanitized(func.argument)
      case identifier: Identifier => {
         val definingNode = {
            // if identifier never used ddgIn returns empty, so directly check astParent
            if (identifier.ddgIn.isEmpty) identifier.astParent.assignment.argument(2)
            else identifier.ddgIn.astParent.assignment.argument(2)
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
      case declaredtype: Type => true
      case _ => {
         println(arg)
         false
      }
   }
   
   println("SQL-Sanitized Identifiers: " + cpg.identifier.filter(isSanitized).name.dedup.l)
   println("SQL-Unsanitized Identifiers: " + cpg.identifier.filterNot(isSanitized).name.dedup.l)

   //Check if sink reachable by source via function calls
   sink.repeat(_.method.callIn.argument)(_.until(_.reachableBy(source))).l 

   // ATA 
   # Step one look for identifiers x = floatval(y) and set x to sanitized
   def sanitized_vars = cpg.assignment.filter(_.code.contains("floatval")).argument(1).code.l 
   # step 2 : go to all identifier with code x and set flag to sanitized
   # as everytime we use x a new identifier is created for that use case
   cpg.identifier.filter(node => sanitized_vars.contains(node.code)).newTagNodePair("SAN_SQL", "TRUE").store  
   run.commit
   # step 3 : go to all assignments of form z = x  and set their first argument to sanitized
   cpg.assignment.where(_.argument(2).isIdentifier.tag.name("SAN_SQL")).argument(1).newTagNodePair("SAN_SQL", "TRUE").store  
   run.commit
   #step 4 :go to all assignment of form z = a+b+c+x and set z to sanitized
   cpg.assignment.where(_.argument(2).isCall.argument.tag.name("SAN_SQL")).argument(1).newTagNodePair("SAN_SQL", "TRUE").store 
   run.commit

   ### NOTE in every step after run.commit 
   ### run this cpg.identifier.tag.name("SAN_SQL").identifier.l
   ### you shoud see the list getting bigger as more variables are set as 
   ### sanitized
   
   
   def customFun(nodes: List[Identifier]): List[Identifier] = {
      for (var node: Identifier <- nodes) {
         node.repeat(_.astParent)(_.until(_.isCall.name(".*assignment.*"))).isCall.argument(1)
         .isIdentifier.name.filter(_==node.name)
      }
   }
}