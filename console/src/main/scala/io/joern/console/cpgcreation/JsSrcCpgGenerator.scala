package io.joern.console.cpgcreation

import io.joern.console.FrontendConfig
import io.joern.jssrc2cpg.JsSrc2Cpg
import io.shiftleft.codepropertygraph.Cpg

import java.nio.file.Path
import scala.util.Try

case class JsSrcCpgGenerator(config: FrontendConfig, rootPath: Path) extends CpgGenerator {
  private lazy val command: Path = if (isWin) rootPath.resolve("jssrc2cpg.bat") else rootPath.resolve("jssrc2cpg.sh")

  /** Generate a CPG for the given input path. Returns the output path, or None, if no CPG was generated.
    */
  override def generate(inputPath: String, outputPath: String = "cpg.bin.zip"): Try[String] = {
    val arguments = Seq(inputPath, "--output", outputPath) ++ config.cmdLineParams
    runShellCommand(command.toString, arguments).map(_ => outputPath)
  }

  override def isAvailable: Boolean =
    command.toFile.exists

  override def applyPostProcessingPasses(cpg: Cpg): Cpg = {
    JsSrc2Cpg.postProcessingPasses(cpg).foreach(_.createAndApply())
    cpg
  }

  override def isJvmBased = true
}
