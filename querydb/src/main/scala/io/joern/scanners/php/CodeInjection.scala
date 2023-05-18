package io.joern.scanners.php

import io.joern.console._
import io.joern.dataflowengineoss.language._
import io.joern.dataflowengineoss.queryengine.EngineContext
import io.joern.macros.QueryMacros._
import io.joern.scanners._
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.semanticcpg.language._

object CodeInjection extends QueryBundle {

  implicit val resolver: ICallResolver = NoResolve

  @q
  def CodeInjection()(implicit context: EngineContext): Query =
    Query.make(
      name = "php-code-injection",
      author = Crew.dsp,
      title = "Code Injection vulnerability.",
      description = """
          |An attacker controlled parameter is used in an insecure `shell-exec` call.
          |
          |If the parameter is not validated and sanitized, this is a remote code execution.
          |""".stripMargin,
      score = 14,
      withStrRep({ cpg =>
        // $_REQUEST["foo"], $_GET["foo"], $_POST["foo"]
        // are identifier (at the moment)

      implicit val attack_san_functions: List[String] = Constants.san_functions_code

      def source = cpg.call.name(Operators.assignment).argument.code(Constants.attacker_input) 

      def sink = cpg.call.name("eval").argument.filterNot(SanitizationFilter.isSanitized)

      sink.reachableBy(source).l ::: sink.repeat(_.method.callIn.argument.filterNot(SanitizationFilter.isSanitized))(_.until(_.reachableBy(source))).l

      }),

      tags = List(QueryTags.remoteCodeExecution, QueryTags.default)
    )
}
