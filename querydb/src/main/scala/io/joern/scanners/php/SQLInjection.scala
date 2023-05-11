package io.joern.scanners.php

import io.joern.console._
import io.joern.dataflowengineoss.language._
import io.joern.dataflowengineoss.queryengine.EngineContext
import io.joern.macros.QueryMacros._
import io.joern.scanners._
import io.shiftleft.codepropertygraph.generated.Operators
import io.shiftleft.semanticcpg.language._

object SQLInjection extends QueryBundle {

  implicit val resolver: ICallResolver = NoResolve

  @q
  def SQLInjection()(implicit context: EngineContext): Query =
    Query.make(
      name = "php-sql-injection",
      author = Crew.dsp,
      title = "SQL injection: A parameter is used in an insecure database API call.",
      description = """
          |An attacker controlled parameter is used in an insecure database API call.
          |
          |If the parameter is not validated and sanitized, this is a SQL injection.
          |""".stripMargin,
      score = 11,
      withStrRep({ cpg =>
        // $_REQUEST["foo"], $_GET["foo"], $_POST["foo"]
        // are identifier (at the moment)
      def source = cpg.call.name(Operators.assignment).argument.code(".*_(REQUEST|GET|POST|ENV|COOKIE|SERVER).*") 

      def sink = cpg.call.name(".*(mysql_query|mysqli_query|pg_query|sqlite_query|query).*").argument
	    // sink.reachableBy(source).l 
      // }),

      def path = sink.reachableByFlows(source)

      var sanitized = false
     
      for (c <- path.head.elements.isCall.name) {if (SanFuncs.san_functions_sql.contains(c) || SanFuncs.san_functions_all.contains(c))  {sanitized = true}}

      if(!sanitized) {sink.reachableBy(source).l} else {overflowdb.traversal.Traversal()}
      }),

      tags = List(QueryTags.remoteCodeExecution, QueryTags.default)
      )
}
