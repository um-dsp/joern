package io.joern.scanners.php

import io.joern.console._
import io.joern.dataflowengineoss.language._
import io.joern.dataflowengineoss.queryengine.EngineContext
import io.joern.macros.QueryMacros._
import io.joern.scanners._
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.semanticcpg.language._

object FileInclusion extends QueryBundle {

  implicit val resolver: ICallResolver = NoResolve

  @q
  def FileInclusion()(implicit context: EngineContext): Query =
    Query.make(
      name = "fphp-file-inclusion",
      author = Crew.dsp,
      title = "File inclusion vulnerability.",
      description = """
          |An attacker controlled parameter is used in file inclusion command.
          |
          |If the parameter is not validated and sanitized, this is a remote code execution.
          |""".stripMargin,
      score = 12,
      withStrRep({ cpg =>
        // $_REQUEST["foo"], $_GET["foo"], $_POST["foo"]
        // are identifier (at the moment)
	    def source = cpg.call.name(Operators.assignment).argument.code(".*_(REQUEST|GET|POST|ENV|COOKIE|SERVER).*") 

      def sink = cpg.call.code(".*(include|require|include_once|require_once).*").argument.filterNot(SanitizationFilter.isSanitized)

      sink.reachableBy(source).l ::: sink.repeat(_.method.callIn.argument.filterNot(SanitizationFilter.isSanitized))(_.until(_.reachableBy(source))).l
      }),
      tags = List(QueryTags.remoteCodeExecution, QueryTags.default)
    )
}