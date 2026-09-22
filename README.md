# Plataforma de Evento Premium 🖤💛

Ecossistema completo para divulgação, inscrição, gerenciamento e check-in de eventos de alto padrão —
landing page cinematográfica + painel administrativo SaaS + ticket digital com QR Code + comunicação multicanal.

Feito para rodar em **hospedagem simples** (cPanel/compartilhada): **PHP 7.4+ puro, sem Composer, sem build, sem Node**.
Banco **SQLite** (zero configuração) ou **MySQL**.

---

## ✨ O que está incluído

### Landing page pública
- Hero cinematográfico, contador regressivo configurável, sobre, experiência, programação (timeline),
  palestrantes, local com mapa, ingressos (Standard/Premium/VIP, gratuito ou pago), FAQ, CTA final e footer
- Identidade black + gold, tipografia serifada + sans, microanimações, mobile-first, 100% editável pelo painel

### Inscrição & Ticket
- Formulário dinâmico (campos configuráveis: texto, e-mail, tel, número, data, select, radio, checkbox, textarea; obrigatórios/opcionais)
- Aceites LGPD (nunca pré-marcados), validação de CPF/e-mail/telefone, anti-duplicidade
- Código único `EVT-2026-XXXXXX` + ticket digital com **QR Code seguro** (token, sem dados pessoais)
- Página de sucesso, `/ticket/{token}`, recuperação de ticket, `.ics` (adicionar à agenda), reenvio por e-mail/WhatsApp

### Painel administrativo (`/admin`)
- **Dashboard** em tempo real: inscritos, check-ins, pendentes, cancelados, vagas, receita + gráficos
- **Inscrições**: busca instantânea, filtros, perfil completo com timeline, confirmar/cancelar, pagamento, LGPD (excluir dados), exportação **CSV/Excel/PDF(print)**
- **Check-in**: leitor de QR Code (câmera), busca manual, validação (válido/já utilizado/cancelado/não encontrado), contadores ao vivo
- **Comunicação**: editor com variáveis `{{nome}}` `{{codigo_inscricao}}` `{{ticket_url}}`…, templates, automações
  (boas-vindas, pagamento, 7 dias, 1 dia, dia do evento, pós check-in, cancelamento), disparo em massa com
  segmentação + confirmação, **fila** com tentativas, histórico, SMTP configurável com teste, **WhatsApp Business (Meta Cloud API)** oficial
- **Conteúdo**: evento, hero, sobre, experiência, programação, palestrantes, ingressos, FAQ, formulário, identidade/cores, termos e privacidade — tudo sem código
- **Relatórios**, **Usuários** (5 perfis com permissões), **Auditoria**, **Configurações**

### API
`POST /api/register` · `GET /api/event` · `POST /api/ticket/resend` · `GET /ticket/{token}` ·
`POST /api/admin/checkin-confirm` · `POST /api/admin/comms/bulk-send` · `GET /api/admin/dashboard-charts` …

---

## 🚀 Instalação em hospedagem simples (cPanel)

1. Envie **todos os arquivos** para a `public_html` (FTP ou Gerenciador de Arquivos).
2. Acesse `https://seudominio.com/install.php` e siga o assistente:
   - **SQLite** (recomendado): nenhuma configuração — o banco é criado sozinho em `storage/`.
   - **MySQL**: crie o banco no cPanel e informe host/usuário/senha no instalador.
3. Crie o usuário administrador → acesse `https://seudominio.com/admin`.
4. Pronto! Personalize tudo pelo painel (nenhum código precisa mudar).

> Requisitos: PHP 7.4+ com `pdo`, `pdo_sqlite` e/ou `pdo_mysql`, `mbstring`, `openssl`, `curl`, `json`, `fileinfo`.
> O instalador verifica tudo automaticamente.

### Cron (fila de mensagens + lembretes automáticos)
No cPanel → **Cron Jobs**, a cada 5 minutos:
```
php /home/SEU_USUARIO/public_html/cron.php SEU_TOKEN
```
(O token está no `.env` como `CRON_TOKEN`, gerado na instalação.)
Sem cron, o painel processa lotes automaticamente ao navegar + botão “Processar agora”.

### E-mail (SMTP)
Painel → **Comunicação → E-mail**: host, porta, usuário, senha, TLS/SSL, remetente → **Enviar teste**.
Na hospedagem cPanel, geralmente: `mail.seudominio.com`, porta `587` (TLS) ou `465` (SSL).

### WhatsApp (API oficial Meta)
Painel → **Comunicação → WhatsApp**: `Phone Number ID` + token da [Meta Cloud API](https://developers.facebook.com/docs/whatsapp/cloud-api) → **Enviar teste**.
Nenhum recurso usa WhatsApp Web/scraping — apenas API oficial.

---

## 👥 Perfis de acesso

| Perfil | Pode |
|---|---|
| **Administrador** | Tudo |
| **Gestor** | Inscrições, check-in, comunicação, conteúdo, relatórios, auditoria |
| **Credenciamento** | Check-in + visualizar inscritos |
| **Comunicação** | Mensagens + visualizar inscritos/relatórios |
| **Visualização** | Somente leitura |

## 🔒 Segurança & LGPD
- Senhas com `password_hash`, sessões seguras (HttpOnly/SameSite), CSRF, rate limiting, auditoria
- Prepared statements (anti SQL injection), escape de saída (anti XSS), uploads validados
- Segredos (SMTP/WhatsApp) **criptografados** (AES-256) e nunca exibidos
- Consentimentos registrados com data/IP, termos/privacidade editáveis, exclusão de dados por participante

## 🗂 Estrutura
```
index.php            → front controller + rotas
install.php          → instalador assistido   ·  cron.php → fila + lembretes
config/              → config.php, .env.example
app/Core/            → Database (SQLite/MySQL + migrations), Auth, Csrf, Event, Seed…
app/Services/        → Registration, Ticket, Checkin, Email (SMTP), WhatsApp, Message (fila), Stats, Export
app/Controllers/     → Public, Api, Admin, AdminApi, AdminContentApi, AdminCommsApi
views/public|admin/  → templates (landing, ticket, dashboard, check-in…)
assets/css|js|img/   → front-end (sem build)
storage/             → banco SQLite, logs (bloqueado via .htaccess)
uploads/             → imagens enviadas (execução de scripts bloqueada)
```

## 🔌 Integração futura de pagamentos
Inscrições pagas nascem com `payment_status=PENDING`. Conecte seu gateway (Mercado Pago, Stripe, PagSeguro…)
no webhook chamando a ação de pagamento (`/api/admin/registrations/{id}/payment` → `APPROVED`), que dispara a
automação “pagamento aprovado” automaticamente.

## 📄 Licença
Uso livre para o evento do contratante.
