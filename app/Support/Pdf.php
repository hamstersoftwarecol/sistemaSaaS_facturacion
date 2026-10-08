<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class Pdf
{
    public static function render(string $view, array $data = []): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->setChroot(base_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data + ['pdf' => true])->render(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }

    public static function response(string $view, array $data, string $filename, bool $download = false): Response
    {
        return response(static::render($view, $data), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
        ]);
    }
}
