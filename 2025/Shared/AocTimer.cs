using System.Diagnostics;

namespace Shared;

public class AocTimer
{
    private Stopwatch sw;
    
    public AocTimer()
    {
        sw = new Stopwatch();
        sw.Start();
    }

    public void Duration(string message)
    {
        var ns = sw.Elapsed.TotalNanoseconds;
        
        Console.Write($"{message} (");
        if (ns > 1000000000)
        {
            Console.ForegroundColor = ConsoleColor.Red;
            Console.Write($"{Math.Round(ns / 1000000000, 2)}s");
        }
        else if (ns > 1000000)
        {
            Console.ForegroundColor = ConsoleColor.Yellow;
            Console.Write($"{Math.Round(ns / 1000000, 2)}ms");
        }
        else if (ns > 1000)
        {
            Console.ForegroundColor = ConsoleColor.Green;
            Console.Write($"{Math.Round(ns / 1000, 2)}μs");
        }
        else
        {
            Console.ForegroundColor = ConsoleColor.Green;
            Console.Write($"{ns}ns");
        }
    
        Console.ResetColor();
        Console.WriteLine(")");
    }
}