<?php
namespace App\Core;

defined('APP') or exit;

/**
 * Dados iniciais da plataforma (evento, conteúdos, ingressos,
 * programação, FAQ, campos, templates, automações e perfis).
 * Tudo editável pelo painel — nenhum código precisa mudar.
 */
class Seed {
    public static function run($adminName = 'Administrador', $adminEmail = 'admin@evento.com', $adminPass = 'Admin@123') {
        $db = Database::class;

        // ---------- Perfis ----------
        $roles = [
            'admin' => ['Administrador', ['*']],
            'gestor' => ['Gestor', ['dashboard.view','registrations.view','registrations.edit','registrations.export','checkin.use','comms.view','comms.send','content.manage','event.manage','reports.view','audit.view']],
            'credenciamento' => ['Credenciamento', ['dashboard.view','registrations.view','checkin.use']],
            'comunicacao' => ['Comunicação', ['dashboard.view','registrations.view','comms.view','comms.send','reports.view']],
            'visualizacao' => ['Visualização', ['dashboard.view','registrations.view','reports.view']],
        ];
        $roleIds = [];
        foreach ($roles as $slug => $r) {
            $exists = $db::fetch('SELECT id FROM ' . $db::table('roles') . ' WHERE slug = ?', [$slug]);
            if ($exists) { $roleIds[$slug] = $exists['id']; continue; }
            $roleIds[$slug] = $db::insert('roles', [
                'slug' => $slug, 'name' => $r[0],
                'permissions' => json_encode($r[1], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        }

        // ---------- Usuário admin ----------
        $adminExists = $db::fetch('SELECT id FROM ' . $db::table('users') . ' WHERE email = ?', [$adminEmail]);
        if (!$adminExists) {
            $db::insert('users', [
                'role_id' => $roleIds['admin'],
                'name' => $adminName,
                'email' => $adminEmail,
                'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT, ['cost' => 12]),
                'active' => 1,
                'must_change_password' => 1,
                'created_at' => now(),
            ]);
        }

        // ---------- Evento ----------
        $event = $db::fetch('SELECT * FROM ' . $db::table('events') . ' ORDER BY id ASC LIMIT 1');
        if (!$event) {
            $eventId = $db::insert('events', [
                'name' => 'CRONNUS EXPERIENCE 2026',
                'tagline' => 'O movimento das empresas que decidiram substituir o caos pela excelência operacional.',
                'description' => "Um dia para conectar líderes, compartilhar cases reais, discutir o futuro da indústria e reconhecer quem faz a excelência acontecer.\n\nUm dia inteiro de inspiração, conexão e reconhecimento para líderes e empresas que querem transformar sua indústria em referência.",
                'date_start' => '2026-10-08',
                'date_end' => '2026-10-08',
                'time_text' => '08h às 17h',
                'countdown_target' => '2026-10-08 08:00:00',
                'venue_name' => 'Auditório da CPQD',
                'address' => 'Rua Dr. Ricardo Benetton Martins — Parque II do Polo de Alta Tecnologia',
                'city' => 'Campinas',
                'state' => 'SP',
                'map_url' => 'https://maps.google.com/?q=CPQD+Campinas+SP',
                'logo' => 'assets/img/logo-cronnus.svg',
                'contact_email' => '',
                'contact_whatsapp' => '',
                'contact_phone' => '',
                'status' => 'ACTIVE',
                'created_at' => now(),
            ]);
            $event = $db::fetch('SELECT * FROM ' . $db::table('events') . ' WHERE id = ?', [$eventId]);
        }
        $E = (int) $event['id'];

        // ---------- Conteúdo da landing ----------
        $content = [
            'hero' => [
                'badge' => 'VAGAS LIMITADAS · EVENTO POR CONVITE',
                'kicker' => '08 DE OUTUBRO DE 2026 · CAMPINAS — SP',
                'title' => 'CRONNUS',
                'subtitle' => 'Experience 2026',
                'description' => 'O movimento das empresas que decidiram substituir o caos pela excelência operacional.',
                'cta_primary' => 'GARANTA SUA PRESENÇA',
                'cta_secondary' => 'CONHECER O EVENTO',
                'date_text' => '08 DE OUTUBRO DE 2026',
                'venue_text' => "Auditório da CPQD\nCampinas — SP",
            ],
            'countdown' => [
                'title' => 'FALTAM',
                'label_days' => 'DIAS', 'label_hours' => 'HORAS',
                'label_minutes' => 'MINUTOS', 'label_seconds' => 'SEGUNDOS',
            ],
            'about' => [
                'kicker' => 'O MOVIMENTO',
                'title' => 'DO CAOS À EXCELÊNCIA OPERACIONAL',
                'text' => "A CRONNUS Experience é o encontro das empresas que decidiram substituir o caos pela excelência operacional.\n\nUm dia para conectar líderes, compartilhar cases reais, discutir o futuro da indústria e reconhecer quem faz a excelência acontecer — com conteúdo real, conexões de alto nível e resultados que ficam.",
                'differentials' => json_encode([
                    ['icon' => 'mic', 'title' => 'Palestras inspiradoras', 'text' => 'Conteúdo prático e visão que transforma.'],
                    ['icon' => 'users', 'title' => 'Conexões estratégicas', 'text' => 'Relacionamento que abre oportunidades.'],
                    ['icon' => 'star', 'title' => 'WCM Awards', 'text' => 'Reconhecendo quem faz a excelência acontecer.'],
                    ['icon' => 'chart', 'title' => 'Cases e resultados', 'text' => 'Aprenda com quem gera impacto real.'],
                ], JSON_UNESCAPED_UNICODE),
            ],
            'experience' => [
                'kicker' => 'O QUE ESPERAR',
                'title' => 'UM DIA QUE FICA',
                'subtitle' => 'Cinco pilares desenhados para um dia inesquecível.',
                'cards' => json_encode([
                    ['icon' => 'mic', 'title' => 'Palestra Magna', 'text' => 'Insights que despertam.'],
                    ['icon' => 'chat', 'title' => 'Cronnus Talks', 'text' => 'Conteúdos práticos que transformam.'],
                    ['icon' => 'users', 'title' => 'Cronnus Connect', 'text' => 'Relacionamento, rodada de negócios e conexões que geram resultados.'],
                    ['icon' => 'star', 'title' => 'WCM Awards', 'text' => 'Reconhecimento que inspira. Mais de 30 categorias premiadas.'],
                    ['icon' => 'target', 'title' => 'Tendências e Futuro', 'text' => 'Prepare sua operação para os próximos 10 anos.'],
                ], JSON_UNESCAPED_UNICODE),
            ],
            'schedule' => ['kicker' => 'PROGRAMAÇÃO', 'title' => 'O DIA, MOMENTO A MOMENTO', 'subtitle' => 'Das 08h às 17h · Horários sujeitos a pequenos ajustes na véspera.'],
            'speakers' => ['kicker' => 'PALCO', 'title' => 'CONVIDADOS ESPECIAIS', 'subtitle' => 'Lideranças que transformam a indústria.'],
            'venue' => [
                'kicker' => 'LOCAL', 'title' => 'ONDE A EXPERIÊNCIA ACONTECE',
                'button' => 'COMO CHEGAR',
                'info_title' => 'Informações do local',
            ],
            'tickets' => [
                'kicker' => 'CONVITES', 'title' => 'GARANTA SUA PRESENÇA',
                'subtitle' => 'Evento exclusivo por convite · Vagas limitadas.',
                'button' => 'ESCOLHER CONVITE',
            ],
            'faq' => ['kicker' => 'DÚVIDAS', 'title' => 'PERGUNTAS FREQUENTES', 'subtitle' => 'Tudo o que você precisa saber antes do grande dia.'],
            'register' => [
                'kicker' => 'INSCRIÇÃO · POR CONVITE', 'title' => 'GARANTA SUA PRESENÇA',
                'subtitle' => 'Vagas limitadas. Preencha seus dados com atenção — seu ticket digital chega em seguida.',
                'button' => 'CONFIRMAR INSCRIÇÃO',
                'success_title' => 'INSCRIÇÃO CONFIRMADA',
                'success_message' => 'Sua participação está confirmada. Você faz parte desse movimento.',
                'success_note' => 'Enviamos os detalhes da sua inscrição para seu e-mail.',
            ],
            'cta_final' => [
                'title' => 'RESERVE ESTA DATA. VOCÊ FAZ PARTE DESSE MOVIMENTO.',
                'subtitle' => '08 de outubro de 2026 · Auditório da CPQD · Campinas — SP',
                'button' => 'GARANTA SUA PRESENÇA',
            ],
            'footer' => [
                'about' => 'CRONNUS Experience 2026 — o movimento das empresas que decidiram substituir o caos pela excelência operacional.',
                'rights' => '© 2026 Cronnus Experience. Todos os direitos reservados.',
            ],
            'legal' => [
                'terms_title' => 'Termos de Inscrição',
                'terms' => "<p><strong>1. Inscrição.</strong> A inscrição é pessoal e vinculada ao convite, exceto mediante solicitação aprovada pela organização em até 7 dias antes do evento.</p><p><strong>2. Vagas.</strong> As vagas são limitadas e a confirmação está sujeita à disponibilidade do lote vigente.</p><p><strong>3. Cancelamento.</strong> Cancelamentos podem ser solicitados pelo e-mail de contato do evento.</p><p><strong>4. Programação.</strong> A organização pode ajustar horários, atrações e palestrantes sem aviso prévio, mantendo o padrão da experiência.</p><p><strong>5. Imagem.</strong> Ao participar, o inscrito autoriza o uso de sua imagem em registros do evento, salvo manifestação contrária por escrito.</p>",
                'privacy_title' => 'Política de Privacidade',
                'privacy' => "<p>Coletamos apenas os dados necessários para sua inscrição, credenciamento e comunicação sobre o evento, em conformidade com a LGPD (Lei nº 13.709/2018).</p><p><strong>Dados coletados:</strong> identificação, contato, dados profissionais e respostas do formulário.</p><p><strong>Uso:</strong> gestão da inscrição, emissão de ticket, check-in e comunicações transacionais. Marketing apenas com seu consentimento.</p><p><strong>Direitos:</strong> você pode solicitar acesso, correção ou exclusão dos seus dados pelo e-mail de contato do evento.</p><p><strong>Segurança:</strong> aplicamos controles de acesso, criptografia em trânsito e registros de auditoria.</p>",
            ],
            'theme' => [
                'gold' => '#D4A017', 'gold_light' => '#F2C14E',
                'bg' => '#080808', 'bg2' => '#111111',
                'font_head' => 'Cormorant Garamond', 'font_body' => 'Inter',
            ],
        ];
        foreach ($content as $section => $pairs) {
            foreach ($pairs as $k => $v) {
                $exists = $db::fetch(
                    'SELECT id FROM ' . $db::table('event_content') . ' WHERE event_id = ? AND section = ? AND ckey = ?',
                    [$E, $section, $k]
                );
                if ($exists) continue;
                $db::insert('event_content', [
                    'event_id' => $E, 'section' => $section, 'ckey' => $k,
                    'cvalue' => $v, 'updated_at' => now(),
                ]);
            }
        }

        // ---------- Convites ----------
        $tickets = [
            ['CONVITE', 'Acesso completo à CRONNUS Experience 2026.', 0, 300, "Acesso ao evento\nCredenciamento\nPalestra magna e Cronnus Talks\nCoffee Cronnus Connect\nCertificado digital", 'ACTIVE', 0],
            ['CONVITE VIP', 'Experiência elevada para convidados especiais.', 0, 50, "Tudo do Convite\nAssentos reservados\nLounge VIP\nKit exclusivo", 'ACTIVE', 1],
        ];
        if (!$db::fetch('SELECT id FROM ' . $db::table('ticket_types') . ' WHERE event_id = ? LIMIT 1', [$E])) {
            foreach ($tickets as $t) {
                $db::insert('ticket_types', [
                    'event_id' => $E, 'name' => $t[0], 'description' => $t[1],
                    'price' => $t[2], 'quantity' => $t[3], 'benefits' => $t[4],
                    'status' => $t[5], 'sort_order' => $t[6], 'created_at' => now(),
                ]);
            }
        }

        // ---------- Programação (oficial) ----------
        $sched = [
            ['08h00 — 08h30', 'Credenciamento e Boas-Vindas', 'Recepção dos convidados e abertura oficial do dia.', null, 'Auditório da CPQD'],
            ['08h45 — 09h20', 'Palestra Magna / O Despertar', 'O fim da gestão baseada em apagar incêndios: os erros que impedem uma operação de atingir classe mundial.', null, 'Palco Cronnus'],
            ['09h25 — 09h45', 'Cronnus Talk 01 / A Decisão', 'Por que as transformações morrem depois da implantação?', null, 'Palco Cronnus'],
            ['09h45 — 10h05', 'Cronnus Talk 02 / A Liderança', 'A liderança que constrói empresas de classe mundial.', null, 'Palco Cronnus'],
            ['10h05 — 10h35', 'Coffee: Cronnus Connect', 'Rodada de negócios — conexões e relacionamento que geram resultados.', null, 'Espaço Connect'],
            ['10h35 — 10h55', 'Cronnus Talk 04 / A Transformação', 'Os bastidores de uma implantação de classe mundial.', null, 'Palco Cronnus'],
            ['10h55 — 11h15', 'Cronnus Talk 05 / O Legado', 'A cultura da excelência: o que diferencia empresas comuns das extraordinárias.', null, 'Palco Cronnus'],
            ['11h15 — 12h00', 'Cronnus Talk 06 / O Mercado', 'Por que crescimento comercial sem excelência operacional pode se transformar em caos. Sua empresa está preparada para entregar o que promete?', null, 'Palco Cronnus'],
        ];
        if (!$db::fetch('SELECT id FROM ' . $db::table('schedule_items') . ' WHERE event_id = ? LIMIT 1', [$E])) {
            $i = 0;
            foreach ($sched as $s) {
                $db::insert('schedule_items', [
                    'event_id' => $E, 'stime' => $s[0], 'title' => $s[1],
                    'description' => $s[2], 'speaker' => $s[3], 'slocation' => $s[4],
                    'sort_order' => $i++, 'image' => null,
                ]);
            }
        }

        // Palestrantes: cadastrados pelo painel (seção oculta enquanto vazia).

        // ---------- FAQ ----------
        $faqs = [
            ['Como faço minha inscrição?', 'Clique em "Garanta sua presença", escolha seu convite e preencha o formulário. Em segundos você recebe seu código e ticket digital.'],
            ['O evento é por convite?', 'Sim. A CRONNUS Experience é um evento exclusivo por convite, com vagas limitadas para preservar a qualidade da experiência.'],
            ['Onde será o evento?', 'No Auditório da CPQD, em Campinas/SP. Veja o mapa na seção "Local" desta página.'],
            ['Como receberei meu ticket?', 'Imediatamente após a inscrição: nesta tela, por e-mail e, se disponível, pelo WhatsApp. Guarde seu código — ele é sua entrada.'],
            ['Posso transferir minha inscrição?', 'Transferências podem ser solicitadas em até 7 dias antes do evento, sujeitas à aprovação da organização.'],
            ['Como funciona o check-in?', 'No dia, apresente o QR Code do seu ticket na entrada. Nossa equipe faz a leitura em segundos e libera seu acesso.'],
        ];
        if (!$db::fetch('SELECT id FROM ' . $db::table('faqs') . ' WHERE event_id = ? LIMIT 1', [$E])) {
            $i = 0;
            foreach ($faqs as $f) {
                $db::insert('faqs', ['event_id' => $E, 'question' => $f[0], 'answer' => $f[1], 'sort_order' => $i++]);
            }
        }

        // ---------- Campos do formulário ----------
        // [fname,label,ftype,required,section,map_column,options,placeholder]
        $fields = [
            ['name', 'Nome completo', 'text', 1, 'personal', 'name', null, 'Como está no seu documento'],
            ['social_name', 'Nome social (opcional)', 'text', 0, 'personal', 'social_name', null, 'Como prefere ser chamado(a)'],
            ['email', 'E-mail corporativo', 'email', 1, 'personal', 'email', null, 'voce@empresa.com'],
            ['phone', 'Telefone', 'tel', 1, 'personal', 'phone', null, '(19) 99999-9999'],
            ['whatsapp', 'WhatsApp', 'tel', 0, 'personal', 'whatsapp', null, '(19) 99999-9999'],
            ['cpf', 'CPF', 'text', 1, 'personal', 'cpf', null, '000.000.000-00'],
            ['birthdate', 'Data de nascimento', 'date', 0, 'personal', 'birthdate', null, null],
            ['company', 'Empresa', 'text', 1, 'personal', 'company', null, 'Onde você trabalha'],
            ['role', 'Cargo', 'text', 1, 'personal', 'role', null, 'Seu cargo'],
            ['city', 'Cidade', 'text', 0, 'personal', 'city', null, null],
            ['state', 'Estado (UF)', 'text', 0, 'personal', 'state', null, 'SP'],
            ['cep', 'CEP', 'text', 0, 'address', 'cep', null, '13000-000'],
            ['street', 'Rua / Avenida', 'text', 0, 'address', 'street', null, null],
            ['number', 'Número', 'text', 0, 'address', 'number', null, null],
            ['complement', 'Complemento', 'text', 0, 'address', 'complement', null, 'Apto, bloco...'],
            ['district', 'Bairro', 'text', 0, 'address', 'district', null, null],
            ['addr_city', 'Cidade', 'text', 0, 'address', 'addr_city', null, null],
            ['addr_state', 'Estado (UF)', 'text', 0, 'address', 'addr_state', null, null],
            ['how_found', 'Como ficou sabendo do evento?', 'select', 0, 'extra', null, 'Instagram|Facebook|Google|WhatsApp|Indicação|Outro', null],
            ['prev_editions', 'Você participou da edição 2025?', 'radio', 0, 'extra', null, 'Sim|Não', null],
        ];
        if (!$db::fetch('SELECT id FROM ' . $db::table('registration_fields') . ' WHERE event_id = ? LIMIT 1', [$E])) {
            $i = 0;
            foreach ($fields as $f) {
                $db::insert('registration_fields', [
                    'event_id' => $E, 'fname' => $f[0], 'label' => $f[1], 'ftype' => $f[2],
                    'required' => $f[3], 'section' => $f[4], 'map_column' => $f[5],
                    'options' => $f[6], 'placeholder' => $f[7], 'active' => 1, 'sort_order' => $i++,
                ]);
            }
        }

        // ---------- Templates de mensagem ----------
        $tpls = [
            ['welcome', 'Boas-vindas', 'both', 'Sua presença está garantida!',
             "Olá {{nome}},\n\nÉ um prazer confirmar sua presença na {{evento}}.\n\nSeu código de inscrição:\n{{codigo_inscricao}}\n\nGuarde esse código para o dia do evento.\n\nSeu ticket:\n{{ticket_url}}\n\nData: {{data_evento}} — {{horario}}\nLocal: {{local}}\n\nVocê faz parte desse movimento. Até breve!"],
            ['confirmed', 'Inscrição confirmada', 'both', 'Inscrição confirmada — {{evento}}',
             "Olá {{nome}}!\n\nSua inscrição para {{evento}} foi confirmada.\n\nCódigo: {{codigo_inscricao}}\nConvite: {{tipo_ingresso}}\nData: {{data_evento}}\nLocal: {{local}}\n\nSeu ticket: {{ticket_url}}\n\nAté lá!"],
            ['payment_approved', 'Pagamento aprovado', 'both', 'Pagamento aprovado',
             "Olá {{nome}},\n\nSeu pagamento para {{evento}} foi aprovado. Sua vaga está garantida!\n\nCódigo: {{codigo_inscricao}}\nTicket: {{ticket_url}}"],
            ['reminder', 'Lembrete do evento', 'both', 'Lembrete: {{evento}} se aproxima',
             "Olá {{nome}}!\n\nFaltam poucos dias para {{evento}}.\n\n{{data_evento}} — {{horario}}\n{{local}}\n\nSeu código: {{codigo_inscricao}}\nTicket: {{ticket_url}}\n\nNos vemos em breve!"],
            ['tomorrow', 'Evento amanhã', 'both', 'Amanhã é dia de {{evento}}!',
             "Olá {{nome}}!\n\nÉ amanhã! {{evento}} acontece {{data_evento}}, {{horario}}, em {{local}}.\n\nTenha seu QR Code em mãos: {{ticket_url}}\n\nEstamos esperando por você."],
            ['today', 'Evento hoje', 'both', 'Hoje é dia de {{evento}}!',
             "Olá {{nome}}!\n\nChegou o grande dia! {{evento}} — hoje, {{horario}}, em {{local}}.\n\nApresente este QR Code na entrada: {{ticket_url}}\n\nBoa experiência!"],
            ['ticket', 'Reenvio de ticket', 'both', 'Seu ticket — {{evento}}',
             "Olá {{nome}}!\n\nAqui está seu ticket para {{evento}}:\n{{ticket_url}}\n\nCódigo: {{codigo_inscricao}} | Convite: {{tipo_ingresso}}"],
            ['cancelled', 'Cancelamento', 'both', 'Inscrição cancelada',
             "Olá {{nome}},\n\nSua inscrição para {{evento}} (código {{codigo_inscricao}}) foi cancelada conforme solicitado.\n\nSe foi um engano, fale conosco. Esperamos você numa próxima edição!"],
            ['thanks', 'Agradecimento', 'both', 'Obrigado por estar conosco!',
             "Olá {{nome}}!\n\nObrigado por fazer parte de {{evento}}. Sua presença tornou o dia especial.\n\nAté a próxima edição!"],
            ['post_event', 'Pós-evento', 'both', 'Como foi sua experiência?',
             "Olá {{nome}}!\n\n{{evento}} foi incrível graças a você. Conte pra gente como foi sua experiência respondendo a este e-mail.\n\nNos vemos na próxima edição!"],
        ];
        if (!$db::fetch('SELECT id FROM ' . $db::table('message_templates') . ' WHERE event_id = ? LIMIT 1', [$E])) {
            foreach ($tpls as $t) {
                $db::insert('message_templates', [
                    'event_id' => $E, 'tkey' => $t[0], 'name' => $t[1],
                    'channel' => $t[2], 'subject' => $t[3], 'body' => $t[4],
                    'active' => 1, 'created_at' => now(),
                ]);
            }
        }

        // ---------- Automações ----------
        $autos = [
            ['after_register', 'Boas-vindas após inscrição', 'both', 'Sua presença está garantida!',
             "Olá {{nome}},\n\nÉ um prazer confirmar sua presença na {{evento}}.\n\nSeu código de inscrição:\n{{codigo_inscricao}}\n\nSeu ticket:\n{{ticket_url}}\n\nData: {{data_evento}} — {{horario}}\nLocal: {{local}}\n\nVocê faz parte desse movimento. Até breve!", 0, 1],
            ['after_payment', 'Pagamento aprovado', 'both', 'Pagamento aprovado',
             "Olá {{nome}},\n\nSeu pagamento para {{evento}} foi aprovado. Sua vaga está garantida!\n\nCódigo: {{codigo_inscricao}}\nTicket: {{ticket_url}}", 0, 1],
            ['d7_before', 'Lembrete — 7 dias antes', 'both', 'Falta 1 semana para {{evento}}',
             "Olá {{nome}}!\n\nFalta uma semana para {{evento}}!\n\n{{data_evento}} — {{horario}}\n{{local}}\n\nSeu ticket: {{ticket_url}}", 7, 1],
            ['d1_before', 'Lembrete — 1 dia antes', 'both', 'Amanhã é dia de {{evento}}!',
             "Olá {{nome}}!\n\nÉ amanhã! {{evento}} acontece {{data_evento}}, {{horario}}, em {{local}}.\n\nTenha seu QR Code em mãos: {{ticket_url}}", 1, 1],
            ['day_of', 'No dia do evento', 'both', 'Hoje é dia de {{evento}}!',
             "Olá {{nome}}!\n\nChegou o grande dia! Hoje, {{horario}}, em {{local}}.\n\nApresente este QR Code na entrada: {{ticket_url}}", 0, 1],
            ['after_checkin', 'Boas-vindas no check-in', 'whatsapp', null,
             "Olá {{nome}}! Check-in realizado com sucesso. Aproveite cada momento da {{evento}}. Boa experiência!", 0, 0],
            ['on_cancel', 'Confirmação de cancelamento', 'both', 'Inscrição cancelada',
             "Olá {{nome}},\n\nSua inscrição para {{evento}} (código {{codigo_inscricao}}) foi cancelada.\n\nEsperamos você numa próxima edição!", 0, 1],
        ];
        if (!$db::fetch('SELECT id FROM ' . $db::table('automations') . ' WHERE event_id = ? LIMIT 1', [$E])) {
            foreach ($autos as $a) {
                $db::insert('automations', [
                    'event_id' => $E, 'trigger' => $a[0], 'name' => $a[1],
                    'channel' => $a[2], 'subject' => $a[3], 'body' => $a[4],
                    'days_before' => $a[5], 'active' => $a[6], 'created_at' => now(),
                ]);
            }
        }

        // ---------- Integrações (inativas por padrão) ----------
        if (!$db::fetch('SELECT id FROM ' . $db::table('email_settings') . ' WHERE event_id = ? LIMIT 1', [$E])) {
            $db::insert('email_settings', [
                'event_id' => $E,
                'host' => env('MAIL_HOST', ''), 'port' => (int) env('MAIL_PORT', 587),
                'username' => env('MAIL_USER', ''), 'password_enc' => '',
                'encryption' => env('MAIL_ENC', 'tls') ?: 'tls',
                'from_email' => env('MAIL_FROM', ''), 'from_name' => env('MAIL_FROM_NAME', 'Cronnus Experience'),
                'active' => 0, 'updated_at' => now(),
            ]);
        }
        if (!$db::fetch('SELECT id FROM ' . $db::table('whatsapp_settings') . ' WHERE event_id = ? LIMIT 1', [$E])) {
            $db::insert('whatsapp_settings', [
                'event_id' => $E, 'provider' => 'meta_cloud',
                'phone_number_id' => env('WA_PHONE_NUMBER_ID', ''),
                'access_token_enc' => '',
                'business_number' => env('WA_BUSINESS_NUMBER', ''),
                'template_lang' => 'pt_BR', 'active' => 0, 'updated_at' => now(),
            ]);
        }

        $db::setSetting('installed', '1');
        $db::setSetting('seeded_at', now());
        return $E;
    }
}
