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
    
    for (var i = 0; i < line.Length - 14; i++)
    {
        if (line.Substring(i, 14).ToCharArray().Distinct().Count() == 14)
        {
            // unique sequence found!
            Console.WriteLine($"How many characters need to be processed before the first start-of-message marker is detected? {i + 14}");
            break;
        }
    }
}