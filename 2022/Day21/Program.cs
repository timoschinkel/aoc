using System.Diagnostics;
using System.Reflection;
using System.Text.RegularExpressions;

namespace Day21;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");
        bool EXAMPLE = args.Contains("example");

        string[] input = EXAMPLE
            ? File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/example.txt")
            : File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        // Part 1; I don't know if this will bite me in the ass for part 2, but this was in my opinion the easiest
        // puzzle of this year so far. I use recursion to find the outcome for root; Every step I check the monkey's
        // operation. If it is a mathematical operation I perform it using the outcome of Resolve() on both sides of 
        // the equation. This will result in an answer pretty fast.
        //
        // Part 2; I started out by not building the equation. From visual judgement I was able to see that the equation
        // can be simplified by finding all occurrences of (<number> <operand> <number>) and solving them. That showed
        // that the right side was a fixed number. Next step is to try and find a simplified equation for x by applying
        // some algebra. I am lucky that all divisions are without floating points :)
        //
        // Post mortem; I have resorted to using string operations like a proper PHP developer. Maybe it would have been
        // better to build up a tree structure and iterate from the leaves to the root.
        
        // Read input
        Dictionary<string, string> monkeys = input
            .Select(line => line.Split(": "))
            .ToDictionary(parts => parts[0], parts => parts[1]);

        var timer = new Stopwatch();
        timer.Start();
        var score = Resolve("root");
        timer.Stop();
        Console.WriteLine($"What number will the monkey named root yell? {score} ({timer.ElapsedMilliseconds}ms)");

        // Part 2
        timer.Start();
        var equation = Interpret("root");
        if (DEBUG) Console.WriteLine($"Equation: {equation}");
        if (DEBUG) Console.WriteLine("");
        
        // try to simplify the equation, so look for x (+|-|*\/) y occurrences and solve it
        var matches = Regex.Matches(equation, @"\((?<left>\-?[0-9]+) (?<operand>\+|\-|\/|\*) (?<right>\-?[0-9]+)\)");
        while (matches.Count > 0)
        {
            foreach (Match match in matches)
            {
                var l = long.Parse(match.Groups["left"].Value);
                var oper = match.Groups["operand"].Value;
                var r = long.Parse(match.Groups["right"].Value);

                long result = 0;
                switch (oper)
                {
                    case "+":
                        result = l + r;
                        break;
                    case "-":
                        result = l - r;
                        break;
                    case "*":
                        result = l * r;
                        break;
                    case "/":
                        result = l / r;
                        break;
                    default:
                        throw new Exception("This should never happen");
                    
                }
                
                equation = equation.Replace($"({match.Groups["left"].Value} {oper} {match.Groups["right"].Value})", $"{result}");
            }
            
            matches = Regex.Matches(equation, @"\((?<left>[0-9]+) (?<operand>\+|\-|\/|\*) (?<right>[0-9]+)\)");
        }

        if (DEBUG) Console.WriteLine($"Simplified equation: {equation}");
        if (DEBUG) Console.WriteLine("");
        
        // Try to solve the equation, assuming the right side of the equation is a single value (like the example and
        // input have).

        // <remainder> should still be wrapped in ()
        var matchOne = Regex.Match(equation, @"^\((?<remainder>.*?) (?<operand>\+|\/|\-|\*) (?<value>\d+)\) = (?<result>\d+)$"); // (<remainder> <operand> <value>) = <result>
        var matchTwo = Regex.Match(equation, @"^\((?<value>\d+) (?<operand>\+|\/|\-|\*) (?<remainder>.*?)\) = (?<result>\d+)$"); // (<value> <operand> <remainder>) = <result>

        while (matchOne.Success || matchTwo.Success)
        {
            if (matchOne.Success)
            {
                // (<remainder> <operand> <value>) = <result>
                long result = long.Parse(matchOne.Groups["result"].Value);
                string operand = matchOne.Groups["operand"].Value;
                long value = long.Parse(matchOne.Groups["value"].Value);
                string remainder = matchOne.Groups["remainder"].Value;

                switch (operand)
                {
                    case "+":
                        equation = $"{remainder} = {result - value}";
                        break;
                    case "-":
                        equation = $"{remainder} = {result + value}";
                        break;
                    case "*":
                        equation = $"{remainder} = {result / value}"; // todo
                        break;
                    case "/":
                        equation = $"{remainder} = {value * result}"; // todo
                        break;
                    default:
                        throw new Exception("This should not happen!");
                }
            }
            else if (matchTwo.Success)
            {
                // (<value> <operand> <remainder>) = <result>
                long result = long.Parse(matchTwo.Groups["result"].Value);
                string operand = matchTwo.Groups["operand"].Value;
                long value = long.Parse(matchTwo.Groups["value"].Value);
                string remainder = matchTwo.Groups["remainder"].Value;
                
                switch (operand)
                {
                    case "+":
                        equation = $"{remainder} = {result - value}";
                        break;
                    case "-":
                        equation = $"{remainder} = {(result - value) * -1}";
                        break;
                    case "*":
                        equation = $"{remainder} = {result / value}"; // todo
                        break;
                    case "/":
                        equation = $"{remainder} = {value / result}"; // todo
                        break;
                    default:
                        throw new Exception("This should not happen!");
                }
            }
            
            if (DEBUG) Console.WriteLine($"Equation: {equation}");
            if (DEBUG) Console.WriteLine("");
            
            matchOne = Regex.Match(equation, @"^\((?<remainder>.*?) (?<operand>\+|\/|\-|\*) (?<value>\d+)\) = (?<result>\-?\d+)$"); // (<remainder> <operand> <value>) = <result>
            matchTwo = Regex.Match(equation, @"^\((?<value>\d+) (?<operand>\+|\/|\-|\*) (?<remainder>.*?)\) = (?<result>\-?\d+)$"); // (<value> <operand> <remainder>) = <result>
        }

        if (DEBUG) Console.WriteLine($"Equation: {equation}");
        if (DEBUG) Console.WriteLine("");
        
        long humn = long.Parse(equation.Replace("x = ", ""));
        timer.Stop();
        
        Console.WriteLine($"What number do you yell to pass root's equality test? {humn} ({timer.ElapsedMilliseconds}ms)");
        
        // Let's try it:
        // monkeys["root"] = monkeys["root"].Replace(" + ", " = ");
        // monkeys["humn"] = $"{humn}";
        //
        // Console.WriteLine(Resolve("root"));
        
        long Resolve(string monkey)
        {
            var yell = monkeys[monkey];
            if (yell.Contains("="))
            {
                var ms = yell.Split(" = ");

                var left = Resolve(ms[0]);
                var right = Resolve(ms[1]);
                
                Console.WriteLine($"{left} = {right}");
                Console.WriteLine(long.MaxValue);
                return 0;
            }
            if (yell.Contains("+"))
            {
                var ms = yell.Split(" + ");
                return Resolve(ms[0]) + Resolve(ms[1]);
            }
            if (yell.Contains(" - ")) // check for " - " to prevent false positives for negative numbers
            {
                var ms = yell.Split(" - ");
                return Resolve(ms[0]) - Resolve(ms[1]);
            }
            if (yell.Contains("*"))
            {
                var ms = yell.Split(" * ");
                return Resolve(ms[0]) * Resolve(ms[1]);
            } 
            if (yell.Contains("/"))
            {
                var ms = yell.Split(" / ");
                return Resolve(ms[0]) / Resolve(ms[1]);
            }
            
            return long.Parse(yell);
        }

        string Interpret(string monkey)
        {
            // Hard-code, because both example and input use a + operation
            var yell = monkey == "root" ? monkeys[monkey].Replace(" + ", " = ") : monkeys[monkey];

            if (monkey == "humn")
            {
                return "x";
            }

            var operation = "";
            if (yell.Contains("=")) operation = "=";
            else if (yell.Contains("+")) operation = "+";
            else if (yell.Contains("-")) operation = "-";
            else if (yell.Contains("/")) operation = "/";
            else if (yell.Contains("*")) operation = "*";

            if (operation == "")
            {
                return yell;
            }
             
            var parts = yell.Split($" {operation} ");

            return monkey != "root" 
                ? $"({Interpret(parts[0])} {operation} {Interpret(parts[1])})"
                : $"{Interpret(parts[0])} {operation} {Interpret(parts[1])}";
        }
    }
}