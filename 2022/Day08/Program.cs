string[] input = File.ReadAllLines(@"./input.txt");

int width = input[0].Length, height = input.Length;
int[,] forest = new int[width, height];
bool[,]? visible = new bool[width, height];

for (var column = 0; column < width; column++)
{
    for (var row = 0; row < height; row++)
    {
        forest[column, row] = Int32.Parse(input[row][column].ToString());
        visible[column, row] = row == 0 || row == height - 1 || column == 0 || column == width - 1; // border is always visible
    }
}

/*
 * The trees can be visible per row - left to right OR right to left - and per column - top to bottom
 * OR bottom to top. My approach is to iterate over the forest twice; Once from the top left to the
 * bottom right and once from the bottom right to the top left. Every scan maintains a maximum tree
 * height per column and per row. Trees that are higher then the other trees in the row or column are
 * marked as visible. To prevent counting trees twice this is done in a 2-dimensional array of the same
 * dimensions as the forest. To calculate the number of visible trees - part 1 - I count all the values
 * of `visible` that have the value `true`.
 */

// From top left to bottom right
int[] maxPerColumn = input[0].Select(c => Int32.Parse(c.ToString())).ToArray();
for (var row = 1; row < height - 1; row++)
{
    int maxForRow = forest[0, row];
    for (var column = 1; column < width - 1; column++)
    {
        var tree = forest[column, row];
        if (tree > maxForRow)
        {
            visible[column, row] = true;
            maxForRow = tree;
        }

        var maxForColumn = maxPerColumn[column];
        if (tree > maxForColumn)
        {
            visible[column, row] = true;
            maxPerColumn[column] = tree;
        }
    }
}

// From bottom right to top left
maxPerColumn = input[height - 1].Select(c => Int32.Parse(c.ToString())).ToArray();
for (var row = width - 2; row >= 1; row--)
{
    int maxForRow = forest[width - 1, row];
    for (var column = width - 2; column >= 1; column--)
    {
        var tree = forest[column, row];
        if (tree > maxForRow)
        {
            visible[column, row] = true;
            maxForRow = tree;
        }

        var maxForColumn = maxPerColumn[column];
        if (tree > maxForColumn)
        {
            visible[column, row] = true;
            maxPerColumn[column] = tree;
        }
    }
}

Print(forest, visible);

var score = 0;
for (var column = 0; column < width; column++)
{
    for (var row = 0; row < height; row++)
    {
        if (visible[column, row]) score++;
    }
}

Console.WriteLine($"how many trees are visible from outside the grid? {score}");

/*
 * For part 2 I could in theory reuse the two scans above; I can keep an index for all the tree heights and the
 * amount of steps away they are (for the vertical view lines it would be an index per column).
 *
 * I however have opted for the brute force approach; Iterate over the field and walk all four directions
 * until you reach the border or a higher tree.
 */

int[,] scenic = new int[width, height];
int maxScenicScore = 0;

for (var column = 0; column < width; column++)
{
    for (var row = 0; row < height; row++)
    {
        var tree = forest[column, row];
        var up = GetScenicScoreVertical(column, row, -1);
        var left = GetScenicScoreHorizontal(column, row, -1);
        var right = GetScenicScoreHorizontal(column, row, 1);
        var down = GetScenicScoreVertical(column, row, 1);
        int scenicScore = left * up * right * down;

        scenic[column, row] = scenicScore;
        maxScenicScore = Math.Max(maxScenicScore, scenicScore);
    }
}

// Print(scenic);
Console.WriteLine($"What is the highest scenic score possible for any tree? {maxScenicScore}");

int GetScenicScoreHorizontal(int column, int row, int delta)
{
    int scenicScore = 0, tree = forest[column, row];
    for (var c = column + delta; c >= 0 && c < width; c += delta)
    {
        scenicScore++;
        if (forest[c, row] >= tree)
        {
            break;
        }
    }

    return scenicScore;
}

int GetScenicScoreVertical(int column, int row, int delta)
{
    int scenicScore = 0, tree = forest[column, row];
    for (var r = row + delta; r >= 0 && r < height; r += delta)
    {
        scenicScore++;
        if (forest[column, r] >= tree)
        {
            break;
        }
    }

    return scenicScore;
}

// Debug
void Print(int[,] forest, bool[,]? visible = null)
{
    var color = Console.ForegroundColor;
    for (var row = 0; row < height; row++)
    {
        for (var column = 0; column < width; column++)
        {
            if (visible != null)
            {
                Console.ForegroundColor = visible[column, row] ? ConsoleColor.Magenta : color;   
            }
            Console.Write(forest[column, row]);
        }
        Console.WriteLine("");
    }
    Console.ForegroundColor = color;
}