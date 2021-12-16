<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1
foreach ($inputs as $input) {
    $transmission = new Transmission($input);
    $packet = $transmission->parse();

    echo 'What do you get if you add up the version numbers in all packets?' . PHP_EOL;
//    echo $input . PHP_EOL;
    echo $packet->getSumOfVersionNumbers() . PHP_EOL;
}

final class Transmission
{
    private string $packet;
    private int $position = 0;
    private string $binary = '';

    private const MAPPING = [
        '0' => '0000',
        '1' => '0001',
        '2' => '0010',
        '3' => '0011',
        '4' => '0100',
        '5' => '0101',
        '6' => '0110',
        '7' => '0111',
        '8' => '1000',
        '9' => '1001',
        'A' => '1010',
        'B' => '1011',
        'C' => '1100',
        'D' => '1101',
        'E' => '1110',
        'F' => '1111',
    ];

    public function __construct(string $packet)
    {
        $this->packet = $packet;
    }

    public function parse(): Packet
    {
        $this->position = 0;
        $this->binary = $this->convertToBinary($this->packet);

        return $this->parsePacket();
    }

    private function parsePacket(): Packet
    {
        $version = bindec($this->read(3));
        $type = bindec($this->read(3));

        if ($type === 4) {
            // literal value
            $bits = [];

            do {
                $last_bit = $this->read(1) === '0';
                $bits[] = $this->read(4);
            } while (!$last_bit);

            return (new Packet($version, $type))->withValue(bindec(join('', $bits)));
        } else {
            // operator
            $length_type_id = $this->read(1);
            if ($length_type_id === '0') {
                // 15 bits that represent the length in bits
                $length = bindec($this->read(15));

                $sub_packets = [];
                $start_position = $this->position;
                while ($this->position < $start_position + $length) {
                    $sub_packets[] = $this->parsePacket();
                }

            } else {
                // 11 bits that represent number of sub-packets
                $num_of_sub_packets = bindec($this->read(11));

                $sub_packets = [];
                for ($i = 0; $i < $num_of_sub_packets; $i++) {
                    $sub_packets[] = $this->parsePacket();
                }

            }
            return (new Packet($version, $type))
                ->withEmbedded(...$sub_packets);
        }

        throw new RuntimeException('Unable to parse');
    }

    private function read(int $bits): string
    {
        $result = substr($this->binary, $this->position, $bits);
        $this->position += $bits;

        return $result;
    }

    private function convertToBinary(string $packet): string
    {
        return array_reduce(
            str_split($packet),
            fn(string $binary, string $character): string => $binary . self::MAPPING[$character],
            ''
        );
    }
}

final class Packet
{
    private int $version;
    private int $type;

    private ?int $value = null;
    private array $embedded = [];

    public function __construct(int $version, int $type)
    {
        $this->version = $version;
        $this->type = $type;
    }

    public function withValue(int $value): self
    {
        $clone = clone $this;
        $clone->value = $value;

        return $clone;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function withEmbedded(Packet ...$embedded): self
    {
        $clone = clone $this;
        $clone->embedded = $embedded;

        return $clone;
    }

    /**
     * @return array<Packet>
     */
    public function getEmbedded(): array
    {
        return $this->embedded;
    }

    public function getSumOfVersionNumbers(): int
    {
        if (count($this->embedded) === 0) {
            return $this->version;
        }

        return $this->version + array_sum(
            array_map(
                fn(Packet $packet): int => $packet->getSumOfVersionNumbers(),
                $this->embedded
            )
        );
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private function toString(string $indent = ''): string
    {
        return
            $indent . sprintf("Type: %d\tVersion: %d\tValue: %s", $this->getType(), $this->getVersion(), $this->value) .
            join('', array_map(fn(Packet $packet): string => PHP_EOL . $indent . $packet->toString($indent . '    '), $this->embedded));
    }
}
