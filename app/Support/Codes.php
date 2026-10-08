<?php

namespace App\Support;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

/**
 * Generación de códigos QR (verificación de documentos) y códigos de barras (etiquetas).
 */
class Codes
{
    public static function qrSvg(string $text, int $size = 140): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));
        $svg = $writer->writeString($text);

        return preg_replace('/^<\?xml.*?\?>\s*/', '', $svg);
    }

    public static function qrDataUri(string $text, int $size = 220): string
    {
        $writer = new Writer(new GDLibRenderer($size, 1));

        return 'data:image/png;base64,'.base64_encode($writer->writeString($text));
    }

    public static function barcodeSvg(string $code, float $widthFactor = 1.6, int $height = 42): string
    {
        $generator = new BarcodeGeneratorSVG;

        return $generator->getBarcode(static::sanitize($code), $generator::TYPE_CODE_128, $widthFactor, $height);
    }

    public static function barcodeDataUri(string $code, int $widthFactor = 2, int $height = 50): string
    {
        $generator = new BarcodeGeneratorPNG;

        return 'data:image/png;base64,'.base64_encode(
            $generator->getBarcode(static::sanitize($code), $generator::TYPE_CODE_128, $widthFactor, $height)
        );
    }

    protected static function sanitize(string $code): string
    {
        $clean = preg_replace('/[^\x20-\x7E]/', '', $code);

        return $clean !== '' ? $clean : '0';
    }
}
