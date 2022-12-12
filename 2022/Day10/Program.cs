using System.Reflection;

namespace Day10;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");

        string[] input = File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");
        
        // Part 1 is about reading the puzzle properly imo. Check the instruction for every cycle. When a noop is 
        // encountered all we do is increase the cycle with one AND perform the check if we need to increase the score.
        // When an `addx` instruction is encountered we increase the cycle once, check if we need to increase the 
        // score, and increase the cycle again AND check if we need to increase the score AGAIN.
        
        int x = 1;
        int score = 0;

        // In part 2 the most tricky part was switching from one based cycles to zero based CRT pixels. *During* every
        // cycle - at the same moments the checks for part 1 are performed - check if the current cycle falls within the
        // range indicated by the value of X. The trick is to take the modulo of the cycle to determine the row of the
        // CRT display.
        
        // Post mortem; The puzzle text uses characters `.` and `#` for "off" and "on" respectively. On Reddit I found
        // the suggestion to use ⬜ and ⬛ for "off" and "on". This will show a better result when the output of the 
        // code is not shown using a monospaced font. What I have noticed before is that the display of special unicode
        // characters in the built-in terminal of Rider is not optimal. Running the application on the Terminal app on
        // MacOS gave me a much better visible result.
        // And yes, I could have moved the checks for part 1 and part 2 to a method, but this worked :)
        
        int width = 40;
        int height = 6;
        string[] crt = new string[width * height];
        
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
                
                // draw pixel
                crt[cycle - 1] = (cycle - 1) % 40 >= x - 1 && (cycle - 1) % 40 <= x + 1 ? "⬛" : "⬜";
                
                continue;
            }
            
            if ((cycle - 20) % 40 == 0)
            {
                if (DEBUG) Console.WriteLine($"During cycle {cycle} the value of x is {x}, which means a signal strength of {x * cycle}");
                score += x * cycle;
            }
            
            // draw pixel 
            crt[cycle - 1] = (cycle - 1) % 40 >= x - 1 && (cycle - 1) % 40 <= x + 1 ? "⬛" : "⬜";
            
            cycle++; // add a cycle
            if ((cycle - 20) % 40 == 0)
            {
                if (DEBUG) Console.WriteLine($"During cycle {cycle} the value of x is {x}, which means a signal strength of {x * cycle}");
                score += x * cycle;
            }
            
            // draw pixel
            crt[cycle - 1] = (cycle - 1) % 40 >= x - 1 && (cycle - 1) % 40 <= x + 1 ? "⬛" : "⬜";
            
            // end of cycle
            int increase = Int32.Parse(instruction.Replace("addx ", ""));
            x += increase;
        }
        
        Console.WriteLine($"What is the sum of these six signal strengths? {score}");
        
        Console.WriteLine("What eight capital letters appear on your CRT?");
        for (var row = 0; row < height; row++)
        {
            for (var column = 0; column < width; column++)
            {
                Console.Write(crt[row * width + column]);
            }
            Console.WriteLine("");
        }
    }
}
