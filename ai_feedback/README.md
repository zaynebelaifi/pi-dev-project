# Post-Delivery Feedback AI

This service classifies delivery reviews using Google Gemini and routes them to either testimonials or support.

## Setup

1. Install Python dependencies:

```bash
pip install -r requirements.txt
```

2. Add the required environment variables to the project root `.env`:

```
GOOGLE_API_KEY=your_key_here
FEEDBACK_AI_DB_URL=
FEEDBACK_AI_CORS_ORIGINS=http://localhost:8000
SUPPORT_ALERT_PHONE=
```

If `FEEDBACK_AI_DB_URL` is empty, the service falls back to `DATABASE_URL` from the Symfony project or SQLite `feedback.db`.

3. Run the API:

```bash
uvicorn delivery_feedback_ai:app --reload
```

## Endpoints

- `POST /webhook/review`
- `GET /testimonials`
- `GET /support/queue`

## Tests

```bash
python -m unittest ai_feedback/tests/test_webhook_review.py
```

## Notes

- `SUPPORT_ALERT_PHONE` uses the existing WhatsApp Cloud API configuration (`WHATSAPP_API_URL` and `WHATSAPP_API_TOKEN`) from the project `.env`.
- Update the frontend widget `API_BASE` value to match your deployed backend.
