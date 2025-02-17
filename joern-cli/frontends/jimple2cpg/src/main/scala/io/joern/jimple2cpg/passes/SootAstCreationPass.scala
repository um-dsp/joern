package io.joern.jimple2cpg.passes

import io.joern.jimple2cpg.Jimple2Cpg
import io.joern.x2cpg.datastructures.Global
import io.shiftleft.codepropertygraph.Cpg
import io.shiftleft.passes.ConcurrentWriterCpgPass
import org.slf4j.LoggerFactory
import soot.Scene
import soot.SootClass
import soot.SourceLocator

/** Creates the AST layer from the given class file and stores all types in the given global parameter.
  */
class SootAstCreationPass(cpg: Cpg) extends ConcurrentWriterCpgPass[SootClass](cpg) {

  val global: Global = new Global()
  private val logger = LoggerFactory.getLogger(classOf[AstCreationPass])

  override def generateParts(): Array[_ <: AnyRef] = Scene.v().getApplicationClasses().toArray()

  override def runOnPart(builder: DiffGraphBuilder, part: SootClass): Unit = {
    val jimpleFile = SourceLocator.v().getSourceForClass(part.getName())
    try {
      // sootClass.setApplicationClass()
      val localDiff = new AstCreator(jimpleFile, part, global).createAst()
      builder.absorb(localDiff)
    } catch {
      case e: Exception =>
        logger.warn(s"Cannot parse: $part", e)
    }
  }

}
