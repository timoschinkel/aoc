namespace _12_Christmas_Tree_Farm;

public class Region (int width, int height, int[] counts)
{
    public int Width => width;
    public int Height => height;
    public int[] Counts => counts;

    public static Region FromInput(string input)
    {
        var parts = input.Split(" ");
        var wh = parts[0].Substring(0, parts[0].Length - 1).Split("x");
        
        return new Region(int.Parse(wh[0]), int.Parse(wh[1]), parts.Skip(1).Select(int.Parse).ToArray());
    }
}