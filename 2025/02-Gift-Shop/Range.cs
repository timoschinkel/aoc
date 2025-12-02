namespace _02_Gift_Shop;

public class Range(long first, long last)
{
    public long First { get; } = first;
    public long Last { get; } = last;

    public static Range FromInput(string input)
    {
        var parts = input.Split('-');
        if (parts.Length != 2)
        {
            throw new Exception("Invalid input");
        }
        
        return new Range(long.Parse(parts[0]), long.Parse(parts[1]));
    }
}