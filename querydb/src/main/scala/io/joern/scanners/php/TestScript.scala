package io.joern.scanners.php

@main def exec(cpgFile: String) = { //, outFile: String) = {
    importCpg(cpgFile)
    val identifier = cpg.identifier.id(3).l.head
    val includeFileNames = cpg.method.fullName(identifier.file.name.l.head+":<global>").call("include|require").argument.code.map(_.replaceAll("\"","")).l
    val fileIdentifiers = includeFileNames.flatMap(file => cpg.method.fullName(file+":<global>").methodReturn.ddgIn.isIdentifier.l)
}