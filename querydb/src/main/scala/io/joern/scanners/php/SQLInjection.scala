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
      
      SanitizationFilter.set_san_functions(SanFuncs.san_functions_sql)

      def source = cpg.call.name(Operators.assignment).argument.code(".*_(REQUEST|GET|POST|ENV|COOKIE|SERVER).*") 

      def sink = cpg.call.name(".*(mysql_query|mysqli_query|pg_query|sqlite_query|query).*").argument.filterNot(SanitizationFilter.isSanitized)
	    
      
      sink.reachableBy(source).l ::: sink.repeat(_.method.callIn.argument.filterNot(SanitizationFilter.isSanitized))(_.until(_.reachableBy(source))).l

      }),

      tags = List(QueryTags.sqlInjection, QueryTags.default)
      )
}
