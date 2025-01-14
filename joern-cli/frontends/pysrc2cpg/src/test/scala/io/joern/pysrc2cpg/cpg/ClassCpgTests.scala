package io.joern.pysrc2cpg.cpg

import io.joern.pysrc2cpg.PySrc2CpgFixture
import io.shiftleft.semanticcpg.language._

class ClassCpgTests extends PySrc2CpgFixture(withOssDataflow = false) {
  "class" should {
    val cpg = code("""class Foo:
        |  pass
        |""".stripMargin)
    "have correct instance class type and typeDecl" in {
      cpg.typ.name("Foo").fullName.head shouldBe "Test0.py:<module>.Foo"
      val typeDecl = cpg.typeDecl.name("Foo").head
      typeDecl.fullName shouldBe "Test0.py:<module>.Foo"
      typeDecl.astParent.head shouldBe cpg.method.name("<module>").head
    }

    "have correct meta class type and typeDecl" in {
      cpg.typ.name("Foo<meta>").fullName.head shouldBe "Test0.py:<module>.Foo<meta>"
      val typeDecl = cpg.typeDecl.name("Foo<meta>").head
      typeDecl.fullName shouldBe "Test0.py:<module>.Foo<meta>"
      typeDecl.astParent.head shouldBe cpg.method.name("<module>").head
    }

    "have correct meta class call handler type and typeDecl" in {
      cpg.typ.name("<metaClassCallHandler>").fullName.head shouldBe
        "Test0.py:<module>.Foo.<metaClassCallHandler>"
      val typeDecl = cpg.typeDecl.name("<metaClassCallHandler>").head
      typeDecl.fullName shouldBe "Test0.py:<module>.Foo.<metaClassCallHandler>"
      typeDecl.astParent.head shouldBe cpg.typeDecl.name("Foo<meta>").head
    }

    "have correct fake new type and typeDecl" in {
      cpg.typ.name("<fakeNew>").fullName.head shouldBe
        "Test0.py:<module>.Foo.<fakeNew>"
      val typeDecl = cpg.typeDecl.name("<fakeNew>").head
      typeDecl.fullName shouldBe "Test0.py:<module>.Foo.<fakeNew>"
      typeDecl.astParent.head shouldBe cpg.typeDecl.name("Foo<meta>").head
    }
  }

  "class meta call handler" should {
    "have no self parameter if self is explicit" in {
      val cpg = code("""class Foo:
          |  def __init__(self, x):
          |    pass
          |""".stripMargin)

      val handlerMethod = cpg.method.name("<metaClassCallHandler>").head
      handlerMethod.fullName shouldBe "Test0.py:<module>.Foo.<metaClassCallHandler>"
      handlerMethod.lineNumber shouldBe Some(2)

      handlerMethod.parameter.size shouldBe 1
      val xParameter = handlerMethod.parameter.head
      xParameter.name shouldBe "x"

    }

    "have no self parameter if self is in varargs" in {
      val cpg = code("""class Foo:
          |  def __init__(*x):
          |    pass
          |""".stripMargin)

      val handlerMethod = cpg.method.name("<metaClassCallHandler>").head
      handlerMethod.fullName shouldBe "Test0.py:<module>.Foo.<metaClassCallHandler>"
      handlerMethod.lineNumber shouldBe Some(2)

      handlerMethod.parameter.size shouldBe 1
      val xParameter = handlerMethod.parameter.head
      xParameter.name shouldBe "x"

    }
  }

}
