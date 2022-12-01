var answer = 1234;

string[] input = System.IO.File.ReadAllLines(@"./input.txt");
Console.WriteLine($"Number of lines: {input.Length}");

int max = 0;
int current = 0;
foreach (var line in input)
{
    if (line == "")
    {
        max = Math.Max(current, max);
        current = 0;
        continue;
    }

    current += Int32.Parse(line);
}

max = Math.Max(current, max);

Console.WriteLine($"How many total Calories is that Elf carrying? {max}");