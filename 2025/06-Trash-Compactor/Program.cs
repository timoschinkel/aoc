using System.Reflection;
using Shared;

string path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? ".";
string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"{path}/{Environment.GetEnvironmentVariable("INPUT")}" : $"{path}/input.txt");

// Parse input
var numbers = input.SkipLast(1).Select(line => line.Split(' ', StringSplitOptions.RemoveEmptyEntries).Select(number => long.Parse(number)).ToArray()).ToArray();
var operators = input.Last().Split(' ', StringSplitOptions.RemoveEmptyEntries).Select(op => op).ToArray();

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne(numbers, operators);
timer.Duration($"What is the grand total found by adding together all of the answers to the individual problems? {one}");

// Part 02
timer = new AocTimer();
// perform calculation
var two = PartTwo(input);
timer.Duration($"What is the grand total found by adding together all of the answers to the individual problems? {two}");

long PartOne(long[][] numbers, string[] operators)
{
    long max = numbers.Select(row => row.Length).Max();
    
    long sum = 0;
    for (int calculation = 0; calculation < max; calculation++)
    {
        string op = operators[calculation];
        long result = 0;
        if (op == "*") result = 1;

        foreach (var number in numbers)
        {
            if (number.Length >= calculation + 1)
            {
                if (op == "*") result *= number[calculation];
                else if (op == "+") result += number[calculation];
                else
                {
                    throw new Exception($"Operator {op} not supported.");
                }
            }
        }
        
        sum += result;
    }
    
    return sum;
}

long PartTwo(string[] input)
{
    /*
     * This might require some explanation...
     * The important observation, and the example really threw me off on this part, is that every column has a width. And
     * this width can differ per column. How to determine the width? The last line of the input - the operators - give
     * an indication of the width. The operator is always on the first index of the column.
     *
     * This knowledge makes the approach as follows:
     * - iterate over the row of the operators
     * - whenever you encounter an operator we know the width and thus can we extract all the raw inputs for the column
     * - per column we can now construct the numbers and perform the calculation
     *
     * Some additional observations:
     * - missing characters are only at the end of the list, e.g. _10 + __1 + _10 does not appear to occur, so we can
     *   rely on simple trimming.
     *
     * The code is a bit messy, but it works.
     */
    
    long sum = 0;
    
    string ops = input.Last();
    
    int previous = 0;
    for (int index = 1; index < ops.Length; index++)
    {
        if (ops[index] == ' ') continue;

        int width = index - previous - 1;
        var column = ReadColumn(previous, width);
        
        sum += Calculate(ops[previous], column);
        
        previous = index;
    }

    var last = ReadColumn(previous, ops.Length - previous);
    
    sum += Calculate(ops[previous], last);

    return sum;
}

IEnumerable<string> ReadColumn(int start, int width)
{
    List<string> column = new List<string>();
    foreach (var row in input.SkipLast(1))
    {
        column.Add(row.Substring(start, width));
    }
    
    return column;
}

long Calculate(char op, IEnumerable<string> column)
{
    // 64
    // 23
    // 314 +
    // ---    => becomes => 4 (last column) + 431 (middle column) + 623 (first column)
    
    int width = column.Select(row => row.Length).Max();
    
    List<long> rotated = new List<long>();
    for (int i = 0; i < width; i++)
    {
        string number = "";
        foreach (var n in column)
        {
            number += n[i];
        }
        
        rotated.Add(long.Parse(number.Trim()));
    }
    
    return op == '+' ? rotated.Sum() : rotated.Product();
}

public static class ListProduct
{
    public static long Product(this List<long> numbers)
    {
        long product = 1;
        foreach (var number in numbers)
        {
            product *= number;
        }
        return product;
    }
}