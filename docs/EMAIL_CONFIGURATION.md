# Email configuration (SMTP, Mailtrap, production)

Laravel sends:

- **GST invoice PDFs** to clients (`POST /api/invoices/{id}/send-email`).
- **Contact form** notifications to an admin inbox when configured.

## Environment variables (`backend/.env`)

| Variable | Purpose |
|----------|---------|
| `MAIL_MAILER` | `smtp` for real mail, `log` for development (writes to log, no network). |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | SMTP server (Mailtrap, Mailgun SMTP, SES SMTP, etc.). |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Sender shown to recipients. |
| `MAIL_ADMIN_ADDRESS` | Optional. If set, contact form submissions are emailed here via `ContactSubmittedMail`. |

## Mailtrap (development)

1. Create an inbox at [Mailtrap](https://mailtrap.io/).
2. Use the SMTP credentials in `.env`, for example:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS="noreply@yourdomain.test"
MAIL_FROM_NAME="GST Invoice Dev"
MAIL_ADMIN_ADDRESS="you@example.com"
```

3. Run `php artisan config:clear` after edits.

## Production

- Use your provider’s SMTP or a native driver (`mailgun`, `ses`, `postmark`, etc.) as documented in [Laravel Mail](https://laravel.com/docs/mail).
- Set `MAIL_FROM_ADDRESS` to a domain you are allowed to send from (SPF/DKIM configured at DNS).

## Queues (optional)

For heavy PDF mail, consider dispatching `Mail` on a queue (`QUEUE_CONNECTION=database` is already common in `.env.example`) and running `php artisan queue:work`.

## Quick test without SMTP

```env
MAIL_MAILER=log
```

Invoice and contact flows still run; message content is written to `storage/logs/laravel.log`.
