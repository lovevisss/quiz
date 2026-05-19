<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionTag;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class QuestionImportService
{
    /**
     * @param array $questions
     * @return int Number of questions imported
     */
    public function import(array $questions): int
    {
        $count = 0;
        foreach ($questions as $data) {
            $row = $this->normalizeRow($data);

            if ($this->isDuplicate($row)) {
                continue;
            }

            QuestionTag::syncNames($row['tags'] ?? []);
            Question::create($row);
            $count++;
        }

        return $count;
    }

    public function importFile(UploadedFile $file): int
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if (! $path) {
            throw new InvalidArgumentException('Invalid upload file.');
        }

        if ($extension === 'csv') {
            return $this->import($this->rowsFromCsv($path));
        }

        if ($extension === 'xlsx') {
            return $this->import($this->rowsFromXlsx($path));
        }

        throw new InvalidArgumentException('Unsupported file type. Please upload csv or xlsx.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsFromCsv(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        $encoding = mb_detect_encoding($raw, ['UTF-8', 'GB18030', 'GBK'], true) ?: 'UTF-8';
        $csv = $encoding === 'UTF-8' ? $raw : mb_convert_encoding($raw, 'UTF-8', $encoding);
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv;

        $rows = [];
        $headers = null;

        foreach (preg_split('/\r\n|\r|\n/', $csv) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = array_map(
                fn ($value) => trim((string) $value),
                str_getcsv($line)
            );

            if ($headers === null) {
                $headers = $cells;
                continue;
            }

            $rows[] = array_combine($headers, $cells) ?: [];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsFromXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Failed to open xlsx file.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if (is_string($sharedStringsXml)) {
            $shared = simplexml_load_string($sharedStringsXml);
            if ($shared !== false && isset($shared->si)) {
                foreach ($shared->si as $si) {
                    $sharedStrings[] = trim((string) $si->t);
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! is_string($sheetXml)) {
            throw new InvalidArgumentException('xlsx missing worksheet data.');
        }

        $xml = simplexml_load_string($sheetXml);
        if ($xml === false || ! isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        $headers = null;

        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $type = (string) ($cell['t'] ?? '');
                $value = (string) ($cell->v ?? '');

                if ($type === 's') {
                    $index = (int) $value;
                    $cells[] = $sharedStrings[$index] ?? '';
                } else {
                    $cells[] = trim($value);
                }
            }

            if ($headers === null) {
                $headers = $cells;
                continue;
            }

            $rows[] = array_combine($headers, $cells) ?: [];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedRow[trim((string) $key)] = $value;
        }

        $content = (string) $this->getByAliases($normalizedRow, ['content', 'question', '题目', '标题']);
        $type = (string) $this->getByAliases($normalizedRow, ['type', '类型']);
        $answer = $this->getByAliases($normalizedRow, ['answer', '正确项', '正确答案']);
        $answer = $answer === null ? null : trim((string) $answer);
        $explanation = $this->getByAliases($normalizedRow, ['explanation', '解析', '答案解析']);
        $difficulty = (int) ($this->getByAliases($normalizedRow, ['difficulty', '难度']) ?? 1);

        $options = $this->getByAliases($normalizedRow, ['options']);
        if (is_string($options)) {
            $options = collect(explode(',', $options))
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->all();
        }

        if (! is_array($options) || $options === []) {
            $matrix = ['A', 'B', 'C', 'D'];
            $built = [];
            foreach ($matrix as $label) {
                $value = $this->getByAliases($normalizedRow, [
                    "option_{$label}",
                    "option {$label}",
                    "option{$label}",
                    "选项{$label}",
                    "选项 {$label}",
                    $label,
                ]);
                if ($value !== null && trim((string) $value) !== '') {
                    $built[] = trim((string) $value);
                }
            }
            $options = $built;
        }

        $tags = $this->getByAliases($normalizedRow, ['tags', '标签']) ?? [];
        if (is_string($tags)) {
            $tags = collect(explode(',', $tags))
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->all();
        }

        if ($type === '') {
            $type = str_contains((string) $answer, ',') ? 'multiple' : 'single';
            if ($options === []) {
                $type = 'text';
            }
        }

        $tags = QuestionTag::normalizeNames(is_array($tags) ? $tags : []);

        return [
            'content' => $content,
            'type' => $type,
            'options' => is_array($options) ? $options : [],
            'answer' => $answer,
            'explanation' => $explanation !== null ? (string) $explanation : null,
            'option_explanations' => [],
            'difficulty' => max(1, min(5, $difficulty)),
            'tags' => $tags,
            'status' => filter_var(
                $this->getByAliases($normalizedRow, ['status', '启用']) ?? true,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? true,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $aliases
     */
    private function getByAliases(array $row, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            foreach ($row as $key => $value) {
                if (mb_strtolower(trim($key)) === mb_strtolower(trim($alias))) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function isDuplicate(array $row): bool
    {
        return Question::query()
            ->where('content', $row['content'])
            ->where('type', $row['type'])
            ->exists();
    }
}
