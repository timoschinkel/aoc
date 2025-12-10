namespace _09_Movie_Theater;

public class Edge(long x1, long y1, long x2, long y2)
{
    public long X1 { get; } = x1;
    public long Y1 { get; } = y1;
    public long X2 { get; } = x2;
    public long Y2 { get; } = y2;
    
    public long MinX { get; } = Math.Min(x1, x2);
    public long MaxX { get; } = Math.Max(x1, x2);
    public long MinY { get; } = Math.Min(y1, y2);
    public long MaxY { get; } = Math.Max(y1, y2);
}