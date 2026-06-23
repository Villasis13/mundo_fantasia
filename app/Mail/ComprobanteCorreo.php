<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComprobanteCorreo extends Mailable
{
    use Queueable, SerializesModels;
    use SerializesModels;

    private $correo_corporativo;
    private $comprobante;
    private $url_xml;
    private $url_cdr;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($comprobante,$correo,$rutaXML,$rutaCDR)
    {
        $this->correo_corporativo = $correo;
        $this->comprobante = $comprobante;
        $this->url_xml = $rutaXML;
        $this->url_cdr = $rutaCDR;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Comprobante de venta ',
        );
    }
    public function build()
    {
        $mensaje = "Estimado cliente, Le agradecemos sinceramente por su preferencia y la confianza depositada en nosotros. Adjunto a este correo encontrará su comprobante de venta correspondiente a la compra realizada. Esperamos que su experiencia haya sido satisfactoria y quedamos a su disposición para cualquier consulta o requerimiento adicional. ¡Gracias por elegirnos!";
        $mail = $this
            ->from(env('MAIL_USERNAME'), 'Notificaciones')
            ->subject('Envío de comprobante de venta')
            ->view('emails.ComprobanteCorreo')
            ->with([
                'subject' => "Envío de comprobante de venta",
                'messageBody' => $mensaje,
                'remitente' => env('APP_NAME'),
            ]);
        // PDF
        if (!empty($this->comprobante) && file_exists($this->comprobante)) {
            $mail->attach($this->comprobante, [
                'as' => 'comprobante.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        // XML (comprobante)
        if (!empty($this->url_xml) && file_exists($this->url_xml)) {
            $mail->attach($this->url_xml, [
                'as' => 'comprobante.xml',
                'mime' => 'application/xml',
            ]);
        }

        // CDR (XML)
        if (!empty($this->url_cdr) && file_exists($this->url_cdr)) {
            $mail->attach($this->url_cdr, [
                'as' => 'cdr.xml',
                'mime' => 'application/xml',
            ]);
        }

        return $mail;
    }
    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.ComprobanteCorreo',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
