string[] input = File.ReadAllLines(@"./input.txt");

foreach (var line in input)
{
    for (var i = 0; i < line.Length - 4; i++)
    {
        if (line.Substring(i, 4).ToCharArray().Distinct().Count() == 4)
        {
            // unique sequence found!
            Console.WriteLine($"How many characters need to be processed before the first start-of-packet marker is detected? {i + 4}");
            break;
        }
    }
}