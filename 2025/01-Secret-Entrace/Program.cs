using System.Diagnostics;
using _01_Secret_Entrace;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input
Rotation[] instructions = input
    .Select(line =>
    {
    return new Rotation(
        line.Substring(0, 1),
        int.Parse(line.Substring(1, line.Length - 1))
    );
}).ToArray();

// Part 01
var sw = Stopwatch.StartNew();
// perform calculation
int one = PartOne(instructions);
sw.Stop();
PrintElapsedTime($"Using password method 0x434C49434B, what is the password to open the door? {one} ([ELAPSED])", sw.Elapsed);

// Part 02
sw = Stopwatch.StartNew();
// perform calculation
sw.Stop();
PrintElapsedTime($"The answer is: {0} ([ELAPSED])", sw.Elapsed);

int PartOne(Rotation[] instructions)
{
    int position = 50;
    int zeros = 0;
    foreach (var instruction in instructions)
    {
        if (instruction.Direction == "L")
        {
            position -= (instruction.Clicks % 100);
            if (position < 0)
            {
                position += 100;
            }
        }
        else // assume R
        {
            position = (position + instruction.Clicks) % 100;   
        }

        Console.WriteLine($"{instruction.Direction}{instruction.Clicks}: {position}");
        
        if (position == 0)
        {
            zeros++;
        }
    }

    return zeros;
}

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
