<?php
declare(strict_types=1);

namespace AutoLoc\DAO;

use JsonException;
use RuntimeException;

abstract class BaseJsonDAO
{
    public function __construct(protected string $filePath)
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de créer le dossier de données.');
        }
        if (!is_file($this->filePath)) {
            file_put_contents($this->filePath, "[]\n", LOCK_EX);
        }
    }

    /** @return array<int, array<string, mixed>> */
    protected function readRecords(): array
    {
        $handle = fopen($this->filePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Impossible d’ouvrir le fichier JSON.');
        }
        try {
            if (!flock($handle, LOCK_SH)) {
                throw new RuntimeException('Impossible de verrouiller le fichier JSON.');
            }
            rewind($handle);
            $content = stream_get_contents($handle);
            $records = $content === false || trim($content) === ''
                ? []
                : json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($records)) {
                throw new RuntimeException('Le fichier JSON doit contenir une liste.');
            }
            return array_values($records);
        } catch (JsonException $exception) {
            throw new RuntimeException('Le fichier JSON contient des données invalides.', 0, $exception);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @param callable(array<int, array<string, mixed>>): array<int, array<string, mixed>> $mutator */
    protected function mutateRecords(callable $mutator): void
    {
        $handle = fopen($this->filePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Impossible d’ouvrir le fichier JSON.');
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Impossible de verrouiller le fichier JSON.');
            }
            rewind($handle);
            $content = stream_get_contents($handle);
            $records = $content === false || trim($content) === ''
                ? []
                : json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($records)) {
                throw new RuntimeException('Le fichier JSON doit contenir une liste.');
            }
            $newRecords = $mutator(array_values($records));
            $json = json_encode(array_values($newRecords), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, $json . PHP_EOL) === false) {
                throw new RuntimeException('Impossible d’enregistrer le fichier JSON.');
            }
            fflush($handle);
        } catch (JsonException $exception) {
            throw new RuntimeException('Erreur lors du traitement du fichier JSON.', 0, $exception);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @param array<string, mixed> $record @return array<string, mixed> */
    protected function insertRecord(array $record): array
    {
        $inserted = [];
        $this->mutateRecords(function (array $records) use ($record, &$inserted): array {
            $maxId = 0;
            foreach ($records as $item) {
                $maxId = max($maxId, (int) ($item['id'] ?? 0));
            }
            $inserted = ['id' => $maxId + 1] + $record;
            $records[] = $inserted;
            return $records;
        });
        return $inserted;
    }

    /** @param array<string, mixed> $record */
    protected function replaceRecord(int $id, array $record): bool
    {
        $updated = false;
        $this->mutateRecords(function (array $records) use ($id, $record, &$updated): array {
            foreach ($records as $index => $item) {
                if ((int) ($item['id'] ?? 0) === $id) {
                    $records[$index] = ['id' => $id] + $record;
                    $updated = true;
                    break;
                }
            }
            return $records;
        });
        return $updated;
    }

    protected function removeRecord(int $id): bool
    {
        $removed = false;
        $this->mutateRecords(function (array $records) use ($id, &$removed): array {
            $filtered = [];
            foreach ($records as $item) {
                if ((int) ($item['id'] ?? 0) === $id) {
                    $removed = true;
                    continue;
                }
                $filtered[] = $item;
            }
            return $filtered;
        });
        return $removed;
    }

    /** @param array<string, mixed> $data @param string[] $required */
    protected function validateRequired(array $data, array $required): void
    {
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || trim((string) $data[$field]) === '') {
                throw new \InvalidArgumentException("Le champ « {$field} » est obligatoire.");
            }
        }
    }

    protected function positiveFloat(mixed $value, string $field): float
    {
        if (!is_numeric($value) || (float) $value < 0) {
            throw new \InvalidArgumentException("Le champ « {$field} » doit être un nombre positif ou nul.");
        }
        return (float) $value;
    }

    protected function positiveInt(mixed $value, string $field): int
    {
        $validated = filter_var($value, FILTER_VALIDATE_INT);
        if ($validated === false || $validated <= 0) {
            throw new \InvalidArgumentException("Le champ « {$field} » doit être un entier positif.");
        }
        return $validated;
    }
}
