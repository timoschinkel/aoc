namespace Day10;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");

        string[] input = File.ReadAllLines(@"./input.txt");
        
        // Part 1 is about reading the puzzle properly imo. Check the instruction for every cycle. When a noop is 
        // encountered all we do is increase the cycle with one AND perform the check if we need to increase the score.
        // When an `addx` instruction is encountered we increase the cycle once, check if we need to increase the 
        // score, and increase the cycle again AND check if we need to increase the score AGAIN.
        
        int x = 1;
        int score = 0;
        
        var cycle = 0;
        foreach (var instruction in input)
        {
            cycle++;

            // start of cycle:
            if (instruction.Equals("noop"))
            {
                if ((cycle - 20) % 40 == 0)
                {
                    if (DEBUG) Console.WriteLine($"During cycle {cycle} the value of x is {x}, which means a signal strength of {x * cycle}");
                    score += x * cycle;
                }
                continue;
            }
            
            if ((cycle - 20) % 40 == 0)
            {
                if (DEBUG) Console.WriteLine($"During cycle {cycle} the value of x is {x}, which means a signal strength of {x * cycle}");
                score += x * cycle;
            }
            cycle++; // add a cycle
            if ((cycle - 20) % 40 == 0)
            {
                if (DEBUG) Console.WriteLine($"During cycle {cycle} the value of x is {x}, which means a signal strength of {x * cycle}");
                score += x * cycle;
            }
            
            // end of cycle
            int increase = Int32.Parse(instruction.Replace("addx ", ""));
            x += increase;
        }
        
        Console.WriteLine($"What is the sum of these six signal strengths? {score}");
    }
}
