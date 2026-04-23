# SMS Deployment Checklist (Twilio)

## 1) Pre-deploy
- Confirm these environment variables exist in production:
  - `TWILIO_SID`
  - `TWILIO_AUTH_TOKEN`
  - `TWILIO_PHONE` (E.164, e.g. `+13203612548`)
- Confirm dependency is installed:
  - `twilio/sdk`

## 2) Deploy commands
Run from project root:

```powershell
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console lint:container --env=prod
```

## 3) Required app commands
Verify both commands exist:

```powershell
php bin/console list app
```

Expected commands:
- `app:update-event-statuses`
- `app:send-event-reminders`

## 4) Windows Task Scheduler jobs
Replace the path in `cd /d` if your deployment path differs.

### Event status updater (every 5 min)
```powershell
schtasks /Create /SC MINUTE /MO 5 /TN "Big4 Update Event Statuses" /TR "cmd /c cd /d C:\Users\Legion\OneDrive\Documents\pi-dev-project && php bin\console app:update-event-statuses --env=prod" /F
```

### SMS reminder sender (every 1 min)
```powershell
schtasks /Create /SC MINUTE /MO 1 /TN "Big4 Send Event Reminders" /TR "cmd /c cd /d C:\Users\Legion\OneDrive\Documents\pi-dev-project && php bin\console app:send-event-reminders --env=prod" /F
```

## 5) Smoke test (safe)
- Create/update one event to start in ~45-60 minutes.
- Register one client user with a valid E.164 phone number in `user.phone_number`.
- Run manually:

```powershell
php bin/console app:send-event-reminders
```

- Confirm output counters (`sent`, `failed`, `skipped`) and check application logs.
- Confirm event `sms_reminder_sent` becomes true only after successful reminder run.

## 6) Security
- Never commit Twilio credentials.
- If secrets were shared/exposed, rotate `TWILIO_AUTH_TOKEN` in Twilio and update production env immediately.
