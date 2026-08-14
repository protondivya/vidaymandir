<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use setasign\Fpdi\Fpdi;

class WatermarkPdf extends Fpdi
{
    private float $angle = 0;

    /**
     * Stamp the current page with a diagonal watermark and a footer line.
     */
    public function stamp(string $text): void
    {
        $width = $this->GetPageWidth();
        $height = $this->GetPageHeight();

        $this->SetFont('Helvetica', 'B', 34);
        $this->SetTextColor(216, 216, 216);
        $this->rotate(-45, $width / 2, $height / 2);
        $this->SetXY(-$width * 0.15, $height / 2 - 10);
        $this->Cell($width * 1.3, 20, $text, 0, 0, 'C');
        $this->rotate(0);

        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(130, 130, 130);
        $this->SetXY(12, $height - 24);
        $this->Cell($width - 24, 12, 'Licensed download for: '.$text, 0, 0, 'C');
    }

    /**
     * Rotate the coordinate system around the given point (in FPDF units).
     */
    private function rotate(float $angle, float $x = -1, float $y = -1): void
    {
        if ($x == -1) {
            $x = $this->GetX();
        }

        if ($y == -1) {
            $y = $this->GetY();
        }

        if ($this->angle != 0) {
            $this->_out('Q');
        }

        $this->angle = $angle;

        if ($angle == 0) {
            return;
        }

        $angle *= M_PI / 180;
        $cos = cos($angle);
        $sin = sin($angle);
        $cx = $x * $this->k;
        $cy = ($this->h - $y) * $this->k;

        $e = $cx - $cos * $cx + $sin * $cy;
        $f = $cy - $sin * $cx - $cos * $cy;

        $this->_out(sprintf(
            'q %.5F %.5F %.5F %.5F %.5F %.5F cm',
            $cos,
            $sin,
            -$sin,
            $cos,
            $e,
            $f,
        ));
    }
}
