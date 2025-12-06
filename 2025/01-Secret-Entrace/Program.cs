using _01_Secret_Entrace;
using Shared;

string[] input = File.ReadAllLines(Environment.GetEnvironmentVariable("INPUT") != null ? $"./{Environment.GetEnvironmentVariable("INPUT")}" : "./input.txt");

// Parse input
Rotation[] instructions = input
    .Select(line =>
    {
    return new Rotation(
        line.Substring(0, 1),
        int.Parse(line.Substring(1, line.Length - 1))
    );
}).ToArray();

// Part 01
var timer = new AocTimer();
// perform calculation
int one = PartOne(instructions);
timer.Duration($"Using password method 0x434C49434B, what is the password to open the door? {one}");

// Part 02
timer = new AocTimer();
// perform calculation
int two = PartTwo(instructions);
timer.Duration($"Using password method 0x434C49434B, what is the password to open the door? {two}");

int PartOne(Rotation[] instructions)
{
    int position = 50;
    int zeros = 0;
    foreach (var instruction in instructions)
    {
        if (instruction.Direction == "L")
        {
            position -= (instruction.Clicks % 100);
            if (position < 0)
            {
                position += 100;
            }
        }
        else // assume R
        {
            position = (position + instruction.Clicks) % 100;   
        }

        if (position == 0)
        {
            zeros++;
        }
    }

    return zeros;
}

int PartTwo(Rotation[] instructions)
{
    int position = 50;
    int zeros = 0;

    // instructions = new[] { new Rotation("R", 49) };

    foreach (var instruction in instructions)
    {
        // We will pass 0 for every rotation by definition independent of the start position
        if (Math.Abs(instruction.Clicks) >= 100)
        {
            zeros += (int)Math.Floor(instruction.Clicks / 100.0);
        }

        // We now only need the remainder
        var clicks = instruction.Clicks % 100;

        if (instruction.Direction == "L")
        {
            if (position > 0 && position < clicks)
            {
                // We pass 0
                position = position - clicks + 100;
                zeros++;
            } else {
                position -= clicks;
                if (position < 0)
                {
                    position += 100;
                }
            }
        }
        else // assume R
        {
            if (position + clicks > 100)
            {
                // We pass 0
                position = position + clicks - 100;
                zeros++;
            }
            else
            {
                position = (position + clicks) % 100;
            }
        }

        if (position == 0)
        {
            zeros++;
        }
    }

    return zeros;
}
