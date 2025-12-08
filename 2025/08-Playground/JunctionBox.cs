namespace _08_Playground;

public class JunctionBox
{
    public int X { get; }
    public int Y { get; }
    public int Z { get; }
    public int? Circuit { get; set; }

    public JunctionBox(int x, int y, int z)
    {
        X = x;
        Y = y;
        Z = z;
    }

    public override string ToString()
    {
        return $"({X},{Y},{Z})";
    }
    
    public static JunctionBox FromInput(string input)
    {
        var parts = input.Split(",");
        
        return new JunctionBox(int.Parse(parts[0]), int.Parse(parts[1]), int.Parse(parts[2]));
    }
}