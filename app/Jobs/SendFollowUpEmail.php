<?php

namespace App\Jobs;

use App\Mail\FollowUpMail;
use App\Models\Deal;
use App\Models\DealFollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendFollowUpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /* ─── 10 templates de follow-up diferentes ─── */
    const TEMPLATES = [
        "Olá,\n\nEspero que esteja bem. Queria saber se teve oportunidade de analisar a nossa proposta e se tem alguma questão que possamos esclarecer.\n\nEstamos ao dispor.",
        "Bom dia,\n\nSó a verificar se recebeu a nossa proposta e se existe algo que possamos clarificar ou ajustar.\n\nNão hesite em contactar-nos.",
        "Olá,\n\nEstou a fazer um breve seguimento para perceber se a nossa proposta vai ao encontro das suas necessidades.\n\nFica à vontade para partilhar qualquer feedback.",
        "Bom dia,\n\nQueria apenas confirmar que a nossa proposta chegou corretamente e saber se tem alguma dúvida.\n\nEstamos disponíveis para uma chamada rápida se preferir.",
        "Olá,\n\nPassaram alguns dias desde que enviámos a nossa proposta. Existe algum ponto que gostasse de discutir ou esclarecer?\n\nFicamos ao aguardo do seu contacto.",
        "Bom dia,\n\nEspero que esteja tudo bem. Gostaríamos de saber se já teve oportunidade de analisar a nossa proposta e se tem algum comentário.\n\nEstamos disponíveis para ajudar.",
        "Olá,\n\nEstou a fazer um ponto de situação relativamente à nossa proposta. Há algo que possamos melhorar ou adaptar às suas necessidades?\n\nFique à vontade para nos contactar.",
        "Bom dia,\n\nSó a confirmar se a nossa proposta ainda está em análise ou se existe alguma decisão já tomada.\n\nEstamos ao dispor para qualquer esclarecimento.",
        "Olá,\n\nEspero que a semana esteja a correr bem. Queríamos verificar se existe alguma atualização relativamente à nossa proposta.\n\nAguardamos o seu retorno.",
        "Bom dia,\n\nQueria assegurar-me que tem todo o apoio necessário para tomar uma decisão. Estamos disponíveis para reunir ou esclarecer qualquer dúvida.\n\nFicamos ao aguardo.",
    ];

    public function __construct(
        public DealFollowUp $followUp
    ) {}

    public function handle(): void
    {
        $followUp = $this->followUp->fresh();

        /* ─── Verifica se ainda deve enviar ─── */
        if (!$followUp || !$followUp->active) return;
        if ($followUp->deal->stage !== 'follow_up') {
            $followUp->update(['active' => false]);
            return;
        }

        /* ─── Escolhe template rotativo ─── */
        $templateIndex = $followUp->emails_sent % count(self::TEMPLATES);
        $body          = self::TEMPLATES[$templateIndex];

        /* ─── Envia email ─── */
        Mail::to($followUp->email)
            ->send(new FollowUpMail($followUp->deal, $body, $followUp->emails_sent + 1));

        /* ─── Regista na cronologia ─── */
        $followUp->deal->activities()->create([
            'user_id'     => $followUp->user_id,
            'type'        => 'email',
            'title'       => 'Follow-up automático #' . ($followUp->emails_sent + 1),
            'description' => 'Enviado para ' . $followUp->email,
            'completed'   => true,
        ]);

        /* ─── Agenda próximo envio em 2 dias úteis ─── */
        $nextSend = now()->addDays(2);

        /* Respeita horário de trabalho (9h-18h, seg-sex) */
        if ($nextSend->isWeekend()) {
            $nextSend = $nextSend->nextWeekday();
        }
        $nextSend->setHour(9)->setMinute(0)->setSecond(0);

        $followUp->update([
            'emails_sent'  => $followUp->emails_sent + 1,
            'next_send_at' => $nextSend,
        ]);

        /* ─── Agenda próxima execução ─── */
        self::dispatch($followUp)->delay($nextSend);
    }
}