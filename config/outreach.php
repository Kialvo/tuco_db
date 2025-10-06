<?php

return [
    'languages' => [
        'en' => 'English',
        'it' => 'Italian',
        'es' => 'Spanish',
        'pt' => 'Portuguese',
        'de' => 'German',
        'fr' => 'French',
        'sq' => 'Albanian',
        'cs' => 'Czech',
    ],

    'sensitive_line' => [
        'en' => ' and that the rate for an article on sensitive topics is [special topic price]?',
        'it' => ' e che la tariffa per un articolo su argomenti sensibili è [special topic price]?',
        'es' => ' y que la tarifa para un artículo sobre temas sensibles es [special topic price]?',
        'pt' => ' e que a tarifa para um artigo sobre temas sensíveis é [special topic price]?',
        'de' => ' und dass der Preis für einen Artikel zu sensiblen Themen [special topic price] beträgt?',
        'fr' => ' et que le tarif pour un article sur des sujets sensibles est de [special topic price] ?',
        'sq' => ' dhe që tarifa për një artikull me tema të ndjeshme është [special topic price]?',
        'cs' => ' a že sazba za článek na citlivá témata je [special topic price]?',
    ],
    // Template subjects & bodies.
    // Use tokens:
    // [domain] [publisher price] [publisher_price] [special topic price] [special_topic_price]
    // {{sensitive_line}}  ← will be included or removed per-recipient
    'templates' => [

        'en' => [
            'first' => [
                'subject' => 'Quick confirmation on guest post rates',
                'body' => <<<TXT
Hi,

I hope you’re doing well. This is Martina from Menford.

We’ve already collaborated with you on [domain] and really appreciated the experience.

I’m reaching out to confirm your current rates for publishing a guest post with a permanent dofollow link, without the sponsored tag. Could you please confirm that the rate for a standard article is [publisher price]{{sensitive_line}}?

Looking forward to your reply so I can offer your site to our client.

Best,
Martina
TXT,
                'sensitive_line' => ', and that the rate for an article on sensitive topics is [special topic price]'
            ],
            'followup' => [
                'subject' => 'Following up on guest post rates',
                'body' => <<<TXT
Hi,

Just following up on my previous email — could you confirm your current rates for a guest post with a permanent dofollow link?

It would help me propose your site to our client.

Best,
Martina
TXT
            ],
        ],

        'it' => [
            'first' => [
                'subject' => 'Conferma rapida delle tariffe per guest post',
                'body' => <<<TXT
Buongiorno,

Spero vada tutto bene. Sono Martina di Menford.

Abbiamo già collaborato con voi su [domain] e abbiamo apprezzato l’esperienza.

Vi scrivo per confermare le tariffe attuali per la pubblicazione di un guest post con link dofollow permanente, senza tag sponsored. Potreste confermare che la tariffa per un articolo standard è [publisher price]{{sensitive_line}}?

Resto in attesa di un vostro riscontro così da proporre il vostro sito al nostro cliente.

Un saluto,
Martina
TXT,
                'sensitive_line' => ', e che la tariffa per un articolo su temi sensibili è [special topic price]'
            ],
            'followup' => [
                'subject' => 'Follow-up: tariffe per guest post',
                'body' => <<<TXT
Buongiorno,

Riprendo la mia precedente email — potreste confermare le vostre tariffe attuali per un guest post con link dofollow permanente?

Mi aiuterebbe a proporre il vostro sito al nostro cliente.

Un saluto,
Martina
TXT
            ],
        ],

        'es' => [
            'first' => [
                'subject' => 'Confirmación rápida de tarifas para guest post',
                'body' => <<<TXT
Hola,

Espero que estés bien. Soy Martina de Menford.

Ya hemos colaborado contigo en [domain] y apreciamos mucho la experiencia.

Te escribo para confirmar tus tarifas actuales para publicar un guest post con enlace dofollow permanente, sin la etiqueta sponsored. ¿Podrías confirmar que la tarifa para un artículo estándar es [publisher price]{{sensitive_line}}?

Quedo atenta a tu respuesta para poder proponer tu sitio a nuestro cliente.

Saludos,
Martina
TXT,
                'sensitive_line' => ', y que la tarifa para un artículo de temas sensibles es [special topic price]'
            ],
            'followup' => [
                'subject' => 'Seguimiento sobre tarifas de guest post',
                'body' => <<<TXT
Hola,

Solo para dar seguimiento a mi correo anterior: ¿podrías confirmar tus tarifas actuales para un guest post con enlace dofollow permanente?

Me ayudaría a proponer tu sitio a nuestro cliente.

Saludos,
Martina
TXT
            ],
        ],

        'pt' => [
            'first' => [
                'subject' => 'Confirmação rápida das tarifas de guest post',
                'body' => <<<TXT
Olá,

Espero que esteja bem. Aqui é a Martina, da Menford.

Já colaboramos com você em [domain] e apreciamos a experiência.

Escrevo para confirmar suas tarifas atuais para publicar um guest post com link dofollow permanente, sem a tag sponsored. Poderia confirmar que o valor para um artigo padrão é [publisher price]{{sensitive_line}}?

Fico no aguardo para poder propor seu site ao nosso cliente.

Atenciosamente,
Martina
TXT,
                'sensitive_line' => ', e que o valor para um artigo sobre temas sensíveis é [special topic price]'
            ],
            'followup' => [
                'subject' => 'Retomando: tarifas de guest post',
                'body' => <<<TXT
Olá,

Apenas retomando meu e-mail anterior — poderia confirmar suas tarifas atuais para um guest post com link dofollow permanente?

Isso me ajudará a propor seu site ao nosso cliente.

Atenciosamente,
Martina
TXT
            ],
        ],

        'de' => [
            'first' => [
                'subject' => 'Kurze Bestätigung der Gastbeitragsraten',
                'body' => <<<TXT
Hallo,

ich hoffe, es geht Ihnen gut. Hier ist Martina von Menford.

Wir haben bereits mit Ihnen auf [domain] zusammengearbeitet und die Zusammenarbeit sehr geschätzt.

Ich möchte Ihre aktuellen Preise für die Veröffentlichung eines Gastbeitrags mit dauerhaftem Dofollow-Link ohne Sponsored-Tag bestätigen. Können Sie bestätigen, dass der Preis für einen Standardartikel [publisher price] beträgt{{sensitive_line}}?

Ich freue mich auf Ihre Rückmeldung, damit ich Ihre Seite unserem Kunden vorschlagen kann.

Viele Grüße
Martina
TXT,
                'sensitive_line' => ', und dass der Preis für einen Artikel zu sensiblen Themen [special topic price] beträgt'
            ],
            'followup' => [
                'subject' => 'Rückfrage zu Gastbeitragsraten',
                'body' => <<<TXT
Hallo,

ich wollte kurz an meine vorherige E-Mail erinnern — könnten Sie Ihre aktuellen Preise für einen Gastbeitrag mit dauerhaftem Dofollow-Link bestätigen?

Das hilft mir, Ihre Seite unserem Kunden vorzuschlagen.

Viele Grüße
Martina
TXT
            ],
        ],

        'fr' => [
            'first' => [
                'subject' => 'Confirmation rapide des tarifs pour article invité',
                'body' => <<<TXT
Bonjour,

J’espère que vous allez bien. Ici Martina de Menford.

Nous avons déjà collaboré avec vous sur [domain] et avons beaucoup apprécié l’expérience.

Je vous contacte pour confirmer vos tarifs actuels pour la publication d’un article invité avec lien dofollow permanent, sans la mention sponsored. Pourriez-vous confirmer que le tarif pour un article standard est [publisher price]{{sensitive_line}} ?

Dans l’attente de votre retour afin de proposer votre site à notre client.

Cordialement,
Martina
TXT,
                'sensitive_line' => ', et que le tarif pour un article sur des sujets sensibles est de [special topic price]'
            ],
            'followup' => [
                'subject' => 'Relance : tarifs pour article invité',
                'body' => <<<TXT
Bonjour,

Je me permets de revenir vers vous — pourriez-vous confirmer vos tarifs actuels pour un article invité avec lien dofollow permanent ?

Cela m’aidera à proposer votre site à notre client.

Cordialement,
Martina
TXT
            ],
        ],

        'sq' => [
            'first' => [
                'subject' => 'Konfirmim i shpejtë i çmimeve për guest post',
                'body' => <<<TXT
Përshëndetje,

Shpresoj të jeni mirë. Jam Martina nga Menford.

Kemi bashkëpunuar më parë me ju në [domain] dhe e vlerësuam shumë përvojën.

Po ju shkruaj për të konfirmuar çmimet aktuale për publikimin e një guest post me link dofollow të përhershëm, pa etiketën sponsored. A mund të konfirmoni që çmimi për një artikull standard është [publisher price]{{sensitive_line}}?

Pres përgjigjen tuaj që ta propozoj faqen tuaj te klienti ynë.

Të fala,
Martina
TXT,
                'sensitive_line' => ', dhe që çmimi për një artikull me tema të ndjeshme është [special topic price]'
            ],
            'followup' => [
                'subject' => 'Rikujtim: çmimet për guest post',
                'body' => <<<TXT
Përshëndetje,

Po rikujtoj emailin tim të mëparshëm — a mund të konfirmoni çmimet tuaja aktuale për një guest post me link dofollow të përhershëm?

Kjo do të më ndihmojë ta propozoj faqen tuaj te klienti ynë.

Të fala,
Martina
TXT
            ],
        ],

        'cs' => [
            'first' => [
                'subject' => 'Rychlé potvrzení cen za guest post',
                'body' => <<<TXT
Dobrý den,

doufám, že se máte dobře. Zde Martina z Menford.

Už jsme s vámi spolupracovali na [domain] a velmi si toho vážíme.

Píšu kvůli potvrzení vašich aktuálních cen za publikaci guest postu s trvalým dofollow odkazem, bez štítku sponsored. Můžete prosím potvrdit, že cena za standardní článek je [publisher price]{{sensitive_line}}?

Děkuji za odpověď, abych mohla váš web nabídnout našemu klientovi.

S pozdravem
Martina
TXT,
                'sensitive_line' => ', a že cena za článek na citlivá témata je [special topic price]'
            ],
            'followup' => [
                'subject' => 'Připomenutí: ceny za guest post',
                'body' => <<<TXT
Dobrý den,

navazuji na předchozí e-mail — mohli byste prosím potvrdit vaše aktuální ceny za guest post s trvalým dofollow odkazem?

Pomůže mi to nabídnout váš web našemu klientovi.

S pozdravem
Martina
TXT
            ],
        ],
    ],
];
