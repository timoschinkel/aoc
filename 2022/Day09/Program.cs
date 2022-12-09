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

int headX = 0, headY = 0, tailX = 0, tailY = 0;
List<(int, int)> tailLocations = new List<(int, int)>{ (0, 0) };
List<(int, int)> tailLocations2 = new List<(int, int)>{ (0, 0) };

(int, int)[] rope = new (int, int)[] // index 0 is head, index 9 is tail
{
    (0, 0), (0, 0), (0, 0), (0, 0), (0, 0), (0, 0), (0, 0), (0, 0), (0, 0), (0, 0)
};

int maxX = 0, maxY = 0, minX = 0, minY = 0; // for drawing purposes
Print();
Print2();

foreach (var line in input)
{
    if (DEBUG)
    {
        Console.WriteLine($"== {line} ==");
        Console.WriteLine("");
    }

    int dX = 0, dY = 0;
    switch (line[0])
    {
        case 'D':
            dY = Int32.Parse(line.Replace("D ", ""));
            Down(dY);
            break;
        case 'U':
            dY = Int32.Parse(line.Replace("U ", ""));
            Up(dY);
            break;
        case 'L':
            dX = Int32.Parse(line.Replace("L ", ""));
            Left(dX);
            break;
        case 'R':
            dX = Int32.Parse(line.Replace("R ", ""));
            Right(dX);
            break;
        default:
            throw new Exception("This should not happen ...");
    }
    
    Print();
    Print2();
}

void Up(int steps)
{
    for (var step = 0; step < steps; step++)
    {
        Move(headX, headY + 1);
    }
}

void Down(int steps)
{
    for (var step = 0; step < steps; step++)
    {
        Move(headX, headY - 1);
    }
}

void Left(int steps)
{
    for (var step = 0; step < steps; step++)
    {
        Move(headX - 1, headY);
    }
}

void Right(int steps)
{
    for (var step = 0; step < steps; step++)
    {
        Move(headX + 1, headY);
    }
}

void Move(int x, int y)
{
    // Perform part 2 in separate method
    MoveHead(x, y);
    
    // Move head
    headX = x;
    headY = y;
    
    // Move Tail towards head, if needed
    if (Math.Abs(headX - tailX) > 1 || Math.Abs(headY - tailY) > 1)
    {
        var dx = headX - tailX;
        if (dx >= 1) tailX++;
        if (dx <= -1) tailX--;
		
        var dy = headY - tailY;
        if (dy >= 1) tailY++;
        if (dy <= -1) tailY--;
        
        tailLocations.Add((tailX, tailY));
    }

    maxX = Math.Max(maxX, headX);
    minX = Math.Min(minX, headX);
    maxY = Math.Max(maxY, headY);
    minY = Math.Min(minY, headY);
}

void MoveHead(int x, int y)
{
    // Move head
    rope[0] = (x, y);
    
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
    
    // Add tail location to list
    tailLocations2.Add(rope[9]);
}

int score = tailLocations.Distinct().Count();
Console.WriteLine($"How many positions does the tail of the rope visit at least once? {score}");

int score2 = tailLocations2.Distinct().Count();
Console.WriteLine($"How many positions does the tail of the rope visit at least once? {score2}");

void Print()
{
    if (DEBUG == false) return;

    Console.WriteLine($"Head: ({headX}, {headY}), tail: ({tailX}, {tailY})");
    for (var row = Math.Max(4, maxY); row >= minY; row--)
    {
        string ht = "", positions = ""; 
        for (var column = minX; column <= Math.Max(5, maxX); column++)
        {
            char c = '.';
            if (row == 0 && column == 0) c = 's';
            if (row == tailY && column == tailX) c = 'T';
            if (row == headY && column == headX) c = 'H';

            ht += c;
            positions += tailLocations.Contains((column, row)) ? "#" : ".";
        }
        Console.WriteLine($"{ht}          {positions}");
    }
    Console.WriteLine("");
}

void Print2()
{
    if (DEBUG == false) return;

    Console.WriteLine($"Head: ({headX}, {headY}), tail: ({tailX}, {tailY})");
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
            positions += tailLocations2.Contains((column, row)) ? "#" : ".";
        }
        Console.WriteLine($"{ht}          {positions}");
    }
    Console.WriteLine("");
}