package io.joern.kotlin2cpg.querying

import io.joern.kotlin2cpg.Kt2CpgTestContext
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.proto.cpg.Cpg.DispatchTypes
import io.shiftleft.semanticcpg.language._
import org.scalatest.freespec.AnyFreeSpec
import org.scalatest.matchers.should.Matchers

class ArrayAccessExprsTests extends AnyFreeSpec with Matchers {

  "CPG for code with simple map construction and access" - {
    lazy val cpg = Kt2CpgTestContext.buildCpg("""
        |package mypkg
        |
        |fun main(args: Array<String>) {
        |    val foo = mapOf("one" to 1, "two" to 2, "three" to 3)
        |    val bar = foo["one"]
        |    println(bar)
        |}
        |""".stripMargin)

    "should contain a CALL node for `foo[\"one\"]` with the correct properties set" in {
      val List(c) = cpg.call.code("val bar.*").argument.isCall.l
      c.code shouldBe "foo[\"one\"]"
      c.methodFullName shouldBe Operators.indexAccess
      c.dispatchType shouldBe DispatchTypes.STATIC_DISPATCH.toString
      c.lineNumber shouldBe Some(5)
      c.columnNumber shouldBe Some(14)
    }
  }

  "CPG for code with simple array construction and access" - {
    lazy val cpg = Kt2CpgTestContext.buildCpg("""
        |package mypkg
        |
        |fun main(args: Array<String>) {
        |    val foo = listOf(1, 2, 3)
        |    val bar = foo[1]
        |    println(foo)
        |}
        |""".stripMargin)

    "should contain a CALL node for `map[\"one\"]` with the correct properties set" in {
      val List(c) = cpg.call.code("val bar.*").argument.isCall.l
      c.code shouldBe "foo[1]"
      c.methodFullName shouldBe Operators.indexAccess
      c.dispatchType shouldBe DispatchTypes.STATIC_DISPATCH.toString
      c.lineNumber shouldBe Some(5)
      c.columnNumber shouldBe Some(14)
    }
  }
}