string[] input = File.ReadAllLines(@"./input.txt");

// Starting position is always in the left bottom corner, marking that (0, 0)
// That means that U means an increase of the y axis with 1, D means decreasing. 
// Similarly R means increase x axis with 1 and L means decrease with 1.
// 
// This assignment reminds me of https://wiki.gnome.org/Apps/Robots that I played
// on Ubuntu. The implementation is not so difficult; Move the robots (= tail) one
// step towards the player (= head). The difference between Robots and this assignment
// is that the tail does not always move.
//
// Part 2 is in essence the same exercise, with the difference that we don't just need
// to move the head, but potentially move every part of the rope. To do so we can reuse
// the Move() function and not have it look at the head and the tail, but have it look
// at two adjacent pieces of the rope.
//
// Post mortem: 
// I added quite some code to allow visualization of the puzzle state. On top of that the 
// code is really verbose. I think the logic for moving can be a lot more elegant. On top
// of that the code used for part 2 can be used for part 1 as well. All it needs is a rope
// consisting of two coordinates.

var DEBUG = false;

List<(int, int)> tailLocations = new List<(int, int)>{ (0, 0) };
List<(int, int)> tailLocations2 = new List<(int, int)>{ (0, 0) };

(int, int)[] rope = Enumerable.Repeat((0, 0), 10).ToArray();

int maxX = 0, maxY = 0, minX = 0, minY = 0; // for drawing purposes
Print();

foreach (var line in input)
{
    if (DEBUG)
    {
        Console.WriteLine($"== {line} ==");
        Console.WriteLine("");
    }

    var direction = line[0];
    var steps = Int32.Parse(line.Replace("D ", "").Replace("U ", "").Replace("L ", "").Replace("R ", ""));

    for (var step = 0; step < steps; step++)
    {
        switch (direction)
        {
            case 'D':
                rope[0].Item2 -= 1;
                minY = Math.Min(minY, rope[0].Item2);
                break;
            case 'U':
                rope[0].Item2 += 1;
                maxY = Math.Max(maxY, rope[0].Item2);
                break;
            case 'L':
                rope[0].Item1 -= 1;
                minX = Math.Min(minX, rope[0].Item1);
                break;
            case 'R':
                rope[0].Item1 += 1;
                maxX = Math.Max(maxY, rope[0].Item1);
                break;
            default:
                throw new Exception("This should not happen ...");
        }
    
        // Move rest of the rope
        // perform move for each part of the rope
        for (var knot = 1; knot < rope.Length; knot++)
        {
            var (leaderX, leaderY) = rope[knot - 1];
            var (chaserX, chaserY) = rope[knot];
        
            if (Math.Abs(leaderX - chaserX) > 1 || Math.Abs(leaderY - chaserY) > 1)
            {
                var dx = leaderX - chaserX;
                if (dx >= 1) chaserX++;
                if (dx <= -1) chaserX--;
		
                var dy = leaderY - chaserY;
                if (dy >= 1) chaserY++;
                if (dy <= -1) chaserY--;

                rope[knot] = (chaserX, chaserY);
            }
        }
    
        tailLocations.Add(rope[1]);
        tailLocations2.Add(rope[9]);   
    }

    Print();
}

int score = tailLocations.Distinct().Count();
Console.WriteLine($"How many positions does the tail of the rope visit at least once? {score}");

int score2 = tailLocations2.Distinct().Count();
Console.WriteLine($"How many positions does the tail of the rope visit at least once? {score2}");

void Print()
{
    if (DEBUG == false) return;

    for (var row = Math.Max(4, maxY); row >= minY; row--)
    {
        string ht = "", positions = ""; 
        for (var column = minX; column <= Math.Max(5, maxX); column++)
        {
            string c = ".";
            if (row == 0 && column == 0) c = "s";
            for (var knot = 9; knot >= 0; knot--)
            {
                var (x, y) = rope[knot];
                if (column == x && row == y) c = knot == 0 ? "H" : knot.ToString();
            }

            ht += c;
            positions += tailLocations2.Contains((column, row)) ? "#" : (tailLocations.Contains((column, row)) ? "*" : ".");
        }
        Console.WriteLine($"{ht}          {positions}");
    }
    Console.WriteLine("");
}