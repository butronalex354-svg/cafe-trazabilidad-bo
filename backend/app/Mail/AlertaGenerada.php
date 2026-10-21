<?php

namespace App\Mail;

use App\Models\Alerta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaGenerada extends Mailable
{
    use Queueable, SerializesModels;

    private const TITULOS = [
        'plaga' => 'Se detectó una posible plaga o enfermedad',
        'suelo' => 'El pH de tu parcela está fuera de rango',
        'stock' => 'Un lote se quedó con poco stock',
    ];

    public function __construct(public Alerta $alerta)
    {
    }

    public function envelope(): Envelope
    {
        $titulo = self::TITULOS[$this->alerta->tipo] ?? 'Nueva alerta del sistema';

        return new Envelope(
            subject: "Café Trazabilidad: {$titulo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.alerta-generada',
            with: [
                'titulo' => self::TITULOS[$this->alerta->tipo] ?? 'Nueva alerta del sistema',
                'mensaje' => $this->alerta->mensaje,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
