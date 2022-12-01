string[] input = File.ReadAllLines(@"./input.txt");
Console.WriteLine($"Number of lines: {input.Length}");

int max = 0;
int current = 0;
List<int> elves = new List<int>();

foreach (var line in input)
{
    if (line == "")
    {
        if (current > 0)
        {
            elves.Add(current);
        }
        max = Math.Max(current, max);
        current = 0;
        continue;
    }

    current += Int32.Parse(line);
}

max = Math.Max(current, max);
elves.Add(current);

Console.WriteLine($"How many total Calories is that Elf carrying? {max}");

elves = elves.OrderByDescending(value => value).ToList();
int total = elves.GetRange(0, 3).Sum();

Console.WriteLine($"How many Calories are those Elves carrying in total? {total}");