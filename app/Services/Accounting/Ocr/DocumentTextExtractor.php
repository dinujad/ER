<?php

namespace App\Services\Accounting\Ocr;

use Symfony\Component\Process\Process;

/**
 * Phase 7 — gets the text out of a supplier invoice file.
 *  - PDF with a text layer: `pdftotext -layout` when installed, else a built-in PHP reader (text PDFs only).
 *  - JPG / PNG (photos, scans): `tesseract` when installed on the server. Scanned PDFs: convert to an image or paste the text.
 * Returns null when no text can be read — the review screen then lets the user paste the text.
 */
class DocumentTextExtractor
{
    /** @return array{text: ?string, method: string} */
    public function extract(string $path, string $mime): array
    {
        $isPdf = str_contains($mime, 'pdf') || str_ends_with(strtolower($path), '.pdf');

        if ($isPdf) {
            $text = $this->run([$this->binary('pdftotext_path', 'pdftotext'), '-layout', $path, '-']);
            if ($this->usable($text)) {
                return ['text' => $text, 'method' => 'pdftotext'];
            }
            $text = $this->phpPdfText((string) @file_get_contents($path));
            if ($this->usable($text)) {
                return ['text' => $text, 'method' => 'php-pdf'];
            }
        }

        if (!$isPdf) {
            $text = $this->run([$this->binary('tesseract_path', 'tesseract'), $path, 'stdout', '-l', (string) config('accounting_ai.ocr.tesseract_lang', 'eng'), '--psm', '6']);
            if ($this->usable($text)) {
                return ['text' => $text, 'method' => 'tesseract'];
            }
        }

        return ['text' => null, 'method' => 'none'];
    }

    private function usable(?string $text): bool
    {
        return $text !== null && mb_strlen(trim(preg_replace('/\s+/', ' ', $text))) >= 20;
    }

    private function binary(string $key, string $default): string
    {
        return (string) (config('accounting_ai.ocr.' . $key) ?: $default);
    }

    /** @param array<int, string> $cmd */
    private function run(array $cmd): ?string
    {
        try {
            $p = new Process($cmd);
            $p->setTimeout(60);
            $p->run();

            return $p->isSuccessful() ? $p->getOutput() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Minimal text reader for computer-generated PDFs (FlateDecode / plain content streams, Tj / TJ operators).
     * Not a full PDF parser — scanned PDFs have no text and need tesseract.
     */
    public function phpPdfText(string $pdf): ?string
    {
        if ($pdf === '' || !str_starts_with($pdf, '%PDF')) {
            return null;
        }
        $out = [];
        if (!preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams)) {
            return null;
        }
        foreach ($streams[1] as $raw) {
            $data = @gzuncompress($raw);
            if ($data === false) {
                $data = @gzinflate(substr($raw, 2));
            }
            if ($data === false || $data === null) {
                $data = $raw;
            }
            if (!str_contains($data, 'BT')) {
                continue;
            }
            preg_match_all('/BT(.*?)ET/s', $data, $blocks);
            foreach ($blocks[1] as $block) {
                $line = '';
                preg_match_all('/(\[(?:[^\]\\\\]|\\\\.)*\]\s*TJ|\((?:[^)\\\\]|\\\\.)*\)\s*Tj|T\*|Td|TD|\'|")/s', $block, $ops);
                foreach ($ops[0] as $op) {
                    if (in_array(trim($op), ['T*', 'Td', 'TD', "'", '"'], true)) {
                        if ($line !== '') {
                            $out[] = $line;
                            $line = '';
                        }
                        continue;
                    }
                    preg_match_all('/\(((?:[^)\\\\]|\\\\.)*)\)/s', $op, $strs);
                    foreach ($strs[1] as $s) {
                        $line .= $this->unescape($s);
                    }
                }
                if ($line !== '') {
                    $out[] = $line;
                }
            }
        }
        $text = trim(implode("\n", $out));

        return $text === '' ? null : $text;
    }

    private function unescape(string $s): string
    {
        $s = preg_replace_callback('/\\\\([0-7]{1,3})/', fn ($m) => chr(octdec($m[1])), $s);

        return strtr($s, ['\\n' => "\n", '\\r' => '', '\\t' => ' ', '\\(' => '(', '\\)' => ')', '\\\\' => '\\']);
    }
}
