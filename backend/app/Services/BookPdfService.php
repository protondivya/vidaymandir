<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Book;
use App\Services\Pdf\WatermarkPdf;
use FPDF;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BookPdfService
{
    private const DISK = 'local';

    private const BOOKS_DIR = 'books';

    /**
     * The number of pages in the given PDF file.
     */
    public function pageCount(string $sourcePath): int
    {
        return (new WatermarkPdf)->setSourceFile($sourcePath);
    }

    /**
     * Store an uploaded PDF for the book.
     */
    public function store(Book $book, UploadedFile $file): void
    {
        $disk = Storage::disk(self::DISK);
        $path = $disk->putFileAs(self::BOOKS_DIR, $file, $book->slug.'.pdf');

        $book->forceFill(['pdf_file' => $path])->save();
    }

    /**
     * Write a watermarked copy of the book's PDF to a temporary file and return its path.
     */
    public function watermark(Book $book, string $mark): string
    {
        $source = $book->pdfPath();

        if ($source === null) {
            throw new RuntimeException('Book has no PDF file.');
        }

        $pdf = new WatermarkPdf;
        $pageCount = $pdf->setSourceFile($source);

        for ($page = 1; $page <= $pageCount; $page++) {
            $templateId = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
            $pdf->stamp($mark);
        }

        $outputPath = $this->temporaryPath($book->slug);
        $pdf->Output($outputPath, 'F');

        return $outputPath;
    }

    /**
     * Generate a simple demo PDF for a seeded book.
     */
    public function generateDemoPdf(string $title, string $relativePath, int $pages = 10): void
    {
        $disk = Storage::disk(self::DISK);
        $absolutePath = $disk->path($relativePath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        $pdf = new FPDF;
        $pdf->SetAutoPageBreak(true, 24);

        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', 'B', 18);
            $pdf->Cell(0, 14, $title, 0, 1, 'C');
            $pdf->Ln(6);
            $pdf->SetFont('Helvetica', '', 12);

            foreach ($this->demoLines() as $line) {
                $pdf->MultiCell(0, 8, $line);
                $pdf->Ln(2);
            }

            $pdf->SetY(-24);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Cell(0, 10, "Page {$page} of {$pages}", 0, 0, 'C');
        }

        $pdf->Output($absolutePath, 'F');
    }

    /**
     * A reusable paragraph of placeholder text for generated demo PDFs.
     *
     * @return list<string>
     */
    private function demoLines(): array
    {
        return [
            'This document is a sample text render used to demonstrate the online reading and watermarking features of the Digital Free Library.',
            'It is generated locally by the demo seeder and contains no copyrighted material. The entire text is plain placeholder copy, repeated on every page so that the PDF has a realistic page count.',
            'When a reader downloads this book, the library stamps each page with the reader name and the date of download. That watermark travels with the file and discourages unauthorised redistribution.',
            'The library keeps track of how far each reader has advanced through the book, so a session can be resumed on any device.',
        ];
    }

    /**
     * Return a unique writable temporary file path for an output PDF.
     */
    private function temporaryPath(string $prefix): string
    {
        $file = tempnam(sys_get_temp_dir(), 'pdf_'.$prefix.'_');

        if ($file === false) {
            throw new RuntimeException('Could not create a temporary file.');
        }

        return $file.'.pdf';
    }
}
