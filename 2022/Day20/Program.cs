using System.Diagnostics;
using System.Reflection;

namespace Day20;

class Program
{
    public static void Main(string[] args)
    {
        bool DEBUG = args.Contains("debug");
        bool EXAMPLE = args.Contains("example");

        string[] input = EXAMPLE
            ? File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/example.txt")
            : File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        var timer = new Stopwatch();
        
        // Read input
        List<(int id, long value)> numbers = new();
        for (var id = 0; id < input.Length; id++)
        {
            numbers.Add((id, value: long.Parse(input[id])));
        }
        
        // Part 1; "The list is circular, so moving a number off one end of the list wraps back around to the other end
        // as if the ends were connected." A few tricks are needed to solve this; 
        // - Apply the operation and perform a modulo (num of numbers - 1)
        // - The modulo operation of C# is NOT a modulus, but a remainder
        // - Because it is circular an additional action to loop around is needed to match the example
        // 
        // Part 2; The easy, but CPU intensive approach - everything for a star - was my approach. I changed the value 
        // from an integer - in c# 32-bit - to a long - in c# 64-bit. Reread the input, multiply with the decryption key
        // and run the same operation as part 1, but now we run it 10 times.
        
        if (DEBUG) Console.WriteLine("Initial arrangement:");
        Print();
        
        // Part 1
        timer.Start();

        var modulo = numbers.Count - 1;
        for (int i = 0; i < numbers.Count; i++)
        {
            var index = numbers.FindIndex(number => number.id == i);
            var current = numbers[index];
            var operation = current.value;
            var newIndex = (int)(((index + operation) % modulo + modulo) % modulo);

            // The operations below are purely to have the Print() function show the same result as the examples
            if (newIndex == 0) newIndex = numbers.Count - 1;
            else if (newIndex == numbers.Count - 1) newIndex = 0;

            if (DEBUG) Console.WriteLine($"Moving {operation} from {index} to {newIndex}");
            
            // Remove current from numbers:
            numbers.RemoveAt(index);

            // Reinsert
            numbers = numbers.Take(newIndex).Append(current).Concat(numbers.Skip(newIndex)).ToList();

            Print();
        }
        
        // Find 0
        var zero = numbers.FindIndex(number => number.value == 0);
        var positionOfOneThousand = (zero + 1000) % numbers.Count;
        var positionOfTwoThousand = (zero + 2000) % numbers.Count;
        var positionOfThreeThousand = (zero + 3000) % numbers.Count;
        
        timer.Stop();
        Console.WriteLine($"What is the sum of the three numbers that form the grove coordinates? {numbers[positionOfOneThousand].value + numbers[positionOfTwoThousand].value + numbers[positionOfThreeThousand].value} ({timer.ElapsedMilliseconds}ms)");
        
        // Part 2
        
        // Reread input with the decryption key
        long decryptionKey = 811589153;
        numbers.Clear();
        for (var id = 0; id < input.Length; id++)
        {
            numbers.Add((id, value: long.Parse(input[id]) * decryptionKey));
        }
        
        timer.Restart();

        for (var iteration = 0; iteration < 10; iteration++)
        {
            for (int i = 0; i < numbers.Count; i++)
            {
                var index = numbers.FindIndex(number => number.id == i);
                var current = numbers[index];
                var operation = current.value;
                var newIndex = (int)(((index + operation) % modulo + modulo) % modulo);

                // The operations below are purely to have the Print() function show the same result as the examples
                if (newIndex == 0) newIndex = numbers.Count - 1;
                else if (newIndex == numbers.Count - 1) newIndex = 0;

                if (DEBUG) Console.WriteLine($"Moving {operation} from {index} to {newIndex}");
            
                // Remove current from numbers:
                numbers.RemoveAt(index);

                // Reinsert
                numbers = numbers.Take(newIndex).Append(current).Concat(numbers.Skip(newIndex)).ToList();

                Print();
            }
        }
        
        zero = numbers.FindIndex(number => number.value == 0);
        positionOfOneThousand = (zero + 1000) % numbers.Count;
        positionOfTwoThousand = (zero + 2000) % numbers.Count;
        positionOfThreeThousand = (zero + 3000) % numbers.Count;
        
        timer.Stop();
        Console.WriteLine($"What is the sum of the three numbers that form the grove coordinates? {numbers[positionOfOneThousand].value + numbers[positionOfTwoThousand].value + numbers[positionOfThreeThousand].value} ({timer.ElapsedMilliseconds}ms)");
        
        void Print()
        {
            if (!DEBUG) return;

            Console.WriteLine(String.Join(", ", numbers.Select(number => number.value)));
            Console.WriteLine("");
        }
    }
}