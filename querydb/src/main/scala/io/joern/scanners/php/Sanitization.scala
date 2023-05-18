package io.joern.scanners.php

import io.joern.console._
import io.joern.dataflowengineoss.language._
import io.joern.dataflowengineoss.queryengine.EngineContext
import io.joern.macros.QueryMacros._
import io.joern.scanners._
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.codepropertygraph.generated.nodes._
import io.shiftleft.semanticcpg.language._

object SanitizationFilter {
   implicit val resolver: ICallResolver = NoResolve
   // implicit val attack_san_functions: List[String] = Constants.san_functions_sql
   val safe_types: List[String] = List("int", "integer", "bool", "boolean", "float", "double")
   
   // Check whether given CPG Node is sanitized, filter accordingly
   def isSanitized(arg: Any)(implicit san_functions_specific: List[String]): Boolean = arg match {
      case traversal: overflowdb.traversal.Traversal[_] => isSanitized(traversal.l)(san_functions_specific)
      case List() => false
      case listOfNodes: List[_] => listOfNodes.map(isSanitized(_)(san_functions_specific)).reduce((x,y) => x && y)
      case literal: Literal => true
      case func: Call => Constants.san_functions_all.contains(func.name) || 
                           san_functions_specific.contains(func.name) || 
                           (func.name == "<operator>.cast" && safe_types.contains(func.typeFullName)) ||
                           isSanitized(func.argument)(san_functions_specific) || 
                           {
                              if (!func.callee.ast.isReturn.astChildren.isEmpty) 
                                 isSanitized(func.callee.ast.isReturn.astChildren)(san_functions_specific) 
                              else false
                           }
      case identifier: Identifier => {
         val definingNode = {
            // identifier coming from method parameter is unsanitized
            if (identifier.method.parameter.name.l.contains(identifier.name) && identifier.astParent.assignment.argument(1).isIdentifier.name(identifier.name).isEmpty) 
               identifier.method.parameter.name(identifier.name)
            // identifier used as argument of settype with a safe type will be sanitized
            else if (!identifier.astParent.isCallTo("settype").isEmpty && safe_types.contains(identifier.astParent.isCallTo("settype").argument(2).code.l.head.replaceAll("\"","")) ){
               identifier.astParent.isCallTo("settype").argument(2)
            }
            // CfgNode assigning a variable will have ddgIn pointing to the value of the assignment
            else if (!identifier.ddgIn.astParent.assignment.argument(1).isIdentifier.name(identifier.name).isEmpty) {
               identifier.ddgIn.astParent.assignment.argument(2)
            }
            // empty ddgIn edge
            // assigned but never used variables don't have ddgIn edge
            else if (identifier.ddgIn.isEmpty && !identifier.astParent.assignment.isEmpty) identifier.astParent.assignment.argument(2)
               // add identifiers from included files
            else if (identifier.ddgIn.isEmpty) {
               val includedFileNames = identifier.repeat(_.astParent)(_.until(_.isMethod.name("<global>"))).ast.isCallTo("include|require").argument.code.map(_.replaceAll("\"","")).l
               val x: List[Identifier] = includedFileNames.flatMap(file => cpg.method.fullName(file+":<global>").methodReturn.ddgIn.isIdentifier.l).filter(_.name==identifier.name)
            }
            // used variable will have ddgIn periodically pointing to its last usage
            else {
               identifier.repeat(_.ddgIn.isIdentifier.name(identifier.name))(_.until(_.astParent.isCallTo("settype|<operator>.assignment").argument(1).isIdentifier.name(identifier.name)))
            }
         }
         isSanitized(definingNode)(san_functions_specific)
      }
      case metadata: MetaData => true
      case namespace: Namespace => true
      case namespaceblock: NamespaceBlock => true
      case typedec: TypeDecl => true
      case block: Block => true
      case file: File => true
      case local: Local => false
      // case method: Method => \
      case methodParam: MethodParameterIn => false
      case declaredtype: Type => false
      case declaredtype: TypeRef => false
      case _ => {
         // println(arg)
         false
      }
   }
}