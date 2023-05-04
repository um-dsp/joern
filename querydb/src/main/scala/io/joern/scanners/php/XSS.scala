package io.joern.scanners.php

import io.joern.console._
import io.joern.dataflowengineoss.language._
import io.joern.dataflowengineoss.queryengine.EngineContext
import io.joern.macros.QueryMacros._
import io.joern.scanners._
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.semanticcpg.language._

object XSS extends QueryBundle {

  implicit val resolver: ICallResolver = NoResolve

  @q
  def XSS()(implicit context: EngineContext): Query =
    Query.make(
      name = "php-xss",
      author = Crew.dsp,
      title = "Cross site scripting vulnerability.",
      description = """
          |An attacker controlled parameter is used in an insecure echo | print  call.
          |
          |If the parameter is not validated and sanitized, this is a remote code execution.
          |""".stripMargin,
      score = 10,
      withStrRep({ cpg =>
        // $_REQUEST["foo"], $_GET["foo"], $_POST["foo"]
        // are identifier (at the moment)
      def source = 
          cpg.call.name(Operators.assignment).argument.code(".*_(REQUEST|GET|POST|ENV|COOKIE|SERVER).*") 


      def sink = cpg.call.name("print|echo|printf").argument

	    def path = sink.reachableByFlows(source)

      var sanitized = false
     
      for (c <- path.head.elements.isCall.name) {if (SanFuncs.san_functions_xss.contains(c) || SanFuncs.san_functions_all.contains(c))  {sanitized = true}}

      if(!sanitized) {sink.reachableBy(source)}
      }),

      tags = List(QueryTags.remoteCodeExecution, QueryTags.default)
    )
}
