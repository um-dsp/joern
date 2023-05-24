package io.joern.scanners.php

import io.joern.console._
import io.joern.dataflowengineoss.language._
import io.joern.dataflowengineoss.queryengine.EngineContext
import io.joern.macros.QueryMacros._
import io.joern.scanners._
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.semanticcpg.language._

object CommandExec extends QueryBundle {

  implicit val resolver: ICallResolver = NoResolve

  @q
  def CommandExec()(implicit context: EngineContext): Query =
    Query.make(
      name = "php-command-exec",
      author = Crew.dsp,
      title = "Command exec: A parameter is used in an insecure command call.",
      description = """
          |An attacker controlled parameter is used in an insecure OS command call.
          |
          |If the parameter is not validated and sanitized, this is a remote code execution.
          |""".stripMargin,
      score = 13,
      withStrRep({ cpg =>
        // $_REQUEST["foo"], $_GET["foo"], $_POST["foo"]
        // are identifier (at the moment)

      implicit val attack_san_functions: List[String] = Constants.san_functions_os_command

      def source = cpg.call.name(Operators.assignment).argument.code(Constants.attacker_input) 

      def sink = cpg.call.name("shell_exec|exec|system|mail|popen|expect_popen|passthru|pcntl_exec|proc_opend|backticks").argument.filterNot(SanitizationFilter.isSanitized(_))
	    
      sink.reachableBy(source).l ::: sink.repeat(_.method.callIn.argument.filterNot(SanitizationFilter.isSanitized(_)))(_.until(_.reachableBy(source))).l

      }),

      tags = List(QueryTags.remoteCodeExecution, QueryTags.default)
    )
}