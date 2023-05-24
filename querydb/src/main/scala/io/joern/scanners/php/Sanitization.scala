package io.joern.scanners.php

import io.joern.console._
import io.joern.dataflowengineoss.language._
import io.joern.dataflowengineoss.queryengine.EngineContext
import io.joern.macros.QueryMacros._
import io.joern.scanners._
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.codepropertygraph.generated.nodes._
import io.shiftleft.codepropertygraph.generated.Cpg
import io.shiftleft.semanticcpg.language._

object SanitizationFilter {
   implicit val resolver: ICallResolver = NoResolve
   // implicit val attack_san_functions: List[String] = Constants.san_functions_sql
   val safe_types: List[String] = List("int", "integer", "bool", "boolean", "float", "double")

   var cpg = None: Option[Cpg]
   def setCpg(cpgInput: Cpg) = {
      cpg = Some(cpgInput)
   }

   // stores isSanitized result for quicker lookup: decreasing analysis of sample-web-app from 0.1135 to 0.00767 seconds
   case class isSanitizedInput(node: Any, sanitizedParameters: List[Boolean] = List(), san_functions_specific: List[String])
   var sanitizationMap = collection.mutable.Map[isSanitizedInput, Boolean]()

   def isMethodSanitized(function: Method, arguments: List[Expression], sanitizedParameters: List[Boolean])(implicit san_functions_specific: List[String]): Boolean = {
      val isArgumentSanitized: List[Boolean] = arguments.map(isSanitized(_, sanitizedParameters)(san_functions_specific))
      // sanitization function returns a sanitized result
      if (Constants.san_functions_all.contains(function.name) || san_functions_specific.contains(function.name)) true
      else if (function.name == "<operator>.assignment") isArgumentSanitized(1)
      // if the function isn't user defined (e.g. <operator>.plus) assume it's sanitized only if all arguments are sanitized
      else if (function.code == "<empty>") !isArgumentSanitized.contains(false)
      // else given whether passed arguments are sanitized check if return is sanitized
      else {
         isSanitized(function.ast.isReturn, isArgumentSanitized)(san_functions_specific)
      }
   }

   // Check whether given CPG Node is sanitized, filter accordingly
   def isSanitized(node: Any, sanitizedParameters: List[Boolean] = List())(implicit san_functions_specific: List[String]): Boolean = 
      sanitizationMap.get(isSanitizedInput(node, sanitizedParameters, san_functions_specific)) match {
      case Some(result) => result
      case None => {
         val result: Boolean = 
         // try { 
            node match { 
            case traversal: overflowdb.traversal.Traversal[_] => isSanitized(traversal.l, sanitizedParameters)(san_functions_specific)
            case List() => true
            case listOfNodes: List[_] => listOfNodes.map(isSanitized(_, sanitizedParameters)(san_functions_specific)).reduce((x,y) => x && y)
            case literal: Literal => true
            case function: Call => (function.name == "<operator>.cast" && safe_types.contains(function.typeFullName)) ||
                                 isMethodSanitized(function.callee.l.head, function.argument.l, sanitizedParameters)(san_functions_specific)
            case identifier: Identifier => {
               var isArgumentSanitized = sanitizedParameters
               val definingNode = {
                  // identifier coming from method parameter is unsanitized
                  if (identifier.method.parameter.name.l.contains(identifier.name) && identifier.ddgIn.isIdentifier.name(identifier.name).l.isEmpty && identifier.astParent.assignment.argument(1).isIdentifier.name(identifier.name).isEmpty)
                     identifier.method.parameter.name(identifier.name).l
                  // identifier used as argument of settype with a safe type will be sanitized
                  else if (!identifier.astParent.isCallTo("settype").isEmpty && safe_types.contains(identifier.astParent.isCallTo("settype").argument(2).code.l.head.replaceAll("\"","")) ){
                     identifier.astParent.isCallTo("settype").argument(2).l
                  }
                  // CfgNode assigning a variable will have ddgIn pointing to the value of the assignment
                  else if (!identifier.astParent.assignment.argument(1).isIdentifier.name(identifier.name).isEmpty) {
                     identifier.astParent.assignment.argument(2).l
                  }
                  // identifier passed by reference to function
                  else if (try identifier.astParent.isCallTo(".*").callee.parameter.l.map(p => p.evaluationStrategy == "BY_REFERENCE")(identifier.order-1) catch {case _ : Throwable => false}) {
                     isArgumentSanitized = identifier.astParent.isCallTo(".*").argument.l.map(node => {
                        var paramsByRef = node.astParent.isCallTo(".*").callee.parameter.l.map(p => p.evaluationStrategy == "BY_REFERENCE")
                        if (!paramsByRef.isEmpty && paramsByRef(node.order-1)) 
                           isSanitized(node.ddgIn.l, sanitizedParameters)(san_functions_specific) 
                        else isSanitized(node, sanitizedParameters)(san_functions_specific)
                     })
                     identifier.astParent.isCallTo(".*").callee.methodReturn.ddgIn.isIdentifier.name(identifier.astParent.isCallTo(".*").callee.parameter.l(identifier.order-1).name).l
                  }
                  // assigned but never used variables don't have ddgIn edge
                  else if (identifier.ddgIn.isEmpty && !identifier.astParent.assignment.argument(1).isIdentifier.name(identifier.name).isEmpty) {
                     identifier.astParent.assignment.argument(2).l
                  }
                  // add identifiers from included files            
                  else if (identifier.ddgIn.isEmpty) {
                     cpg match {
                        case None => {
                           println("Don't forget to set the cpg of the SanitizationFilter class using setCpg(cpg) function")
                           List()
                        }
                        case Some(cpg) => {
                           val includeFileNames = cpg.method.fullName(identifier.file.name.l.head+":<global>").call("include|require").argument.code.map(_.replaceAll("\"","")).l
                           val fileIdentifiers = includeFileNames.flatMap(file => cpg.method.fullName(file+":<global>").methodReturn.ddgIn.isIdentifier.name(identifier.name).l)
                           fileIdentifiers
                        }
                     }
                  }
                  // used variable will have ddgIn periodically pointing to its last usage
                  else {
                     identifier.ddgIn.isIdentifier.name(identifier.name).l
                     // identifier.repeat(_.ddgIn.isIdentifier.name(identifier.name))(_.until(_.astParent.isCallTo("settype|<operator>.assignment").argument(1).isIdentifier.name(identifier.name)))
                  }
               }
               !definingNode.isEmpty && isSanitized(definingNode, isArgumentSanitized)(san_functions_specific)
            }
            case metadata: MetaData => true
            case namespace: Namespace => true
            case namespaceblock: NamespaceBlock => true
            case typedec: TypeDecl => true
            case block: Block => true
            case file: File => true
            case local: Local => false
            // case method: Method => \
            case methodParam: MethodParameterIn => {
               if (sanitizedParameters.isEmpty) false
               else sanitizedParameters(methodParam.index-1)
            }
            case returnBlock: Return => isSanitized(returnBlock.astChildren, sanitizedParameters)(san_functions_specific)
            case declaredtype: Type => true
            case declaredtype: TypeRef => true
            case _ => {
               // println(node)
               false
            }
         } 
         // } catch {case _ => false}
         sanitizationMap(isSanitizedInput(node, sanitizedParameters, san_functions_specific)) = result
         result
   }
}

   // make sure to load the cpg of testCases.php into memory before running the command in the joern terminal
   // to run enter: SanitizationFilter.printTestOutput(cpgObject)
   def printTestOutput(cpg: Cpg, san_functions_specific: List[String] = List()) = {
      val t0 = System.nanoTime()
      // val sanitized = cpg.identifier.filter(SanitizationFilter.isSanitized(_)).name.dedup.l.filter(!List("p1", "p2", "unsan11", "unsan14").contains(_)).sortWith((s1, s2) => s1.replaceAll("[A-Za-z]","").toInt < s2.replaceAll("[A-Za-z]","").toInt)
      // val unsanitized = cpg.identifier.filterNot(SanitizationFilter.isSanitized(_)).name.dedup.l.filter(!List("p1", "p2", "san12", "san61", "tmp", "_GET", "san14").contains(_)).sortWith((s1, s2) => s1.replaceAll("[A-Za-z]","").toInt < s2.replaceAll("[A-Za-z]","").toInt)
      implicit val san_functions = san_functions_specific
      SanitizationFilter.setCpg(cpg)
      val sanitized = cpg.identifier.filter(SanitizationFilter.isSanitized(_)).name.dedup.l
      val unsanitized = cpg.identifier.filterNot(SanitizationFilter.isSanitized(_)).name.dedup.l
      println("Sanitized Identifiers: " + sanitized)
      println("Unsanitized Identifiers: " + unsanitized)
      val t1 = System.nanoTime()
      println("Elapsed time: " + (t1 - t0)*1e-9 + " seconds")
   }
}