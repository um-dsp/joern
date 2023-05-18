package io.joern.scanners.php

import io.joern.console._
import io.joern.dataflowengineoss.language._
import io.joern.dataflowengineoss.queryengine.EngineContext
import io.joern.macros.QueryMacros._
import io.joern.scanners._
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.semanticcpg.language._

object ExecutionAfterRedirect extends QueryBundle {

  implicit val resolver: ICallResolver = NoResolve

  @q
  def ExecutionAfterRedirect()(implicit context: EngineContext): Query =
    Query.make(
      name = "php-ear-vulnerability",
      author = Crew.dsp,
      title = "EAR  vulnerability.",
      description = """
          |Execution after redirect vulnerability.
          
          |""".stripMargin,
      score = 12,
      withStrRep({ cpg =>
        // $_REQUEST["foo"], $_GET["foo"], $_POST["foo"]
        // are identifier (at the moment)
      implicit val attack_san_functions: List[String] = List()

      def source = cpg.call.name(Operators.assignment).argument.code(Constants.attacker_input) 

      def sink = cpg.call("header").filter(_.code.contains("Location")).argument.filterNot(SanitizationFilter.isSanitized)

      sink.reachableBy(source).l ::: sink.repeat(_.method.callIn.argument.filterNot(SanitizationFilter.isSanitized))(_.until(_.reachableBy(source))).l

      }),
      tags = List(QueryTags.remoteCodeExecution, QueryTags.default)
    )
}
