package io.joern.rubysrc2cpg.passes

import better.files.File
import io.joern.rubysrc2cpg.{Config, RubySrc2Cpg}
import io.shiftleft.codepropertygraph.generated.Languages
import io.shiftleft.semanticcpg.language._
import org.scalatest.matchers.should.Matchers
import org.scalatest.wordspec.AnyWordSpec

class MetaDataPassTests extends AnyWordSpec with Matchers {

  "MetaDataPass" should {

    "create a metadata node with correct language" in {
      File.usingTemporaryDirectory("rubysrc2cpgTest") { dir =>
        val config = Config(inputPath = dir.pathAsString, outputPath = dir.pathAsString)
        val cpg    = new RubySrc2Cpg().createCpg(config).get
        cpg.metaData.language.l shouldBe List(Languages.RUBYSRC)
      }
    }
  }
}
