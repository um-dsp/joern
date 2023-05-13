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
   var san_functions = SanFuncs.san_functions_all   
   def set_san_functions (attack_specific_sanitizations : List[String]) ={
      san_functions = san_functions ::: attack_specific_sanitizations  
   }
  
   case class ListOfNodes(val nodes: List[AnyRef])

   // Check whether given CPG Node is sanitized, filter accordingly
   def isSanitized(arg: AnyRef): Boolean = arg match {
      case traversal: overflowdb.traversal.Traversal[_] => isSanitized(traversal.l)
      case List() => true
      // List of expressions or parameter input
      case listNodes: ListOfNodes => isSanitized(listNodes.nodes.head) && isSanitized(listNodes.nodes.tail)
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
         //println(arg)
         false
      }
   }
}