<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class ProductJsonRepository
{
    public function __construct(
        private readonly string $path = ''
    ) {}

    public function getPath(): string
    {
        return $this->path !== '' ? $this->path : storage_path('app/products.json');
    }

    /**
     * @return list<array{id: string, product_name: string, quantity: int, price_per_item: float, submitted_at: string}>
     */
    public function all(): array
    {
        $items = $this->readWithoutLock();
        $this->sortBySubmittedAt($items);

        return $items;
    }

    /**
     * @param  array{product_name: string, quantity: int|string, price_per_item: float|string}  $data
     * @return array{id: string, product_name: string, quantity: int, price_per_item: float, submitted_at: string}
     */
    public function add(array $data): array
    {
        $entry = null;

        $this->lockedModify(function (array &$items) use ($data, &$entry): void {
            $entry = [
                'id' => (string) Str::uuid(),
                'product_name' => $data['product_name'],
                'quantity' => (int) $data['quantity'],
                'price_per_item' => (float) $data['price_per_item'],
                'submitted_at' => Carbon::now()->toIso8601String(),
            ];
            $items[] = $entry;
        });

        if ($entry === null) {
            throw new RuntimeException('Failed to add product.');
        }

        return $entry;
    }

    /**
     * @param  array{product_name: string, quantity: int|string, price_per_item: float|string}  $data
     * @return array{id: string, product_name: string, quantity: int, price_per_item: float, submitted_at: string}|null
     */
    public function update(string $id, array $data): ?array
    {
        $updated = null;

        $this->lockedModify(function (array &$items) use ($id, $data, &$updated): void {
            foreach ($items as $i => $item) {
                if (($item['id'] ?? '') !== $id) {
                    continue;
                }
                $items[$i]['product_name'] = $data['product_name'];
                $items[$i]['quantity'] = (int) $data['quantity'];
                $items[$i]['price_per_item'] = (float) $data['price_per_item'];
                $updated = $items[$i];

                return;
            }
        });

        return $updated;
    }

    /**
     * @param  list<array{id: string, product_name: string, quantity: int, price_per_item: float, submitted_at: string}>  $items
     */
    public function replaceAll(array $items): void
    {
        $this->lockedModify(function (array &$current) use ($items): void {
            $current = array_values($items);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function sortBySubmittedAt(array &$items): void
    {
        usort($items, fn (array $a, array $b): int => strcmp($a['submitted_at'] ?? '', $b['submitted_at'] ?? ''));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readWithoutLock(): array
    {
        $path = $this->getPath();
        if (! is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values($decoded);
    }

    /**
     * @param  callable(array<int|string, mixed>&): void  $callback
     */
    private function lockedModify(callable $callback): void
    {
        $path = $this->getPath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                throw new RuntimeException('Cannot create storage directory for products.');
            }
        }

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open products file.');
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock products file.');
            }

            $raw = stream_get_contents($handle);
            $items = [];
            if ($raw !== false && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $items = array_values($decoded);
                }
            }

            $callback($items);

            $this->sortBySubmittedAt($items);

            rewind($handle);
            ftruncate($handle, 0);
            $encoded = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            fwrite($handle, $encoded);
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
