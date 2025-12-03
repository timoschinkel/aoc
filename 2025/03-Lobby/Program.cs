using System.Diagnostics;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input
var banks = input.Select(line => line.ToArray().Select(s => int.Parse(s.ToString())).ToArray());

// Part 01
var sw = Stopwatch.StartNew();
// perform calculation
var one = PartOne(banks);
sw.Stop();
PrintElapsedTime($"What is the total output joltage? {one} ([ELAPSED])", sw.Elapsed);

// Part 02
sw = Stopwatch.StartNew();
// perform calculation
var two = PartTwo(banks);
sw.Stop();
PrintElapsedTime($"What is the new total output joltage? {two} ([ELAPSED])", sw.Elapsed);

long PartOne(IEnumerable<int[]> banks)
{
    long joltage = 0;
    foreach (var bank in banks)
    {
        joltage += CalculateLargestJoltage(bank);
    }
    return joltage;
}

long CalculateLargestJoltage(int[] bank)
{
    long max = 0;
    for (int left = 0; left < bank.Length - 1; left++)
    {
        for (int right = left + 1; right < bank.Length; right++)
        {
            max = Math.Max(max, bank[left] * 10 + bank[right]);
            
        }
    }
    return max;
}

long PartTwo(IEnumerable<int[]> banks)
{
    return 0;
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
