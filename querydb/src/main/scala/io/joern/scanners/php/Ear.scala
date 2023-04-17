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
      name = "file-inclusion",
      author = Crew.niko,
      title = "EAR  vulnerability.",
      description = """
          |Execution after redirect vulnerability.
          
          |""".stripMargin,
      score = 12,
      withStrRep({ cpg =>
        // $_REQUEST["foo"], $_GET["foo"], $_POST["foo"]
        // are identifier (at the moment)
        def source =
          cpg.call.name(Operators.assignment).argument.code(".*_(REQUEST|GET|POST).*")
        // extracts all eval, include and require statements and check that their arguments 
        // dynamically evaluated at runtime 
        // eval('Heloo')  X
        // eval ($code)   V
        def sink = cpg.call.code(".*(include|require|include_once|require_once).*").argument.isIdentifier

        sink.reachableBy(source).l 
      }),
      tags = List(QueryTags.remoteCodeExecution, QueryTags.default)
    )
}
