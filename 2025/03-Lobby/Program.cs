using Shared;
using System.Reflection;

string path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? ".";
string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"{path}/{Environment.GetEnvironmentVariable("INPUT")}" : $"{path}/input.txt");

// Parse input
var banks = input.Select(line => line.ToArray().Select(s => int.Parse(s.ToString())).ToArray());

// Part 01
var timer = new AocTimer();
// perform calculation
var one = PartOne(banks);
timer.Duration($"What is the total output joltage? {one}");

// Part 02
timer = new AocTimer();
// perform calculation
var two = PartTwo(banks);
timer.Duration($"What is the new total output joltage? {two}");

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
    long joltage = 0;
    foreach (var bank in banks)
    {
        joltage += CalculateNewLargestJoltage(bank, 12);
    }
    return joltage;
}

int FindMaxWithinRange(int[] bank, int lower, int upper)
{
    int maxValue = bank[lower];
    if (maxValue == 9) return lower; // No higher value possible

    int maxIndex = lower;
    for (int index = lower + 1; index <= upper; index++)
    {
        if (bank[index] == 9) return index;
        
        if (bank[index] > maxValue)
        {
            maxValue = bank[index];
            maxIndex = index;
        }
    }
    return maxIndex;
}

long CalculateNewLargestJoltage(int[] bank, int size)
{
    long max = 0;

    // Find the largest in the search space
    int position = -1;
    for (int i = 0; i < size; i++)
    {
        position = FindMaxWithinRange(bank, position + 1, bank.Length - size + i);
        max += bank[position] * (long)Math.Pow(10, size - i - 1);
    }

    return max;
}