package io.shiftleft.semanticcpg.language

import io.shiftleft.codepropertygraph.Cpg
import io.shiftleft.codepropertygraph.generated.nodes.{ConfigFile, Literal, Local, Method}
import overflowdb.traversal.Traversal

/** Language extensions for android. */
package object android {

  implicit def toNodeTypeStartersFlows(cpg: Cpg): NodeTypeStarters =
    new NodeTypeStarters(cpg)

  implicit def singleToLocalExt[A <: Local](a: A): LocalTraversal =
    new LocalTraversal(Traversal.fromSingle(a))

  implicit def iterOnceToLocalExt[A <: Local](a: IterableOnce[A]): LocalTraversal =
    new LocalTraversal(iterableToTraversal(a))

  implicit def singleToConfigFileExt[A <: ConfigFile](a: A): ConfigFileTraversal =
    new ConfigFileTraversal(Traversal.fromSingle(a))

  implicit def iterOnceToConfigFileExt[A <: ConfigFile](a: IterableOnce[A]): ConfigFileTraversal =
    new ConfigFileTraversal(iterableToTraversal(a))

  implicit def singleToMethodExt[A <: Method](a: A): MethodTraversal =
    new MethodTraversal(Traversal.fromSingle(a))

  implicit def iterOnceToMethodExt[A <: Method](a: IterableOnce[A]): MethodTraversal =
    new MethodTraversal(iterableToTraversal(a))

}
