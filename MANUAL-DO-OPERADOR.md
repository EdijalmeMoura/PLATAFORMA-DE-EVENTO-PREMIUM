# Manual do Operador — Plataforma de Evento Premium

Guia prático para instalar, configurar, operar no dia do evento e encerrar.
Nenhum conhecimento de programação é necessário após a instalação.

---

## 1. Instalação (uma vez)

1. Envie todos os arquivos para a hospedagem (`public_html/` ou subpasta).
2. Acesse `https://seudominio.com.br/install.php`.
3. **Etapa 1** — confira os requisitos (tudo verde em "obrigatórios"),
   escolha o banco:
   - **SQLite** (recomendado): zero configuração, ideal até ~10 mil inscritos.
   - **MySQL**: preencha host/banco/usuário/senha (crie antes no cPanel).
4. **Etapa 2** — crie o administrador (e-mail + senha de 8+ caracteres).
5. Ao concluir: **apague o arquivo `install.php` do servidor** (segurança).

> Reinstalar depois: só com o token do cron —
> `install.php?force=TOKEN_QUE_ESTA_NO_.ENV`. Sem o token, a tela é bloqueada.

## 2. Tour do painel (`/admin`)

| Menu | Para que serve |
|---|---|
| **Dashboard** | Inscritos, check-ins, gráficos + status das integrações |
| **Inscritos** | Buscar, filtrar, ver ficha, editar/cancelar, exportar CSV/Excel |
| **Check-in** | Operação de portaria (scanner QR + busca manual) |
| **Comunicação** | E-mail/WhatsApp: disparos, fila, automações, SMTP, WhatsApp |
| **Conteúdo** | Textos da landing, Sobre, Experiência, WCM Awards, FAQ, Termos |
| **Programação** | Grade de horários (arrastar ↑↓ reordena) |
| **Palestrantes** | Convidados com foto e bio |
| **Galeria** | Fotos da landing (upload, legenda, ordem ↑↓) |
| **Depoimentos** | Prova social antes dos ingressos (foto, cargo, texto) |
| **Ingressos** | Lotes, preços, quantidades, janela de vendas, pausar |
| **Formulário** | Campos da inscrição (padrão + personalizados) |
| **Configurações** | Dados do evento, local, mapa, contato, imagens, status |
| **Relatórios** | Consolidação + impressão/PDF |
| **Usuários** | Equipe, perfis e permissões |
| **Auditoria** | Quem fez o quê, quando e de qual IP |

## 3. Configuração antes de divulgar (checklist)

- [ ] **Configurações**: nome, datas, horário, local, contatos, imagens (logo/hero).
- [ ] **Ingressos**: lotes com preço, quantidade e período de vendas.
- [ ] **Formulário**: ative os campos padrão desejados; crie perguntas extras.
- [ ] **Conteúdo**: revise hero, sobre, diferenciais, números, FAQ, termos.
- [ ] **Comunicação → E-mail**: SMTP (host, porta, usuário, senha) + **Testar**.
- [ ] **Comunicação → WhatsApp** (opcional): Phone Number ID + token Meta + **Testar**.
- [ ] **Comunicação → Automações**: revise boas-vindas e lembretes (D-7, D-1, dia).
- [ ] **Cron** (recomendado): no cPanel → Cron Jobs, a cada 5 min:
      `php /home/SEU_USUARIO/public_html/cron.php SEU_TOKEN`
      (token no `.env` como `CRON_TOKEN`). Sem cron, a fila anda ao abrir a
      página Comunicação + botão **Processar agora**.
- [ ] **Teste de ponta a ponta**: inscreva um e-mail seu → receba o ticket →
      faça check-in de teste → cancele a inscrição de teste.

## 4. Dia do evento — operação de portaria

1. Abra `/admin/checkin` no celular/tablet/notebook (use HTTPS).
2. Clique **Iniciar leitura** e autorize a câmera (use a traseira).
3. Aponte para o QR do participante:
   - **✓ CHECK-IN REALIZADO** (bip agudo) → pode entrar.
   - **⚠ JÁ UTILIZADO** → ticket já entrou; confira documento se necessário.
   - **✕ CANCELADA / NÃO ENCONTRADO** (bip grave) → encaminhe ao suporte.
4. Sem câmera ou QR apagado: **Busca manual** pelo código `EVT-…` (Enter valida).
5. **Sem internet?** A tela avisa "SEM CONEXÃO". O check-in exige conexão —
   garanta um 4G/wi-fi de contingência na portaria.
6. Contadores atualizam sozinhos a cada 15 s. Várias portarias podem operar
   juntas: o sistema impede confirmação dupla do mesmo ticket.

> Dica: crie usuários com perfil **Credenciamento** (só veem inscritos + check-in).

## 5. Comunicação no dia

- **Avisos urgentes** (mudança de sala, horário): Comunicação → **Novo disparo**,
  filtre o público, confira a contagem, confirme. O lote inicial sai na hora;
  o restante via cron ou **Processar agora**.
- **Fila**: Aguardando/Processando/Enviados/Falhas/Cancelados. Falha individual
  pode ser repetida (**Repetir**). Item errado pode ser **Cancelado**.
- **WhatsApp**: fora da janela de 24 h do contato, a Meta pode exigir template
  aprovado — prefira e-mail para lembretes frios em massa.

## 6. Pós-evento

1. **Configurações → Status → Encerrado**: fecha novas inscrições (a página
   avisa; tickets existentes continuam acessíveis).
2. **Relatórios**: confira presença, origem, funil; imprima/gere PDF.
3. **Inscritos → Exportar**: CSV (planilhas) ou XLS (Excel, preserva CPF/zeros).
4. **Disparo de agradecimento** + pesquisa (Comunicação → Novo disparo).
5. **Auditoria**: exporte/arquive mentalmente quem operou o quê.

## 7. Perfis e segurança

- **Administrador**: tudo. **Nunca deixe zero admins ativos** (o sistema bloqueia).
- Senhas de equipe criadas pelo gestor **devem ser trocadas no 1º acesso**.
- Esqueceu a senha? Na tela de login, **Esqueci minha senha** envia um link
  válido por 1 hora (requer SMTP configurado).
- Sessões expiram por inatividade (padrão 2 h, ajustável no `.env`).
- Login com limite de tentativas; falhas ficam na auditoria.
- Segredos (SMTP, WhatsApp) ficam **criptografados**; nunca aparecem na tela.

## 8. Problemas comuns

| Sintoma | Causa provável | Solução |
|---|---|---|
| E-mail não chega | SMTP não configurado / senha mudou | Comunicação → E-mail → Testar; veja erro na Fila |
| Fila só cresce | Sem cron | Configure o cron ou abra Comunicação (processa lote) |
| WhatsApp falha | Token expirado / nº inválido | Reconecte em Comunicação → WhatsApp → Testar |
| QR não lê | Câmera sem permissão / HTTP | Use HTTPS e autorize a câmera; use busca manual |
| "Sessão expirada" | Inatividade | Recarregue e entre de novo |
| Instalação travada | Requisito vermelho | Fale com a hospedagem (PHP 7.4+, PDO, openssl) |
| Página em branco | `.env` errado / permissão | Confira `storage/logs/php-error.log` via FTP |

---

*Versão da plataforma: ver rodapé do painel. Suporte técnico: quem instalou a plataforma.*
