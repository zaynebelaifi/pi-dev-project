"""Post-Delivery AI Feedback Analyzer
====================================
When a customer leaves a review after delivery is marked "delivered":
    - Gemini classifies the sentiment (positive / neutral / negative)
  - Positive reviews are stored as testimonials (shown on homepage)
  - Negative/neutral reviews are routed to support automatically

Stack: FastAPI + Google Generative AI + SQLAlchemy (uses project DB when configured)

Install:
    pip install -r requirements.txt

Run:
    uvicorn delivery_feedback_ai:app --reload
"""

import json
import os
import re
import urllib.request
from datetime import datetime

from google import genai
from dotenv import load_dotenv
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from sqlalchemy import Column, DateTime, Float, Integer, String, Text, create_engine, text
from sqlalchemy.orm import declarative_base, sessionmaker

dotenv_path = os.path.join(os.path.dirname(__file__), "..", ".env")
load_dotenv(dotenv_path=dotenv_path, override=True)

# ---------------------------------------------------------------------------
# App setup
# ---------------------------------------------------------------------------

app = FastAPI(title="Delivery Feedback AI")

cors_origins_raw = os.getenv("FEEDBACK_AI_CORS_ORIGINS", "*")
cors_origins = [o.strip() for o in cors_origins_raw.split(",") if o.strip()]
if not cors_origins:
    cors_origins = ["*"]

app.add_middleware(
    CORSMiddleware,
    allow_origins=cors_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

api_key = os.getenv("GOOGLE_API_KEY") or ""
client = genai.Client(api_key=api_key) if api_key else genai.Client()

# ---------------------------------------------------------------------------
# Database setup (uses project DB if available)
# ---------------------------------------------------------------------------

Base = declarative_base()


def normalize_db_url(raw_url: str) -> str:
    if raw_url.startswith("mysql://"):
        return raw_url.replace("mysql://", "mysql+pymysql://", 1)
    if raw_url.startswith("postgres://"):
        return raw_url.replace("postgres://", "postgresql+psycopg2://", 1)
    return raw_url


def get_db_url() -> str:
    raw = os.getenv("FEEDBACK_AI_DB_URL") or os.getenv("DATABASE_URL") or "sqlite:///./feedback.db"
    return normalize_db_url(raw)


DB_URL = get_db_url()

engine_args = {}
if DB_URL.startswith("sqlite"):
    engine_args["connect_args"] = {"check_same_thread": False}

engine = create_engine(DB_URL, pool_pre_ping=True, **engine_args)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)


class Review(Base):
    __tablename__ = "delivery_reviews"

    id = Column(Integer, primary_key=True, index=True)
    order_id = Column(String(64), nullable=False)
    customer_name = Column(String(255), nullable=False)
    customer_email = Column(String(255), nullable=False)
    review_text = Column(Text, nullable=False)
    rating = Column(Integer, nullable=True)
    sentiment = Column(String(16), nullable=True)
    confidence = Column(Float, nullable=True)
    summary = Column(String(255), nullable=True)
    routed_to = Column(String(32), nullable=True)
    support_ticket = Column(Text, nullable=True)
    created_at = Column(DateTime, nullable=False, server_default=text("CURRENT_TIMESTAMP"))


def init_db() -> None:
    Base.metadata.create_all(bind=engine)


init_db()

# ---------------------------------------------------------------------------
# Pydantic models
# ---------------------------------------------------------------------------

class ReviewPayload(BaseModel):
    order_id: str
    customer_name: str
    customer_email: str
    review_text: str
    rating: int | None = None


class SupportTicket(BaseModel):
    order_id: str
    customer_name: str
    customer_email: str
    review_text: str
    sentiment: str
    summary: str
    support_message: str
    created_at: str


class Testimonial(BaseModel):
    id: int
    customer_name: str
    review_text: str
    summary: str
    rating: int | None
    created_at: str

# ---------------------------------------------------------------------------
# AI: Classify sentiment + generate summary / support ticket
# ---------------------------------------------------------------------------

CONFIDENCE_NEUTRAL_THRESHOLD = 0.6

CLASSIFY_PROMPT = """
You are an expert customer support AI for a delivery platform.

Task: classify sentiment for the review. Use the star rating as a hint, not as the only signal.
Guidance:
- 5-4 stars usually means positive unless the text is clearly negative.
- 1-2 stars usually means negative unless the text is clearly positive.
- 3 stars or mixed language often means neutral.

A customer just submitted feedback after receiving their delivery.
Analyze the review and return ONLY a valid JSON object with these fields:

{
  "sentiment": "positive" | "neutral" | "negative",
  "confidence": 0.0 to 1.0,
  "summary": "One sentence summary of the review (max 15 words)",
  "needs_support": true | false,
  "support_message": "If needs_support is true, write a professional support team internal note explaining the issue and suggested next action. Otherwise return empty string."
}

Examples:
Input: rating=5, review="Super fast delivery, everything was perfect"
Output: {"sentiment":"positive","confidence":0.9,"summary":"Fast delivery and perfect experience.","needs_support":false,"support_message":""}

Input: rating=1, review="Order arrived cold and late"
Output: {"sentiment":"negative","confidence":0.92,"summary":"Food arrived late and cold.","needs_support":true,"support_message":"Delay and temperature issue; check courier handling."}

Input: rating=3, review="Delivery was okay but packaging could improve"
Output: {"sentiment":"neutral","confidence":0.7,"summary":"Average delivery with packaging concerns.","needs_support":true,"support_message":"Packaging feedback; consider quality review."}

Input: rating=4, review="Good taste but missing one item"
Output: {"sentiment":"negative","confidence":0.75,"summary":"Missing item despite good taste.","needs_support":true,"support_message":"Missing item; verify order accuracy and offer resolution."}

Input: rating=4, review="Tasty food and quick handoff"
Output: {"sentiment":"positive","confidence":0.85,"summary":"Tasty food with quick handoff.","needs_support":false,"support_message":""}

Input: rating=2, review="Not bad, but too slow and no updates"
Output: {"sentiment":"negative","confidence":0.78,"summary":"Slow delivery with poor communication.","needs_support":true,"support_message":"Slow delivery; review courier ETA updates."}

Customer name: {name}
Order ID: {order_id}
Star rating: {rating}
Review: {review}

Return only the JSON object. No markdown, no explanation.
"""


def normalize_review_text(review_text: str) -> str:
        text = re.sub(r"<[^>]+>", " ", review_text or "")
        text = re.sub(r"[\r\n\t]+", " ", text)
        text = re.sub(r"\s+", " ", text).strip()
        return text


def analyze_review(payload: ReviewPayload) -> dict:
    normalized_review = normalize_review_text(payload.review_text)
    if not normalized_review:
        return {
            "sentiment": "neutral",
            "confidence": 0.0,
            "summary": "",
            "needs_support": True,
            "support_message": "Empty review text; manual review recommended.",
        }

    prompt = CLASSIFY_PROMPT.format(
        name=payload.customer_name,
        order_id=payload.order_id,
        rating=payload.rating if payload.rating else "not provided",
        review=normalized_review,
    )

    response = client.models.generate_content(
        model="models/gemini-2.5-flash",
        contents=prompt,
    )
    raw = (getattr(response, "text", "") or "").strip()
    if raw:
        print(f"Gemini responded with: {raw}")

    if raw.startswith("```"):
        raw = raw.split("```", 1)[1]
        if raw.startswith("json"):
            raw = raw[4:]
    raw = raw.strip()

    data = None
    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        # Fallback: attempt to extract JSON object from mixed output
        start = raw.find("{")
        end = raw.rfind("}")
        if start != -1 and end != -1 and end > start:
            snippet = raw[start : end + 1]
            try:
                data = json.loads(snippet)
            except json.JSONDecodeError:
                data = None

    def detect_sentiment_from_text(text: str) -> str | None:
        cleaned = text.replace('"', '').strip().lower()
        if "positive" in cleaned:
            return "positive"
        if "negative" in cleaned:
            return "negative"
        if "neutral" in cleaned:
            return "neutral"
        return None

    if data is None:
        # Final fallback: extract sentiment from free-form output
        sentiment = detect_sentiment_from_text(raw)
        if not sentiment:
            sentiment = "neutral"

        summary = ""
        summary_match = re.search(r"summary\"?\s*:\s*\"([^\"]+)\"", raw, re.IGNORECASE)
        if summary_match:
            summary = summary_match.group(1).strip()

        return {
            "sentiment": sentiment,
            "confidence": 0.0,
            "summary": summary,
            "needs_support": sentiment != "positive",
            "support_message": "",
        }

    # Normalize/validate required fields
    sentiment = str(data.get("sentiment", "")).lower().strip()
    if sentiment not in {"positive", "neutral", "negative"}:
        sentiment = detect_sentiment_from_text(raw) or "neutral"
    confidence = float(data.get("confidence", 0.0))
    summary = str(data.get("summary", "")).strip()
    support_message = str(data.get("support_message", ""))

    # Confidence and rating calibration to reduce neutral drift.
    rating = payload.rating
    if confidence < CONFIDENCE_NEUTRAL_THRESHOLD:
        if rating is not None and rating >= 4 and sentiment != "negative":
            sentiment = "positive"
        elif rating is not None and rating <= 2 and sentiment != "positive":
            sentiment = "negative"
        else:
            sentiment = "neutral"

    if not summary:
        summary = summarize_review(normalized_review)

    return {
        "sentiment": sentiment,
        "confidence": confidence,
        "summary": summary,
        "needs_support": bool(data.get("needs_support", sentiment != "positive")),
        "support_message": support_message,
    }


def heuristic_sentiment(review_text: str) -> str:
    text = review_text.lower()
    positive_terms = ["good", "great", "amazing", "excellent", "perfect", "love", "fast", "awesome", "nice"]
    negative_terms = ["bad", "terrible", "awful", "slow", "late", "broken", "cold", "wrong", "poor"]

    if any(term in text for term in positive_terms):
        return "positive"
    if any(term in text for term in negative_terms):
        return "negative"
    return "neutral"


def summarize_review(review_text: str) -> str:
    words = review_text.strip().split()
    return " ".join(words[:15])

# ---------------------------------------------------------------------------
# Support alerts (WhatsApp Cloud API)
# ---------------------------------------------------------------------------


def send_support_alert(payload: ReviewPayload, analysis: dict) -> None:
    phone = os.getenv("SUPPORT_ALERT_PHONE")
    api_url = os.getenv("WHATSAPP_API_URL")
    token = os.getenv("WHATSAPP_API_TOKEN")

    if not phone or not api_url or not token:
        return

    support_message = analysis.get("support_message", "")
    body_text = (
        "Support alert: New feedback requires attention. "
        f"Order #{payload.order_id} - {payload.customer_name}. "
        f"Summary: {analysis.get('summary', '')}. "
        f"Note: {support_message}"
    ).strip()

    payload_body = {
        "messaging_product": "whatsapp",
        "to": phone,
        "type": "text",
        "text": {"body": body_text},
    }

    req = urllib.request.Request(
        api_url,
        data=json.dumps(payload_body).encode("utf-8"),
        headers={
            "Content-Type": "application/json",
            "Authorization": f"Bearer {token}",
        },
        method="POST",
    )

    try:
        urllib.request.urlopen(req, timeout=6)
    except Exception:
        return

# ---------------------------------------------------------------------------
# Helper: save to DB
# ---------------------------------------------------------------------------


def save_review(payload: ReviewPayload, analysis: dict, routed_to: str) -> int:
    db = SessionLocal()
    try:
        review = Review(
            order_id=payload.order_id,
            customer_name=payload.customer_name,
            customer_email=payload.customer_email,
            review_text=payload.review_text,
            rating=payload.rating,
            sentiment=analysis.get("sentiment"),
            confidence=analysis.get("confidence"),
            summary=analysis.get("summary"),
            routed_to=routed_to,
            support_ticket=analysis.get("support_message", ""),
        )
        db.add(review)
        db.commit()
        db.refresh(review)
        return int(review.id)
    finally:
        db.close()

# ---------------------------------------------------------------------------
# POST /webhook/review — main entry point (call this after delivery confirmed)
# ---------------------------------------------------------------------------


@app.post("/webhook/review")
async def receive_review(payload: ReviewPayload):
    try:
        analysis = analyze_review(payload)
    except Exception as e:
        # Fail open: save the review as neutral if AI fails
        print(f"ERROR: AI analysis failed: {str(e)}")
        sentiment = heuristic_sentiment(payload.review_text)
        analysis = {
            "sentiment": sentiment,
            "confidence": 0.0,
            "summary": summarize_review(payload.review_text),
            "needs_support": sentiment != "positive",
            "support_message": "AI analysis failed; please review manually.",
        }

    sentiment = analysis["sentiment"]

    if sentiment == "positive":
        routed_to = "testimonials"
        action = "Added to homepage testimonials"
    else:
        routed_to = "support"
        action = "Routed to support queue"

    review_id = save_review(payload, analysis, routed_to)

    if routed_to == "support":
        send_support_alert(payload, analysis)

    return {
        "review_id": review_id,
        "sentiment": sentiment,
        "confidence": analysis["confidence"],
        "summary": analysis["summary"],
        "routed_to": routed_to,
        "action": action,
        "support_message": analysis.get("support_message", "") if routed_to == "support" else None,
    }

# ---------------------------------------------------------------------------
# GET /testimonials — fetch positive reviews for the homepage widget
# ---------------------------------------------------------------------------


@app.get("/testimonials", response_model=list[Testimonial])
async def get_testimonials(limit: int = 10):
    db = SessionLocal()
    try:
        rows = (
            db.query(Review)
            .filter(Review.routed_to == "testimonials")
            .order_by(Review.created_at.desc())
            .limit(limit)
            .all()
        )
        return [
            Testimonial(
                id=r.id,
                customer_name=r.customer_name,
                review_text=r.review_text,
                summary=r.summary or "",
                rating=r.rating,
                created_at=r.created_at.isoformat() if r.created_at else datetime.utcnow().isoformat(),
            )
            for r in rows
        ]
    finally:
        db.close()

# ---------------------------------------------------------------------------
# GET /support/queue — fetch complaints for the support team dashboard
# ---------------------------------------------------------------------------


@app.get("/support/queue", response_model=list[SupportTicket])
async def get_support_queue(limit: int = 50):
    db = SessionLocal()
    try:
        rows = (
            db.query(Review)
            .filter(Review.routed_to == "support")
            .order_by(Review.created_at.desc())
            .limit(limit)
            .all()
        )
        return [
            SupportTicket(
                order_id=r.order_id,
                customer_name=r.customer_name,
                customer_email=r.customer_email,
                review_text=r.review_text,
                sentiment=r.sentiment or "unknown",
                summary=r.summary or "",
                support_message=r.support_ticket or "",
                created_at=r.created_at.isoformat() if r.created_at else datetime.utcnow().isoformat(),
            )
            for r in rows
        ]
    finally:
        db.close()


if __name__ == "__main__":
    import uvicorn

    uvicorn.run("delivery_feedback_ai:app", host="0.0.0.0", port=8000, reload=True)
