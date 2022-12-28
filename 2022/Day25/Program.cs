using System.Diagnostics;
using System.Reflection;

namespace Day25;

class Program
{
    public static void Main(string[] args)
    {
        bool EXAMPLE = args.Contains("example");

        string[] input = EXAMPLE
            ? File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/example.txt")
            : File.ReadAllLines($"{Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)}/input.txt");

        var timer = new Stopwatch();
        timer.Start();

        string sum = "";
        foreach (var line in input)
        {
            if (sum == "")
            {
                sum = line;
                continue;
            }

            // Add 0's to the left of both string to make sure they are equal lenght. That makes the next steps easier.
            var left = sum.PadLeft(Math.Max(sum.Length, line.Length), '0');
            var right = line.PadLeft(Math.Max(sum.Length, line.Length), '0');
            
            // Perform the same operation as we would when we add to "normal" decimal numbers. So we walk from the least
            // significant to the most significant part of the numbers. For every char we temporarily convert to a
            // decimal representation - because I could not wrap my head around it - and do a traditional addition. Just
            // like with decimal calculus we can have carries, but this time we can also have negative carries. I think 
            // there is some optimization to be made with seeking the index of a char in a string/array, but this was 
            // easier.
            // Don't forget to add the carry after the all characters are handled ;) 
            
            int carry = 0;
            string newSum = "";
            for (int pos = left.Length - 1; pos >= 0; pos--)
            {
                var l = left[pos];
                var r = right[pos];

                var s = ToDec(l) + ToDec(r) + carry;
                if (s == -5)
                {
                    newSum = "0" + newSum;
                    carry = -1;
                }
                if (s == -4)
                {
                    newSum = "1" + newSum;
                    carry = -1;
                }
                if (s == -3)
                {
                    newSum = "2" + newSum;
                    carry = -1;
                }

                if (s == -2)
                {
                    newSum = "=" + newSum;
                    carry = 0;
                }
                
                if (s == -1)
                {
                    newSum = "-" + newSum;
                    carry = 0;
                }
                
                if (s is >= 0 and <= 2)
                {
                    newSum = s + newSum;
                    carry = 0;
                }

                if (s == 3)
                {
                    newSum = "=" + newSum;
                    carry = 1;
                }
                
                if (s == 4)
                {
                    newSum = "-" + newSum;
                    carry = 1;
                }
                
                if (s == 5)
                {
                    newSum = "0" + newSum;
                    carry = 1;
                }
            }

            if (carry < 0)
            {
                newSum = "-" + newSum;
            }

            if (carry > 0)
            {
                newSum = "1" + newSum;
            }
            
            sum = newSum;
        }
        
        timer.Stop();
        Console.WriteLine($"What SNAFU number do you supply to Bob's console? {sum} ({timer.ElapsedMilliseconds}ms)");
        
        int ToDec(char c)
        {
            switch (c)
            {
                case '=': return -2;
                case '-': return -1;
                case '0': return 0;
                case '1': return 1;
                case '2': return 2;
                default: throw new Exception("Unknown character");
            }
        }
    }
}