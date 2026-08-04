<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MetadataDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $digest,
        private readonly array $digestAttachments
    ) {
    }

    public function build(): self
    {
        $mail = $this
            ->subject(__('hiko.metadata_digest_subject', [
                'from' => $this->digest['period_start']->format('d.m.Y H:i'),
                'to' => $this->digest['period_end']->format('d.m.Y H:i'),
            ]))
            ->view('emails.metadata-digest')
            ->text('emails.metadata-digest-text');

        foreach ($this->digestAttachments as $attachment) {
            $mail->attachData(
                $attachment['data'],
                $attachment['name'],
                ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            );
        }

        return $mail;
    }
}
