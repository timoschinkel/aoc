using System.Diagnostics;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input

// Part 01
var sw = Stopwatch.StartNew();
// perform calculation
sw.Stop();
PrintElapsedTime($"The answer is: {0} ([ELAPSED])", sw.Elapsed);

// Part 02
sw = Stopwatch.StartNew();
// perform calculation
sw.Stop();
PrintElapsedTime($"The answer is: {0} ([ELAPSED])", sw.Elapsed);

void PrintElapsedTime(string message, TimeSpan ts)
{
    var ns = ts.TotalNanoseconds;
    var parts = message.Split("[ELAPSED]");
    for (var i = 0; i < parts.Length; i++)
    {
        Console.Write(parts[i]);
        if (i < parts.Length - 1)
        {
            if (ns > 1000000000)
            {
                Console.ForegroundColor = ConsoleColor.Red;
                Console.Write($"{Math.Round(ns / 1000000000, 2)}s");
            }
            else if (ns > 1000000)
            {
                Console.ForegroundColor = ConsoleColor.Yellow;
                Console.Write($"{Math.Ceiling(ns / 1000000)}ms");
            }
            else if (ns > 1000)
            {
                Console.ForegroundColor = ConsoleColor.Green;
                Console.Write($"{Math.Ceiling(ns / 1000)}μs");
            }
            else
            {
                Console.ForegroundColor = ConsoleColor.Green;
                Console.Write($"{ns}ns");
            }
            
            Console.ResetColor();
        }
    }
    
    Console.WriteLine("");
}
