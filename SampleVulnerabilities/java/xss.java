import java.io.IOException;
import java.io.PrintWriter;
import javax.servlet.ServletException;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

public class MyServlet extends HttpServlet {
    protected void doGet(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        String paramValue = request.getParameter("param");

        response.setContentType("text/html");
        PrintWriter out = response.getWriter();

        out.println("<html>");
        out.println("<head><title>Parameter Value</title></head>");
        out.println("<body>");
        out.println("<h1>Parameter Value:</h1>");
        out.println("<p>" + paramValue + "</p>");
        out.println("</body>");
        out.println("</html>");

        out.close();
    }
}

