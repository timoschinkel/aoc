# Advent of Code 2025

See: https://adventofcode.com/2025

## Usage
This year's Advent of Code is written using C# 8.0 - because that's the version I have installed at this moment.

Everyday is a separate project with `Program.cs`. This is a console application that will look for the file `{project}/input.txt`. If you want to run with a different input - e.g. `example.txt` - then this can be specified using the `INPUT` environment variable. The solution has build configurations for both `input.txt` and `example.txt`.

To run a project:

```bash
dotnet build
dotnet run --project {project}
```

For example run day 1 against the example input - ensure `01-Secret-Entrace/example.txt` exists:

```bash
dotnet build
INPUT=example.txt dotnet run --project 01-Secret-Entrace
```

## Requirements
- .NET 8.0
