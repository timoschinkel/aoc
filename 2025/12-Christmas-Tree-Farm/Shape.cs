namespace _12_Christmas_Tree_Farm;

public class Shape
{
    public string[] Input { get; }
    public int Populated { get; }

    public Shape(string[] input)
    {
        Input = input;
        Populated = input.Select(line => line.Count(c => c == '#')).Sum();
    }
}