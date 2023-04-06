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
      name = "php-Code-injection",
      author = Crew.niko,
      title = "Code Injection vulnerability.",
      description = """
          |An attacker controlled parameter is used in an insecure `shell-exec` call.
          |
          |If the parameter is not validated and sanitized, this is a remote code execution.
          |""".stripMargin,
      score = 5,
      withStrRep({ cpg =>
        // $_REQUEST["foo"], $_GET["foo"], $_POST["foo"]
        // are identifier (at the moment)
        def source =
          cpg.call.name(Operators.assignment).argument.code(".*_(REQUEST|GET|POST).*")

        def sink = cpg.call.name("eval|include|require").argument

        sink.reachableBy(source).l
      }),
      tags = List(QueryTags.remoteCodeExecution, QueryTags.default)
    )
}
